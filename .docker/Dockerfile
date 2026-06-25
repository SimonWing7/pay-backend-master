FROM php:8.4-cli-bookworm

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    supervisor \
    libbrotli-dev \
    nano \
    libwebp-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    default-mysql-client

RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Configure GD
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip sockets

RUN pecl install swoole && docker-php-ext-enable swoole
# Install PHP extensions (including PhpRedis)
RUN pecl install redis \
    && docker-php-ext-enable redis
RUN echo "memory_limit = 1G" > /usr/local/etc/php/conf.d/memory-limit.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www



COPY composer.json /var/www/composer.json
COPY composer.lock /var/www/composer.lock
COPY . /var/www

RUN composer install
# Copy existing application directory
COPY .docker/env.example /var/www/.env

# Copy supervisor configuration
COPY .docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Start Supervisor
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

