#!/bin/sh
set -eu

cd /var/www/html

mkdir -p storage/app/dayz storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY is required; refusing to start." >&2
    exit 1
fi

MAX_UPLOAD_SIZE="${MAX_UPLOAD_SIZE:-100M}"
POST_MAX_SIZE="${POST_MAX_SIZE:-110M}"
PHP_MEMORY_LIMIT="${PHP_MEMORY_LIMIT:-512M}"
PHP_MAX_EXECUTION_TIME="${PHP_MAX_EXECUTION_TIME:-300}"

for value in "$MAX_UPLOAD_SIZE" "$POST_MAX_SIZE" "$PHP_MEMORY_LIMIT"; do
    case "$value" in
        *[!0-9KkMmGg]*|'') echo "PHP size values must use formats such as 100M." >&2; exit 1 ;;
    esac
done
case "$PHP_MAX_EXECUTION_TIME" in
    *[!0-9]*|'') echo "PHP_MAX_EXECUTION_TIME must be an integer." >&2; exit 1 ;;
esac

sed -i \
    -e "s/__MAX_UPLOAD_SIZE__/${MAX_UPLOAD_SIZE}/g" \
    -e "s/__POST_MAX_SIZE__/${POST_MAX_SIZE}/g" \
    -e "s/__PHP_MEMORY_LIMIT__/${PHP_MEMORY_LIMIT}/g" \
    -e "s/__PHP_MAX_EXECUTION_TIME__/${PHP_MAX_EXECUTION_TIME}/g" \
    /etc/nginx/http.d/default.conf /usr/local/etc/php/conf.d/dayz-manager.ini

wait_for_service() {
    name="$1"
    host="$2"
    port="$3"
    attempts=60

    until php -r "\$socket = @fsockopen('$host', $port, \$errorCode, \$errorMessage, 1); if (! \$socket) { exit(1); } fclose(\$socket);" >/dev/null 2>&1; do
        attempts=$((attempts - 1))
        if [ "$attempts" -le 0 ]; then
            echo "Timed out waiting for $name at $host:$port." >&2
            exit 1
        fi
        echo "Waiting for $name at $host:$port..."
        sleep 2
    done
}

wait_for_service PostgreSQL "${DB_HOST:-database}" "${DB_PORT:-5432}"
wait_for_service Redis "${REDIS_HOST:-redis}" "${REDIS_PORT:-6379}"

cache_application() {
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
}

mode="${1:-app}"
case "$mode" in
    app)
        php artisan migrate --force
        cache_application
        php-fpm -D
        exec nginx -g 'daemon off;'
        ;;
    worker)
        cache_application
        exec php artisan queue:work --sleep=2 --tries=3 --timeout=300
        ;;
    scheduler)
        cache_application
        exec php artisan schedule:work
        ;;
    *)
        exec "$@"
        ;;
esac
