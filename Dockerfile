FROM php:8.2-apache

# Install MySQL drivers (mysqli + PDO MySQL)
RUN apt-get update \
    && docker-php-ext-install mysqli pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# Copy app source into Apache document root
COPY . /var/www/html

# Ensure Apache serves our app
ENV APACHE_DOCUMENT_ROOT=/var/www/html

EXPOSE 80

