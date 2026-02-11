FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    git curl unzip zip \
    libpq-dev \
    libzip-dev zlib1g-dev \
    libicu-dev \
    libpng-dev libjpeg-dev libfreetype6-dev \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite \
 && sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

RUN docker-php-ext-configure gd --with-freetype --with-jpeg

# ثبّت zip لحاله + باقي الاكستنشنز
RUN docker-php-ext-install zip \
 && docker-php-ext-install \
    pdo pdo_pgsql \
    mbstring bcmath intl gd

# Enable Apache rewrite
RUN a2enmod rewrite \
 && sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

# Install dependencies (ignore platform reqs to avoid crashes)
RUN composer install --no-dev --optimize-autoloader --no-scripts --ignore-platform-reqs

# Permissions
RUN chown -R www-data:www-data storage bootstrap/cache \
 && chmod -R 775 storage bootstrap/cache

# Copy start script
COPY start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]