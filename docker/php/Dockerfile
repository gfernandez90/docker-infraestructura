FROM php:8.3-fpm-alpine

# Instalar dependencias del sistema y extensiones de PHP para Postgres y CAS
RUN apk add --no-cache \
    libpq-dev \
    libxml2-dev \
    curl-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql soap

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
