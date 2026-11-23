# syntax=docker/dockerfile:1.7

ARG PHP_VERSION=8.4

# ---------- vendor ----------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader
COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

# ---------- runtime ----------
FROM php:${PHP_VERSION}-fpm-alpine AS runtime

RUN apk add --no-cache nginx supervisor icu-dev libpng-dev libjpeg-turbo-dev freetype-dev libzip-dev oniguruma-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" pdo_mysql bcmath gd intl zip exif opcache pcntl

WORKDIR /var/www/html

COPY --from=vendor /app /var/www/html
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint

RUN rm -f /usr/local/etc/php-fpm.d/www.conf \
 && chmod +x /usr/local/bin/entrypoint \
 && mkdir -p storage/app/public storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
 && chown -R 10001:10001 /var/www/html

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    RUN_MIGRATIONS=false

USER 10001:10001
EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
