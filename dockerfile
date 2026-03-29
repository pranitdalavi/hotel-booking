FROM php:8.4-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev unzip git curl mariadb-client \
    && docker-php-ext-install pdo pdo_mysql zip

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php \
    && mv composer.phar /usr/local/bin/composer

WORKDIR /var/www/html

COPY . .

# Set Apache document root
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# 🔥 FORCE FIX MPM (remove ALL and enable only one)
RUN rm -f /etc/apache2/mods-enabled/mpm_* \
 && a2enmod mpm_prefork \
 && a2enmod rewrite

# Install Laravel dependencies
RUN composer install --no-dev --optimize-autoloader

# Cache (safe)
RUN php artisan config:cache || true
RUN php artisan route:cache || true
RUN php artisan view:cache || true

# Permissions
RUN chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

ENV PORT=8080
EXPOSE 8080

# Start Apache (dynamic port)
CMD sed -i "s/80/${PORT}/g" /etc/apache2/ports.conf \
 && sed -i "s/:80/:${PORT}/g" /etc/apache2/sites-available/000-default.conf \
 && apache2-foreground
