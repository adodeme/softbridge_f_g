#!/bin/bash
set -e

# Nettoyage du cache Laravel
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# Migrations
php artisan migrate --force

# Seed (on autorise l'échec pour ne pas bloquer le déploiement)
php artisan db:seed --force || echo "⚠️ Le seed a rencontré une erreur, mais le serveur continue."

# Forcer les en-têtes CORS dans Apache (solution de contournement)
echo 'Header set Access-Control-Allow-Origin "https://soft-bridge.netlify.app"' >> /etc/apache2/apache2.conf
echo 'Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"' >> /etc/apache2/apache2.conf
echo 'Header set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With"' >> /etc/apache2/apache2.conf
echo 'Header set Access-Control-Allow-Credentials "true"' >> /etc/apache2/apache2.conf

# Lancer Apache
apache2-foreground