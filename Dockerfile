FROM php:8.2-apache

# Instalar dependencias y extensiones necesarias
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    libcurl4-openssl-dev \
    && docker-php-ext-install pdo_sqlite \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Habilitar el módulo de Apache para rutas amigables
RUN a2enmod rewrite

# Copiar todo el proyecto
COPY . /var/www/html/

# Dar permisos a la carpeta storage (donde vive la base de datos SQLite)
RUN mkdir -p /var/www/html/backend/storage/cache/assets \
    && chown -R www-data:www-data /var/www/html/backend/storage \
    && chmod -R 777 /var/www/html/backend/storage

# Render usa el puerto 80 por defecto para PHP-Apache, pero por seguridad:
EXPOSE 80
