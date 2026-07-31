#!/bin/bash
set -e

# Nettoyage du cache Laravel
php artisan config:clear
php artisan route:clear
php artisan cache:clear

# Migrations (ne s'exécute que si nécessaire)
php artisan migrate --force

# Seed (updateOrCreate évite les doublons)
php artisan db:seed --force

# Lancement d'Apache au premier plan
apache2-foreground