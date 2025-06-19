# Stage 1: Build dependencies and assets
FROM node:18-alpine AS node_builder

WORKDIR /app

# Copy package files
COPY package*.json ./

# Install Node dependencies (including dev dependencies for build)
RUN npm ci

# Copy source files needed for build
COPY resources/ resources/
COPY public/ public/
COPY vite.config.js tailwind.config.js postcss.config.js jsconfig.json ./

# Build frontend assets
RUN npm run build

# Stage 2: PHP Build
FROM php:8.2-fpm-alpine AS php_builder

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    oniguruma-dev \
    icu-dev \
    libxml2-dev

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    pdo_sqlite \
    mbstring \
    zip \
    exif \
    pcntl \
    bcmath \
    gd \
    intl \
    xml \
    soap

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Stage 3: Production
FROM php:8.2-fpm-alpine

# Install runtime dependencies only
RUN apk add --no-cache \
    libpng \
    libjpeg-turbo \
    freetype \
    libzip \
    oniguruma \
    icu \
    libxml2 \
    nginx \
    supervisor

# Install PHP extensions (same as build stage)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    pdo_sqlite \
    mbstring \
    zip \
    exif \
    pcntl \
    bcmath \
    gd \
    intl \
    xml \
    soap

# Create app user
RUN addgroup -g 1000 -S www && \
    adduser -u 1000 -S www -G www

WORKDIR /var/www/html

# Copy built assets from node_builder
COPY --from=node_builder /app/public/build ./public/build

# Copy vendor dependencies from php_builder
COPY --from=php_builder /app/vendor ./vendor

# Copy application code
COPY --chown=www:www . .

# Copy specific directories that might be needed
COPY --chown=www:www artisan ./
COPY --chown=www:www bootstrap/ ./bootstrap/
COPY --chown=www:www config/ ./config/
COPY --chown=www:www database/ ./database/
COPY --chown=www:www routes/ ./routes/
COPY --chown=www:www app/ ./app/
COPY --chown=www:www resources/ ./resources/
COPY --chown=www:www storage/ ./storage/
COPY --chown=www:www public/ ./public/

# Set permissions
RUN chown -R www:www /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache

# Configure PHP-FPM
RUN sed -i 's/user = www-data/user = www/g' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/group = www-data/group = www/g' /usr/local/etc/php-fpm.d/www.conf \
    && sed -i 's/listen = 127.0.0.1:9000/listen = 9000/g' /usr/local/etc/php-fpm.d/www.conf

# Configure Nginx
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Create necessary directories
RUN mkdir -p /var/log/supervisor \
    && mkdir -p /run/nginx

EXPOSE 80

# Use supervisor to run multiple services
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]