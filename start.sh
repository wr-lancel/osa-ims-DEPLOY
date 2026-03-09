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

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Seed the database
echo "Seeding database..."
php artisan db:seed --force

# Start the queue worker in the background and auto-restart if it crashes
echo "Starting queue worker..."
(while true; do php artisan queue:work; sleep 1; done) &

# Configure Apache port based on Railway's injected $PORT variable
# Default to 80 if PORT is not set (e.g. local matching)
PORT="${PORT:-80}"
echo "Configuring Apache to listen on port $PORT..."
sed -i "s/Listen 80/Listen ${PORT}/g" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:80>/<VirtualHost \*:${PORT}>/g" /etc/apache2/sites-available/000-default.conf

# CRITICAL FIX for AH00534 Apache MPM conflict
# Forcefully ensure ONLY mpm_prefork is loaded right before Apache starts
echo "Enforcing mpm_prefork module..."
rm -f /etc/apache2/mods-enabled/mpm_*.load
rm -f /etc/apache2/mods-enabled/mpm_*.conf
ln -sf /etc/apache2/mods-available/mpm_prefork.load /etc/apache2/mods-enabled/mpm_prefork.load
ln -sf /etc/apache2/mods-available/mpm_prefork.conf /etc/apache2/mods-enabled/mpm_prefork.conf

# Start the Apache server in the foreground
echo "Starting Apache server..."
apache2-foreground
