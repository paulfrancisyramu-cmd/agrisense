FROM php:8.2-apache

# Install PostgreSQL drivers for PDO (pdo_pgsql + pgsql)
RUN apt-get update \
    && apt-get install -y libpq-dev curl unzip \
    && docker-php-ext-install pdo_pgsql pgsql \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy app source into Apache document root
COPY . /var/www/html

# Install PHP dependencies (including PHPMailer)
RUN composer install --no-dev --optimize-autoloader --working-dir=/var/www/html

# Ensure Apache serves our app
ENV APACHE_DOCUMENT_ROOT=/var/www/html

EXPOSE 80
