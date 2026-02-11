#!/bin/bash
set -e

echo "🚀 Starting Ameen Gym (Laravel)..."

# Fix permissions at runtime (important on Render)
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache || true

# Clear caches so env vars are re-read
php artisan config:clear || true
php artisan cache:clear || true
php artisan route:clear || true
php artisan view:clear || true

# Run migrations
php artisan migrate --force

# Rebuild caches (optional but good for production)
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Laravel Ready. Starting Apache..."
exec apache2-foreground