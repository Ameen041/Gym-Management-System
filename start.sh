#!/bin/bash
set -e

echo "🚀 Starting Ameen Gym (Laravel)..."

mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache
mkdir -p /var/www/html/storage/framework/{cache,sessions,views}

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache || true

# clear any cached files so env is re-read
php artisan optimize:clear || true

# make uploaded photos (storage/app/public) reachable at /storage
if [ -d public/storage ] && [ ! -L public/storage ]; then rm -rf public/storage; fi
php artisan storage:link || true

# run migrations
php artisan migrate --force

# Run seeders
php artisan db:seed --force

echo "✅ Laravel Ready. Starting Apache..."
exec apache2-foreground