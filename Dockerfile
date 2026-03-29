# Use official PHP 8.4 with Apache
FROM php:8.4-apache

# Install system dependencies + PHP extensions
RUN apt-get update && apt-get install -y \
    libzip-dev unzip git curl mariadb-client \
    && docker-php-ext-install pdo pdo_mysql zip

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy all project files
COPY . .

# Set Apache document root to Laravel public folder
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Fix Apache MPM conflict + enable rewrite
RUN a2dismod mpm_event mpm_worker \
 && a2enmod mpm_prefork \
 && a2enmod rewrite

# Install Laravel dependencies (optimized for production)
RUN composer install --no-dev --optimize-autoloader

# Cache Laravel configs (safe ones only)
RUN php artisan config:cache || true
RUN php artisan route:cache || true
RUN php artisan view:cache || true

# Set proper permissions
RUN chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

# Railway uses dynamic port
ENV PORT=8080
EXPOSE 8080

# Start Apache with dynamic port support
CMD sed -i "s/80/${PORT}/g" /etc/apache2/ports.conf \
 && sed -i "s/:80/:${PORT}/g" /etc/apache2/sites-available/000-default.conf \
 && apache2-foreground
