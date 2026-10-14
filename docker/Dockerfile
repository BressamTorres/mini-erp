FROM php:8.4-fpm

ENV TZ=America/Sao_Paulo

RUN echo "date.timezone=${TZ}" > /usr/local/etc/php/conf.d/timezone.ini

WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    libzip-dev \
    && docker-php-ext-install pdo_mysql zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY . /var/www

RUN composer install --no-interaction --prefer-dist

CMD ["php-fpm"]
