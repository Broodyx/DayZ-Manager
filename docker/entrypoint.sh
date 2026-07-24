#!/bin/sh
set -eu

mkdir -p storage/app/dayz storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY is required; refusing to start." >&2
    exit 1
fi

MAX_UPLOAD_SIZE="${MAX_UPLOAD_SIZE:-10240}"
case "$MAX_UPLOAD_SIZE" in (*[!0-9]*|'') echo "MAX_UPLOAD_SIZE must be an integer in KB." >&2; exit 1;; esac
sed -i "s/__MAX_UPLOAD_SIZE__/${MAX_UPLOAD_SIZE}/g" /etc/nginx/http.d/default.conf /usr/local/etc/php/conf.d/dayz-manager.ini

php artisan config:cache
php artisan route:cache
php artisan view:cache

case "${APP_ROLE:-app}" in
    app)
        php artisan migrate --force
        php-fpm -D
        exec nginx -g 'daemon off;'
        ;;
    worker)
        exec php artisan queue:work --sleep=3 --tries=3 --timeout=90
        ;;
    scheduler)
        exec php artisan schedule:work
        ;;
    *)
        echo "Unknown APP_ROLE: ${APP_ROLE}" >&2
        exit 1
        ;;
esac
