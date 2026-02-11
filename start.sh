#!/usr/bin/env bash
set -e

echo "✅ Starting Ameen Gym (Laravel) ..."

cd /var/www/html

# Ensure correct permissions (Render sometimes changes ownership)
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R 775 storage bootstrap/cache || true

# Generate key if missing (only if APP_KEY empty)
if [ -z "$APP_KEY" ]; then
  echo "⚠️ APP_KEY is empty. Generating..."
  php artisan key:generate --force
fi

# Cache config/routes/views (safe in prod)
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Run migrations (and seed if you want demo accounts)
php artisan migrate --force

# OPTIONAL: seed demo users (فعّلها إذا بدك حسابات الديمو تنضاف تلقائياً)
# php artisan db:seed --force

echo "🚀 Done. Starting Apache..."
exec apache2-foreground