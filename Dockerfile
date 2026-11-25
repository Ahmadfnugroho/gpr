# syntax=docker/dockerfile:1.7

ARG PHP_VERSION=8.4

# ---------- vendor ----------
# Pin the vendor stage to the same PHP minor as runtime. composer:2 now ships
# PHP 8.5, which the lock file (php 8.1 - 8.4) rejects.
FROM php:${PHP_VERSION}-cli-alpine AS vendor
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader \
      --ignore-platform-reqs
COPY . .
# --no-scripts: package:discover needs runtime extensions/DB that this stage lacks.
# Laravel rebuilds bootstrap/cache/packages.php on first boot in the runtime stage.
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative --no-scripts

# ---------- runtime ----------
FROM php:${PHP_VERSION}-fpm-alpine AS runtime

RUN apk add --no-cache nginx supervisor mariadb-client icu-dev libpng-dev libjpeg-turbo-dev freetype-dev libwebp-dev libzip-dev oniguruma-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install -j"$(nproc)" pdo_mysql bcmath gd intl zip exif opcache pcntl

WORKDIR /var/www/html

COPY --from=vendor /app /var/www/html
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint

RUN rm -f /usr/local/etc/php-fpm.d/www.conf /usr/local/etc/php-fpm.d/zz-docker.conf /usr/local/etc/php-fpm.d/docker.conf \
 && chmod +x /usr/local/bin/entrypoint \
 # Laravel's schema-dump loader shells out to the mysql CLI; the loopback
 # bridge makes the server present a self-signed cert that the client rejects.
 && printf '[client]\nskip-ssl\n' > /etc/my.cnf \
 && mkdir -p public storage/app/public storage/framework/{cache,sessions,views} storage/logs bootstrap/cache /var/lib/nginx/logs /var/log/nginx \
 && chown -R 10001:10001 /var/www/html /var/lib/nginx /var/log/nginx

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    RUN_MIGRATIONS=false

USER 10001:10001
EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
