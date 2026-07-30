# Usamos PHP 8.2 con servidor web Apache integrado
FROM php:8.2-apache

# Instalamos extensiones necesarias para conectar PHP con MySQL / TiDB Cloud
RUN docker-php-ext-install pdo pdo_mysql mysqli

# Activamos el módulo rewrite de Apache
RUN a2enmod rewrite

# Copiamos todo el contenido de tu proyecto a la carpeta raíz de Apache
COPY . /var/www/html/

# Exponemos el puerto 80 para recibir tráfico web
EXPOSE 80