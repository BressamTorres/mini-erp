# syntax=docker/dockerfile:1.7

FROM php:8.4-fpm-bookworm AS php-base

ENV APP_ENV=production \
    PHP_OPCACHE_VALIDATE_TIMESTAMPS=0 \
    TZ=America/Sao_Paulo

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_mysql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /tmp/pear /var/lib/apt/lists/* \
    && printf 'date.timezone=%s\n' "$TZ" > /usr/local/etc/php/conf.d/timezone.ini \
    && printf 'opcache.enable=1\nopcache.memory_consumption=128\nopcache.max_accelerated_files=20000\nopcache.validate_timestamps=%s\n' "$PHP_OPCACHE_VALIDATE_TIMESTAMPS" > /usr/local/etc/php/conf.d/opcache-production.ini

FROM php-base AS composer-build

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --classmap-authoritative

FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts

COPY . .
RUN npm run build

FROM php-base AS app

WORKDIR /var/www/html

COPY --from=composer-build --chown=www-data:www-data /var/www/html /var/www/html
COPY --from=assets --chown=www-data:www-data /app/public/build /var/www/html/public/build
COPY --chmod=755 docker/production/entrypoint.sh /usr/local/bin/production-entrypoint

RUN mkdir -p \
        bootstrap/cache \
        storage/app/private \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/cache/locks \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
    && chown -R www-data:www-data bootstrap/cache storage

USER www-data

ENTRYPOINT ["production-entrypoint"]
CMD ["php-fpm", "-F"]

HEALTHCHECK --interval=15s --timeout=5s --start-period=20s --retries=5 \
    CMD php -r '$socket = @fsockopen("127.0.0.1", 9000); exit($socket === false ? 1 : 0);'

FROM nginx:1.27-alpine AS nginx

COPY docker/production/nginx.conf.template /etc/nginx/templates/default.conf.template
COPY --from=composer-build /var/www/html/public /var/www/html/public
COPY --from=assets /app/public/build /var/www/html/public/build

RUN mkdir -p /var/www/html/storage/app/public \
    && ln -s ../storage/app/public /var/www/html/public/storage

HEALTHCHECK --interval=15s --timeout=5s --start-period=20s --retries=5 \
    CMD wget -q -O /dev/null http://127.0.0.1/up || exit 1

FROM mysql:8.4 AS backup

COPY --chmod=755 docker/production/backup.sh /usr/local/bin/backup-database

ENTRYPOINT ["backup-database"]
