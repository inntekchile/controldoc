# Dockerfile para el servicio web principal (app)

# Usar una imagen base de Ubuntu 22.04
FROM ubuntu:22.04

# Evitar diálogos interactivos durante la instalación
ARG DEBIAN_FRONTEND=noninteractive

# Instalar dependencias del sistema, Nginx, PHP, Supervisor y añadir PPAs
RUN apt-get update && apt-get install -y --no-install-recommends \
    software-properties-common curl git unzip vim gnupg supervisor && \
    add-apt-repository ppa:ondrej/php -y && \
    curl -fsSL https://deb.nodesource.com/setup_lts.x | bash - && \
    apt-get update

# Instalar PHP 8.2 y extensiones
RUN apt-get install -y --no-install-recommends \
    php8.2-fpm php8.2-cli php8.2-mysql php8.2-mbstring php8.2-xml \
    php8.2-zip php8.2-curl php8.2-gd php8.2-bcmath php8.2-intl php8.2-redis

# Instalar Node.js, pnpm y Composer
RUN apt-get install -y nodejs && \
    npm install -g pnpm && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copiar la configuración de Nginx y Supervisor
COPY nginx/default.conf /etc/nginx/sites-available/default
COPY scripts/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copiar el script de inicio
COPY scripts/start-container.sh /usr/local/bin/start-container.sh
RUN chmod +x /usr/local/bin/start-container.sh

# Establecer el directorio de trabajo
WORKDIR /var/www

# Copiar el código de la aplicación
COPY app /var/www

# Ejecutar build de assets y composer install
# Esto se hace aquí para que la imagen sea autónoma
RUN composer install --optimize-autoloader --no-dev && \
    pnpm install && \
    pnpm run build && \
    chown -R www-data:www-data /var/www

# Exponer el puerto de Nginx
EXPOSE 80

# Comando de inicio
CMD ["start-container.sh"]
