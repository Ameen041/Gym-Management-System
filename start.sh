#!/bin/bash
set -e

echo "🚀 Starting Ameen Gym (Laravel)..."

# Debug (safe)
echo "DB_CONNECTION=${DB_CONNECTION}"
echo "DB_HOST=${DB_HOST}"
echo "DB_PORT=${DB_PORT}"
echo "DB_DATABASE=${DB_DATABASE}"
echo "DB_USERNAME=${DB_USERNAME}"

# Ensure dirs exist
mkdir -p /var/www/html/storage /var/www/html/bootstrap/cache
mkdir -p /var/www/html/storage/framework/{cache,sessions,views}

# Fix permissions (Render sometimes needs aggressive perms)
chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache || true

# Clear caches so env vars are re-read
php artisan optimize:clear || true

# Run migrations
php artisan migrate --force

# Rebuild caches (optional)
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

echo "✅ Laravel Ready. Starting Apache..."
exec apache2-foreground