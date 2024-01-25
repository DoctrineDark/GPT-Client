ARG PHP_VERSION=7.1
FROM php:${PHP_VERSION}-fpm

RUN apt-get update && apt-get install -y \
    libpq-dev \
    wget \
    zlib1g-dev \
    libmcrypt-dev \
    libzip-dev \
    openssl

RUN docker-php-ext-install pdo mysqli pdo_mysql

RUN wget https://getcomposer.org/installer -O - -q | php -- --install-dir=/bin --filename=composer --quiet

WORKDIR /var/www
