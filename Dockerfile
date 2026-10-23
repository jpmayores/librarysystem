FROM php:8.1-apache

# Install PostgreSQL extension drivers for PHP
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql pgsql

COPY . /var/www/html/

# Siguraduhing umiiral ang uploads folder
RUN mkdir -p /var/www/html/uploads

# Bigyan ng tamang ownership at permission ang Apache/PHP (www-data)
RUN chown -R www-data:www-data /var/www/html/uploads
RUN chmod -R 775 /var/www/html/uploads

EXPOSE 80