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

# Fresh migration + seed (drops all tables and rebuilds from scratch)
# IMPORTANT: Change this back to "php artisan migrate --force" after the first successful deploy!
echo "Running fresh migrations and seeding..."
php artisan migrate:fresh --seed --force

# Start the queue worker in the background
echo "Starting queue worker..."
php artisan queue:work &

# Start the Laravel application
echo "Starting server..."
php artisan serve --host=0.0.0.0 --port=8000
