FROM php:8.2-apache

# Install required PHP extensions for this app
RUN docker-php-ext-install mysqli curl

# Copy project source into Apache document root
COPY . /var/www/html

# Ensure Apache serves from the project root
ENV APACHE_DOCUMENT_ROOT=/var/www/html

EXPOSE 80

