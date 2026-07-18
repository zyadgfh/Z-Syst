FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    libicu-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    cron \
    zip \
    curl \
    && docker-php-ext-configure zip \
    && docker-php-ext-install pdo pdo_pgsql zip intl mbstring bcmath pcntl opcache

# Install composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

RUN usermod -u 1000 www-data || true

EXPOSE 9000 8000

CMD ["php-fpm"]
