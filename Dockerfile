FROM php:8.3-apache

# Enable Apache rewrite + set DocumentRoot to /public
RUN a2enmod rewrite \
 && sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# System deps + PHP extensions (MySQL + PostgreSQL)
RUN apt-get update && apt-get install -y \
    zip \
    unzip \
    git \
    curl \
    libpq-dev \
 && docker-php-ext-install pdo pdo_mysql mysqli pdo_pgsql pgsql \
 && rm -rf /var/lib/apt/lists/*

# Allow exercise video uploads (default PHP limit is 2MB)
RUN { \
      echo 'upload_max_filesize=100M'; \
      echo 'post_max_size=110M'; \
      echo 'memory_limit=256M'; \
    } > /usr/local/etc/php/conf.d/uploads.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy project
COPY . .

# Install PHP deps
RUN composer install --no-dev --optimize-autoloader --ignore-platform-reqs

# Permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
 && chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache

# Start script
COPY start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80
CMD ["/start.sh"]