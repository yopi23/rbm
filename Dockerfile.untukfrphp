# Menggunakan FrankenPHP sebagai base image
FROM shinsenter/frankenphp:latest

# Set working directory
WORKDIR /var/www/rbm

# Copy file composer ke container untuk optimasi cache
COPY composer.* /var/www/rbm/

# Install dependencies
RUN apt-get update && apt-get install -y \
    mariadb-client \
    libzip-dev \
    unzip \
    git \
    curl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions yang diperlukan
RUN docker-php-ext-install pdo pdo_mysql zip

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set permission untuk project directory
RUN chown -R www-data:www-data /var/www/rbm

# Copy semua project files ke container
COPY . .

# Expose port FrankenPHP
EXPOSE 8000

# Start FrankenPHP dengan konfigurasi
CMD ["frankenphp", "--config=/var/www/rbm/frankenphp.yaml"]
