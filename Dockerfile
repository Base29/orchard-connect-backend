FROM frankenphp:1.3-php8.4-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    postgresql-dev \
    libzip-dev \
    icu-dev \
    bash

# Install PHP extensions required for Laravel, Filament, and Reverb
RUN docker-php-ext-install \
    pdo_pgsql \
    pcntl \
    zip \
    bcmath \
    gd \
    intl

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy application files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Install Node & Build Vite/Filament assets
RUN apk add --no-cache nodejs npm && \
    npm install && \
    npm run build && \
    apk del nodejs npm

# Set up storage directories permissions
RUN mkdir -p storage/framework/{sessions,views,caches} bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap/cache

# Configure FrankenPHP port and document root
ENV FRANKENPHP_CONFIG="local_certs"
ENV SERVER_NAME=":8000"

CMD ["frankenphp", "run-with-env"]
