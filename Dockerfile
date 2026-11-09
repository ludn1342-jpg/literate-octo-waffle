FROM php:8.2-apache

# Instalar extensiones necesarias (SQLite y cURL)
RUN apt-get update && apt-get install -y libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite sqlite3 curl

# Activar mod_rewrite de Apache
RUN a2enmod rewrite

# Copiar todo el proyecto al servidor web
COPY . /var/www/html/

# Dar permisos a la carpeta storage para que SQLite pueda escribir
RUN mkdir -p /var/www/html/backend/storage/cache/assets \
    && chown -R www-data:www-data /var/www/html/backend/storage \
    && chmod -R 775 /var/www/html/backend/storage

# Exponer el puerto (Render lo configura automáticamente)
EXPOSE 80
