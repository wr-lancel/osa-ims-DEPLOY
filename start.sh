#!/bin/bash

echo "Starting deployment script..."

# DO NOT copy .env.example — Railway injects env vars directly into the container.
# Creating a .env file would OVERRIDE Railway's variables with wrong defaults.

# Ensure storage directories exist
mkdir -p storage/framework/{sessions,views,cache}
mkdir -p storage/logs
chmod -R 775 storage bootstrap/cache

# Create the storage symlink (needed for serving uploaded files)
echo "Creating storage link..."
php artisan storage:link --force 2>/dev/null || true

# Cache configuration and optimize
echo "Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# In Railway, keep the WEB container fast-to-ready: don't run long tasks unless enabled.
# Control via env vars:
# - RUN_MIGRATIONS=1  -> run `php artisan migrate --force`
# - RUN_SEED=1        -> run `php artisan db:seed --force`
if [[ "${RUN_MIGRATIONS:-0}" == "1" ]]; then
  echo "Running migrations..."
  php artisan migrate --force
else
  echo "Skipping migrations (set RUN_MIGRATIONS=1 to enable)."
fi

if [[ "${RUN_SEED:-0}" == "1" ]]; then
  echo "Seeding database..."
  php artisan db:seed --force
else
  echo "Skipping seeding (set RUN_SEED=1 to enable)."
fi

# Configure Apache port based on Railway's injected $PORT variable
# Default to 80 if PORT is not set (e.g. local matching)
PORT="${PORT:-80}"
echo "Configuring Apache to listen on port $PORT..."
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:${PORT}>/g" /etc/apache2/sites-available/000-default.conf
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/apache2.conf || true

# Suppress AH00558: Apache cannot determine FQDN in containers (Railway)
# Use a dedicated conf file to avoid appending duplicates on every boot.
if [[ ! -f /etc/apache2/conf-available/servername.conf ]]; then
  echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf
fi
a2enconf servername >/dev/null 2>&1 || true

# CRITICAL FIX for AH00534 Apache MPM conflict
# Forcefully ensure ONLY mpm_prefork is loaded right before Apache starts
echo "Enforcing mpm_prefork module..."
rm -f /etc/apache2/mods-enabled/mpm_*.load
rm -f /etc/apache2/mods-enabled/mpm_*.conf
ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load
ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf

# Start the Laravel queue worker in the background to handle emails
echo "Starting Laravel Queue Worker..."
php artisan queue:work --database=mysql --sleep=3 --tries=3 &

# Start the Apache server in the foreground
echo "Starting Apache server..."
apache2ctl -t
exec apache2-foreground
