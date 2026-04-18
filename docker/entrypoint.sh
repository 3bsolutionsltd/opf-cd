#!/bin/sh
set -e

# Create all required Laravel directories if they don't exist
mkdir -p /var/www/storage/logs
mkdir -p /var/www/storage/framework/cache/data
mkdir -p /var/www/storage/framework/sessions
mkdir -p /var/www/storage/framework/views
mkdir -p /var/www/storage/app/public
mkdir -p /var/www/bootstrap/cache

# Fix permissions so www user can write
chown -R www:www /var/www/storage /var/www/bootstrap/cache
chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Clear cached config to avoid stale config cache issues
su -s /bin/sh www -c "php /var/www/artisan config:clear 2>/dev/null || true"
su -s /bin/sh www -c "php /var/www/artisan cache:clear 2>/dev/null || true"

# Start supervisord
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
