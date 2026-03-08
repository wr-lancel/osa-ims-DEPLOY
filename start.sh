# Output to log what is happening
echo "Starting deployment script..."

# Railway doesn't copy .env automatically, so if one doesn't exist, we create it from example
if [ ! -f .env ]; then
    echo "Creating .env file..."
    cp .env.example .env
fi

# If APP_KEY is empty, we must generate it or the app will 500
if ! grep -q "^APP_KEY=base64:" .env; then
    echo "Generating APP_KEY..."
    php artisan key:generate --force
fi

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
