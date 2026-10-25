#!/bin/bash
set -e

echo "🚀 Starting Himma (Laravel)..."

# storage/app holds uploads and backups: on the server it must be a persistent disk
mkdir -p /var/www/html/storage/app/public /var/www/html/storage/logs /var/www/html/bootstrap/cache
mkdir -p /var/www/html/storage/framework/{cache,sessions,views}

chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache || true

# clear any cached files so env is re-read
php artisan optimize:clear || true

# make uploaded photos (storage/app/public) reachable at /storage
if [ -d public/storage ] && [ ! -L public/storage ]; then rm -rf public/storage; fi
php artisan storage:link || true

# run migrations (a database that is still starting gets a minute)
tries=0
until php artisan migrate --force; do
  tries=$((tries + 1))
  if [ "$tries" -ge 20 ]; then echo "❌ The database did not answer."; exit 1; fi
  echo "⏳ Waiting for the database..."
  sleep 3
done

# Run seeders (exercises, foods, and the admin from ADMIN_EMAIL / ADMIN_PASSWORD)
php artisan db:seed --force

# on the live site, read settings, routes and pages once instead of on every request
if [ "${APP_ENV:-production}" = "production" ]; then
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
fi

# files created above as root must stay writable by the web server
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache || true

# scheduled tasks (daily database backup) run in the background as the web server user
runuser -u www-data -- php artisan schedule:work > /dev/null 2>&1 &

echo "✅ Laravel Ready. Starting Apache..."
exec apache2-foreground