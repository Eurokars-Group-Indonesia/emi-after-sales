# Build stage for Node.js assets
FROM node:20-alpine AS node-builder

WORKDIR /app

COPY package*.json ./

# Use npm install instead of npm ci to handle lock file sync issues
RUN npm install

COPY . .
RUN npm run build

# PHP-FPM stage
FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    mysql-client \
    postgresql-client \
    postgresql-dev \
    supervisor \
    redis \
    nodejs \
    npm \
    icu-dev \
    shadow

# Install PHP extensions (MYSQL + POSTGRESQL)
RUN docker-php-ext-install \
    pdo_mysql \
    pdo_pgsql \
    pgsql \
    zip \
    gd \
    pcntl \
    opcache \
    intl

# Install Redis extension via PECL
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

# SQL Server (Sync MSI / Customer CSI): Microsoft ODBC Driver 18 + pdo_sqlsrv
# URL paket dari https://learn.microsoft.com/sql/connect/odbc/linux-mac/installing-the-microsoft-odbc-driver-for-sql-server (tab Alpine)
ARG MSODBCSQL_URL_BASE=https://download.microsoft.com/download/ade174b7-8cea-4543-91a6-c33ae320c2f0
ARG MSODBCSQL_VERSION=18.7.1.1-1
RUN set -eux; \
    case "$(uname -m)" in x86_64) arch=amd64 ;; aarch64|arm64) arch=arm64 ;; *) echo "arsitektur tidak didukung"; exit 1 ;; esac; \
    apk add --no-cache unixodbc gnupg; \
    curl -fsSLO "${MSODBCSQL_URL_BASE}/msodbcsql18_${MSODBCSQL_VERSION}_${arch}.apk"; \
    curl -fsSLO "${MSODBCSQL_URL_BASE}/msodbcsql18_${MSODBCSQL_VERSION}_${arch}.sig"; \
    curl -fsSL https://packages.microsoft.com/keys/microsoft.asc | gpg --import -; \
    gpg --verify "msodbcsql18_${MSODBCSQL_VERSION}_${arch}.sig" "msodbcsql18_${MSODBCSQL_VERSION}_${arch}.apk"; \
    apk add --no-cache --allow-untrusted "msodbcsql18_${MSODBCSQL_VERSION}_${arch}.apk"; \
    rm -f msodbcsql18_*; \
    apk add --no-cache --virtual .sqlsrv-build-deps $PHPIZE_DEPS unixodbc-dev; \
    pecl install sqlsrv pdo_sqlsrv; \
    docker-php-ext-enable sqlsrv pdo_sqlsrv; \
    apk del .sqlsrv-build-deps

# Create user with UID 1000 if it doesn't exist
RUN if ! id -u 1000 > /dev/null 2>&1; then \
        addgroup -g 1000 appuser && \
        adduser -D -u 1000 -G appuser appuser; \
    fi

# Copy PHP-FPM pool configuration
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock ./

# Install PHP dependencies as root
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy application files
COPY . .

# Copy built assets from node-builder
COPY --from=node-builder /app/public/build ./public/build

# Generate optimized autoload files
RUN composer dump-autoload --optimize

# Create storage directories with proper structure
RUN mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/testing \
    storage/logs \
    storage/app/public \
    bootstrap/cache

# Set ownership and permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache \
    && find /var/www/html/storage -type d -exec chmod 775 {} \; \
    && find /var/www/html/storage -type f -exec chmod 664 {} \; \
    && find /var/www/html/bootstrap/cache -type d -exec chmod 775 {} \; \
    && find /var/www/html/bootstrap/cache -type f -exec chmod 664 {} \; \
    && touch /var/www/html/storage/logs/laravel.log \
    && touch /var/www/html/storage/logs/supervisord.log \
    && chown www-data:www-data /var/www/html/storage/logs/*.log \
    && chmod 664 /var/www/html/storage/logs/*.log

# Copy supervisor configuration
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Expose port 9000 for PHP-FPM
EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]