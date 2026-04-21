FROM php:8.2-apache

# Install PDO MySQL extension required for our application
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache rewrite module
RUN a2enmod rewrite
