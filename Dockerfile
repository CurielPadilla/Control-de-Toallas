# Imagen con PHP + Apache
FROM php:8.2-apache

# Instalar extensión para conectarse a MySQL
RUN docker-php-ext-install pdo pdo_mysql

# Carpeta de trabajo dentro del contenedor
WORKDIR /var/www/html
