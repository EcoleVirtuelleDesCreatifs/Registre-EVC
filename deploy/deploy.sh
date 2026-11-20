#!/usr/bin/env bash
# Mise à jour du registre sur le serveur (à lancer depuis le dossier du projet)
set -euo pipefail

php artisan down --retry=30 || true

git pull --ff-only 2>/dev/null || echo "(pas de dépôt git : fichiers déjà copiés)"
composer install --no-dev --optimize-autoloader --no-interaction

php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache || true

php artisan up
echo "Registre EVC à jour."
