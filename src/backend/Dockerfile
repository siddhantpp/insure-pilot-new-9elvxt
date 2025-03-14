# Stage 1: Build stage
FROM php:8.2-fpm-alpine as builder
WORKDIR /var/www/html
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN apk add --no-cache git zip unzip libzip-dev libpng-dev libjpeg-turbo-dev freetype-dev oniguruma-dev libxml2-dev
RUN docker-php-ext-install pdo_mysql zip gd mbstring xml pcntl
RUN apk add --no-cache $PHPIZE_DEPS && pecl install redis && docker-php-ext-enable redis
COPY composer.json composer.lock ./
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer install --no-dev --optimize-autoloader --no-scripts
COPY . .

# Stage 2: Final image
FROM php:8.2-fpm-alpine
WORKDIR /var/www/html
RUN apk add --no-cache mysql-client wkhtmltopdf
RUN docker-php-ext-install pdo_mysql zip gd mbstring xml pcntl
RUN apk add --no-cache $PHPIZE_DEPS && pecl install redis && docker-php-ext-enable redis
COPY --from=builder /var/www/html /var/www/html
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
ENV PHP_MEMORY_LIMIT=512M
ENV PHP_UPLOAD_MAX_FILESIZE=50M
ENV PHP_POST_MAX_SIZE=50M
ENV DOCUMENT_VIEWER_MAX_FILE_SIZE=50
ENV DOCUMENT_VIEWER_CACHE_TTL=3600
RUN echo 'memory_limit = ${PHP_MEMORY_LIMIT}' > /usr/local/etc/php/conf.d/memory-limit.ini
RUN echo 'upload_max_filesize = ${PHP_UPLOAD_MAX_FILESIZE}' > /usr/local/etc/php/conf.d/upload-limit.ini
RUN echo 'post_max_size = ${PHP_POST_MAX_SIZE}' > /usr/local/etc/php/conf.d/post-limit.ini
HEALTHCHECK --interval=30s --timeout=5s --retries=3 --start-period=30s \
    CMD php -r "if(file_get_contents('http://localhost:9000/ping') != 'pong') { exit(1); }"
EXPOSE 9000
CMD ["php-fpm"]