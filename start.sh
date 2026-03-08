#!/bin/bash
# Ensure storage directories exist
mkdir -p storage/framework/{sessions,views,cache}
mkdir -p storage/logs
chmod -R 775 storage bootstrap/cache

# Cache configuration and optimize
echo "Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Start the queue worker in the background
echo "Starting queue worker..."
php artisan queue:work &

# Start the Laravel application
echo "Starting server..."
php artisan serve --host=0.0.0.0 --port=8000
