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

# Seed the database (only inserts if tables are empty, based on your seeders)
echo "Seeding database..."
php artisan db:seed --force

# Start the queue worker in the background
echo "Starting queue worker..."
php artisan queue:work &

# Start the Laravel application
echo "Starting server..."
php artisan serve --host=0.0.0.0 --port=8000
