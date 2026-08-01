#!/bin/bash
set -e

php artisan config:clear
php artisan route:clear
php artisan cache:clear

# Créer le lien symbolique pour les fichiers uploadés
php artisan storage:link --force

php artisan migrate --force
php artisan db:seed --force

apache2-foreground