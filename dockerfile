# Use official PHP 8.3 with Apache
FROM php:8.4-apache

# Install PHP extensions needed for Laravel
RUN apt-get update && apt-get install -y \
    libzip-dev unzip git curl \
    && docker-php-ext-install pdo pdo_mysql zip

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Generate app key
RUN php artisan key:generate

# Cache configs/routes/views
RUN php artisan config:cache
RUN php artisan route:cache
RUN php artisan view:cache

# Run migrations (optional, if database ready)
# RUN php artisan migrate --force

# Expose Apache port
EXPOSE 10000

# Start Apache
CMD ["apache2-foreground"]
