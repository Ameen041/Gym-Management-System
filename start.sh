#!/bin/bash
set -e

echo "✅ Starting Ameen Gym (Laravel) ..."

# مهم: خليه يشوف متغيرات Render الجديدة
php artisan config:clear || true
php artisan cache:clear || true

# (اختياري) اعمل كاش بعدين
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# ✅ اعمل migrations تلقائياً
php artisan migrate --force || true

# شغل Apache
exec apache2-foreground