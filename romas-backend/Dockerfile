# Utiliser une image PHP officielle avec Apache
FROM php:8.2-apache

# Installer les extensions PHP nécessaires à Laravel
RUN docker-php-ext-install pdo pdo_mysql

# Activer le module rewrite d'Apache (nécessaire pour Laravel)
RUN a2enmod rewrite

# Définir le répertoire de travail
WORKDIR /var/www/html

# Copier le code du projet dans le conteneur
COPY . .

# Installer Composer et les dépendances
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# Définir les permissions sur storage et bootstrap/cache
RUN chown -R www-data:www-data storage bootstrap/cache
RUN chmod -R 775 storage bootstrap/cache

# Copier le fichier de configuration Apache pour pointer vers /public
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Exposer le port 80 (Render mappe automatiquement le port 10000)
EXPOSE 80

# Lancer Apache au premier plan
CMD ["apache2-foreground"]