#!/bin/bash
set -e

echo "🚀 Starting Ameen Gym (Laravel)..."

php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan migrate --force

echo "✅ Laravel Ready. Starting Apache..."

exec apache2-foreground