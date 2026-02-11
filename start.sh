#!/usr/bin/env bash
set -e

# Render provides PORT; make Apache listen on it
if [ -n "$PORT" ]; then
  sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
  sed -i "s/:80/:${PORT}/" /etc/apache2/sites-available/000-default.conf
fi

# Clear old caches (important if you previously cached wrong config)
php artisan config:clear || true
php artisan route:clear  || true
php artisan view:clear   || true

# Cache again using Render ENV vars
php artisan config:cache || true
php artisan route:cache  || true
php artisan view:cache   || true

# Run migrations (for demo)
php artisan migrate --force || true

# OPTIONAL: seed demo accounts if you have DemoUsersSeeder
# Uncomment if you want it always:
# php artisan db:seed --class=DemoUsersSeeder --force || true

exec apache2-foreground