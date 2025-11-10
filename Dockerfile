# Gunakan image PHP 8.2 dengan Apache
FROM php:8.2-apache

# Salin semua file ke direktori web server
COPY . /var/www/html/

# Aktifkan mod_rewrite (dibutuhkan oleh .htaccess)
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html/

# Buka port 80
EXPOSE 80

RUN docker-php-ext-install mysqli pdo pdo_mysql
