FROM php:8.2-apache

# Instalar dependencias de SQLite y cURL
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    libcurl4-openssl-dev \
    && docker-php-ext-install pdo_sqlite \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Activar mod_rewrite
RUN a2enmod rewrite

# Copiar todo el proyecto
COPY . /var/www/html/

# Configurar Apache para permitir acceso y permitir .htaccess en la raíz
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Dar permisos a la carpeta storage (777 para asegurar que SQLite escriba)
RUN mkdir -p /var/www/html/backend/storage/cache/assets \
    && chmod -R 777 /var/www/html/backend/storage

# Redirigir todo el tráfico de la raíz a la carpeta panel.php y backend
# Render necesita que el puerto 80 esté expuesto
EXPOSE 80
