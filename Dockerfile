FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts --optimize-autoloader
COPY . .
RUN composer dump-autoload --no-dev --classmap-authoritative --no-interaction

FROM php:8.4-fpm-alpine
RUN apk add --no-cache nginx libpq-dev libzip-dev libxml2-dev icu-dev linux-headers \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql zip dom intl opcache pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /tmp/pear
WORKDIR /var/www/html
COPY --from=vendor /app .
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/dayz-manager.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint \
    && mkdir -p storage/app/dayz storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache
EXPOSE 8080
ENTRYPOINT ["entrypoint"]
