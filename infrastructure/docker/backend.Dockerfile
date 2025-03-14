# First stage: Composer dependencies
FROM composer:2 as composer

WORKDIR /app

# Copy only the files needed for composer install to leverage Docker layer caching
COPY src/backend/composer.json composer.lock ./

# Install dependencies without dev dependencies for production
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Second stage: Final image
FROM php:8.2-fpm-alpine

WORKDIR /var/www

# Install system dependencies
RUN apk add --no-cache libpng-dev libjpeg-turbo-dev freetype-dev zip libzip-dev oniguruma-dev

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd zip opcache

# Install wkhtmltopdf for PDF generation (needed for barryvdh/laravel-snappy)
RUN apk add --no-cache wkhtmltopdf

# Copy composer dependencies from composer stage
COPY --from=composer /app/vendor /var/www/vendor

# Copy application code
COPY src/backend /var/www

# Optimize composer autoloader
RUN composer dump-autoload --optimize

# Optimize Laravel for production
RUN php artisan config:cache
RUN php artisan route:cache
RUN php artisan view:cache

# Set correct permissions
RUN chown -R www-data:www-data /var/www/storage
RUN chmod -R 775 /var/www/storage

# Copy custom PHP configuration
COPY infrastructure/docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

# Copy PHP-FPM configuration
COPY infrastructure/docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Copy health check script
COPY infrastructure/docker/php/health-check.sh /usr/local/bin/health-check.sh
RUN chmod +x /usr/local/bin/health-check.sh

# Set PHP OpCache environment variables for performance
ENV PHP_OPCACHE_VALIDATE_TIMESTAMPS=0
ENV PHP_OPCACHE_MAX_ACCELERATED_FILES=10000
ENV PHP_OPCACHE_MEMORY_CONSUMPTION=128
ENV PHP_OPCACHE_MAX_WASTED_PERCENTAGE=10

# Set Laravel environment variables
ENV APP_ENV=production
ENV APP_DEBUG=false

# Expose PHP-FPM port
EXPOSE 9000

# Configure health check
HEALTHCHECK --interval=30s --timeout=5s --retries=3 --start-period=10s CMD /usr/local/bin/health-check.sh

# Start PHP-FPM
CMD ["php-fpm"]