#!/bin/bash
set -e

# Nettoyage OBLIGATOIRE du cache pour prendre en compte
# les modifications de config/cors.php et routes/api.php
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# Migrations et données de test
php artisan migrate --force
php artisan db:seed --force

# Lancer Apache
apache2-foreground