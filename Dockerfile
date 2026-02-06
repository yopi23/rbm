#FROM php:8.1-fpm
FROM --platform=linux/amd64 php:8.3-fpm
# FROM shinsenter/frankenphp:latest

COPY composer.* /www/wwwroot/rbm/

WORKDIR /www/wwwroot/rbm

RUN apt-get update && apt-get install -y \
    build-essential \
    libmcrypt-dev \
    mariadb-client \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl \
    libzip-dev \
    zip

RUN apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_mysql gd zip

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN groupadd -g 1000 www
RUN useradd -u 1000 -ms /bin/bash -g www www
COPY . .

COPY --chown=www:www . .

USER www

EXPOSE 9000

CMD [ "php-fpm"]
# CMD ["frankenphp", "start"]
