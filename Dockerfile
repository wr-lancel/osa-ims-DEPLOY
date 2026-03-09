FROM php:8.4-apache

# 1. Configure Apache DocumentRoot to point to Laravel's public directory
ENV APACHE_DOCUMENT_ROOT /app/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 2. Enable Apache mod_rewrite for Laravel routing
RUN a2enmod rewrite

# 3. Install system dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    curl \
    libzip-dev \
    gnupg

# 4. Install Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# 5. Install PHP extensions (including OPcache for massive speed boost)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd pdo pdo_mysql zip opcache

# Configure OPcache for production speed
RUN echo "opcache.enable=1\nopcache.memory_consumption=256\nopcache.interned_strings_buffer=16\nopcache.max_accelerated_files=10000\nopcache.validate_timestamps=0" > /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini

# 6. Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

# 7. Install dependencies and build assets
RUN composer install --optimize-autoloader --no-interaction
RUN npm install && npm run build

# 8. Set permissions for Apache
RUN chown -R www-data:www-data /app/storage /app/bootstrap/cache

# Expose standard web port
EXPOSE 80

# Make the start script executable
RUN chmod +x start.sh

CMD ["sh", "start.sh"]