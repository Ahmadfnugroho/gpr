FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    libzip-dev \
    libonig-dev \
    libicu-dev \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    curl \
    git

RUN apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl

# Install ekstensi PHP
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www

RUN rm -rf /var/www/html

# Copy source code
COPY . /var/www
COPY ./storage/certs /var/www/storage/certs


# Gunakan user root untuk install dep
USER root

RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Atur permission
RUN chown -R www-data:www-data /var/www

# Kembali ke user aplikasi
USER www-data

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
