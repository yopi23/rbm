# Gunakan image PHP 8.0
FROM php:8.0-fpm

# Install dependensi
RUN apt-get update && apt-get install -y libpng-dev libjpeg-dev libfreetype6-dev libzip-dev unzip git && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install gd zip pdo pdo_mysql

# Set working directory
WORKDIR /var/www

# Copy composer.lock dan composer.json
COPY composer.lock composer.json ./

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install PHP dependencies
RUN composer install

# Copy seluruh file proyek
COPY . .

# Set permission
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Expose port
EXPOSE 9000

# Jalankan PHP-FPM
CMD ["php-fpm"]
