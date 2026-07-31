#!/bin/bash
set -e

# Nettoyage du cache Laravel
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# Migrations et données de test
php artisan migrate --force
php artisan db:seed --force

# Forcer les en-têtes CORS dans Apache (solution de contournement fiable)
echo 'Header set Access-Control-Allow-Origin "https://soft-bridge.netlify.app"' >> /etc/apache2/apache2.conf
echo 'Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"' >> /etc/apache2/apache2.conf
echo 'Header set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With"' >> /etc/apache2/apache2.conf
echo 'Header set Access-Control-Allow-Credentials "true"' >> /etc/apache2/apache2.conf

# Lancer Apache
apache2-foreground