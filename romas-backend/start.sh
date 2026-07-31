#!/bin/bash

# Nettoyage du cache Laravel (obligatoire pour prendre en compte CORS et routes)
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# Migrations (ne s'exécute qu'une fois si la base est déjà à jour)
php artisan migrate --force

# Seed (ne fait rien si les données existent déjà grâce à updateOrCreate)
php artisan db:seed --force || echo "⚠️ Le seed a rencontré une erreur, mais le serveur continue."

# Forcer les en-têtes CORS dans Apache (solution de contournement fiable)
echo 'Header set Access-Control-Allow-Origin "https://soft-bridge.netlify.app"' >> /etc/apache2/apache2.conf
echo 'Header set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"' >> /etc/apache2/apache2.conf
echo 'Header set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With"' >> /etc/apache2/apache2.conf
echo 'Header set Access-Control-Allow-Credentials "true"' >> /etc/apache2/apache2.conf

# Lancer Apache en premier plan
apache2-foreground