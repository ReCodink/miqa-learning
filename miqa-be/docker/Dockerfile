FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    oniguruma-dev

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# DON'T COPY src/ yet - it doesn't exist
# We'll mount it as a volume instead

# Create Laravel directories structure for when Laravel is installed
RUN mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views \
    && mkdir -p bootstrap/cache

# Set proper permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 /var/www

USER www-data

EXPOSE 9000
CMD ["php-fpm"]