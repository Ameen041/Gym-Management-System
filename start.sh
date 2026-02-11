#!/bin/bash
set -e

echo "🚀 Starting Ameen Gym (Laravel)..."

# Debug (safe)
echo "DB_CONNECTION=${DB_CONNECTION}"
echo "DB_HOST=${DB_HOST}"
echo "DB_PORT=${DB_PORT}"
echo "DB_DATABASE=${DB_DATABASE}"
echo "DB_USERNAME=${DB_USERNAME}"

APP_DIR="/var/www/html"
STORAGE="$APP_DIR/storage"
CACHE="$APP_DIR/bootstrap/cache"

# Ensure dirs exist
mkdir -p "$STORAGE" "$CACHE"
mkdir -p "$STORAGE/framework/cache" "$STORAGE/framework/sessions" "$STORAGE/framework/views"

# Fix permissions
chown -R www-data:www-data "$STORAGE" "$CACHE" || true
chmod -R 775 "$STORAGE" "$CACHE" || true

# Hard reset cached files (avoid stale env/config)
rm -f "$CACHE/config.php" "$CACHE/routes-v7.php" "$CACHE/services.php" "$CACHE/packages.php" || true

# Clear all Laravel caches (important for Render env vars + APP_URL)
php artisan optimize:clear || true

# Run migrations (do not fail the whole boot if DB is temporarily unavailable)
php artisan migrate --force || true

# Rebuild caches (optional - safe)
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

echo "✅ Laravel Ready. Starting Apache..."
exec apache2-foreground