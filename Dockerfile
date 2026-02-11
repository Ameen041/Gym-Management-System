FROM php:8.2-apache

# Enable Apache rewrite + set DocumentRoot to /public
RUN a2enmod rewrite \
 && sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# System deps + PHP extensions (MYSQL ✅)
RUN apt-get update && apt-get install -y \
    zip \
    unzip \
    git \
    curl \
 && docker-php-ext-install pdo pdo_mysql mysqli \
 && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy project
COPY . .

# Install PHP deps
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# Permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
 && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Start script
COPY start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80
CMD ["/start.sh"]