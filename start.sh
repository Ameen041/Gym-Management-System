#!/bin/bash
set -e

echo "🚀 Starting Ameen Gym (Laravel)..."

# Debug (safe)
echo "DB_CONNECTION=${DB_CONNECTION}"
echo "DB_HOST=${DB_HOST}"
echo "DB_PORT=${DB_PORT}"
echo "DB_DATABASE=${DB_DATABASE}"
echo "DB_USERNAME=${DB_USERNAME}"

# Ensure dirs exist + permissions
mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache
mkdir -p /var/www/html/storage/framework/{cache,sessions,views}

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache || true

# IMPORTANT: remove any cached config files (hard reset)
rm -f /var/www/html/bootstrap/cache/config.php || true
rm -f /var/www/html/bootstrap/cache/routes-v7.php || true
rm -f /var/www/html/bootstrap/cache/services.php || true
rm -f /var/www/html/bootstrap/cache/packages.php || true

# Clear cached stuff
php artisan optimize:clear || true

# Run migrations
php artisan migrate --force

# Cache again (optional)
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

echo "✅ Laravel Ready. Starting Apache..."
exec apache2-foreground