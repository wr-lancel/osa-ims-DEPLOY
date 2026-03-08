#!/bin/bash

# Run migrations
echo "Running migrations..."
php artisan migrate --force

# Start the queue worker in the background
echo "Starting queue worker..."
php artisan queue:work &

# Start the Laravel application
echo "Starting server..."
php artisan serve --host=0.0.0.0 --port=8000
