FROM php:8.4-fpm-alpine
RUN apk add --no-cache nginx libpq-dev libzip-dev libxml2-dev icu-dev linux-headers \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql zip dom intl opcache pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps \
    && rm -rf /tmp/pear
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
ARG INSTALL_DEV_DEPENDENCIES=false
RUN if [ "$INSTALL_DEV_DEPENDENCIES" = "true" ]; then \
        composer install --no-interaction --no-progress --prefer-dist --no-scripts --optimize-autoloader; \
    else \
        composer install --no-dev --no-interaction --no-progress --prefer-dist --no-scripts --optimize-autoloader; \
    fi
COPY . .
RUN if [ "$INSTALL_DEV_DEPENDENCIES" = "true" ]; then \
        composer dump-autoload --classmap-authoritative --no-interaction; \
    else \
        composer dump-autoload --no-dev --classmap-authoritative --no-interaction; \
    fi
RUN php artisan filament:assets
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/dayz-manager.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint \
    && mkdir -p storage/app/dayz storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache
EXPOSE 80
ENTRYPOINT ["entrypoint"]
CMD ["app"]
