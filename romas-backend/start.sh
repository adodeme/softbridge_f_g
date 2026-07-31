#!/bin/bash
set -e

echo "Lancement des migrations..."
php artisan migrate --force

echo "Mise en cache de la configuration..."
php artisan config:cache
php artisan route:cache

echo "Démarrage d'Apache..."
sed -i "s/80/${PORT:-80}/g" /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf
apache2-foreground