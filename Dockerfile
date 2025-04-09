# Gunakan base image PHP + Apache
FROM php:8.2-apache

# Install ekstensi dan dependency sistem
RUN apt-get update && apt-get install -y \
    git curl unzip zip libpng-dev libonig-dev libxml2-dev libzip-dev \
    && docker-php-ext-install pdo pdo_mysql mbstring zip exif pcntl bcmath

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy file Laravel ke dalam container
COPY . .

# Install dependencies Laravel
RUN composer install --optimize-autoloader --no-dev

# Set permission
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Jalankan artisan command yang dibutuhkan
RUN php artisan config:cache \
    && php artisan storage:link

# Expose port
EXPOSE 80

# Start Laravel menggunakan PHP built-in server
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=80"]
