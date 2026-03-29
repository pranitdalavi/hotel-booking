# Use official PHP 8.4 with Apache
FROM php:8.4-apache

# Install PHP extensions needed for Laravel
RUN apt-get update && apt-get install -y \
    libzip-dev unzip git curl mariadb-client \
    && docker-php-ext-install pdo pdo_mysql zip

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Set Apache document root to Laravel's public folder
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Ensure only one Apache MPM is enabled and enable rewrite
RUN a2dismod mpm_event mpm_worker || true
RUN a2enmod mpm_prefork
RUN a2enmod rewrite

# Run composer
RUN composer install --no-dev --optimize-autoloader

# Generate app key and cache configs
RUN php artisan key:generate
RUN php artisan config:cache
RUN php artisan route:cache
RUN php artisan view:cache

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
