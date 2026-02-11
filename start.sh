#!/usr/bin/env sh
set -e

echo "✅ Starting Ameen Gym (Laravel) ..."

php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Run migrations but don't stop the server if DB isn't ready yet
php artisan migrate --force || true

exec apache2-foreground