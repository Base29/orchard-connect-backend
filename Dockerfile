# Stage 1: Node builder for Vite/Filament assets
FROM node:20-alpine AS node-builder
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
RUN npm run build

# Stage 2: Production runner
FROM dunglas/frankenphp:1.3-php8.4

# Install PHP extensions required for Laravel, Filament, and Reverb using the pre-installed helper
RUN install-php-extensions pdo_pgsql pcntl zip bcmath gd intl

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy application files
COPY . .

# Copy built Vite assets from node-builder
COPY --from=node-builder /app/public/build ./public/build

# Install PHP dependencies (production only)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set up storage directories permissions
RUN mkdir -p storage/framework/{sessions,views,caches} bootstrap/cache && \
    chown -R www-data:www-data storage bootstrap/cache

# Configure FrankenPHP port and document root
ENV FRANKENPHP_CONFIG="local_certs"
ENV SERVER_NAME=":8000"

CMD ["frankenphp", "run-with-env"]
