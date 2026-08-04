FROM php:8.2-apache
# Force rebuild – step 5
WORKDIR /var/www/html

# Installation des dépendances système et extensions PHP
RUN apt-get update && apt-get install -y \
    libpng-dev libonig-dev libxml2-dev libzip-dev zip unzip curl libpq-dev \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip

# Activation des modules Apache nécessaires
RUN a2enmod rewrite
RUN a2enmod headers

# Copie de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copie du code source
COPY . .

# Installation des dépendances PHP
RUN php -d memory_limit=512M /usr/bin/composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

# Permissions
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

# Configuration Apache
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Script de démarrage
COPY start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

EXPOSE 80

CMD ["/usr/local/bin/start.sh"]