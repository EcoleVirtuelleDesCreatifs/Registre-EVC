#!/usr/bin/env bash
# Sauvegarde quotidienne : base de données + documents PDF.
# Complément hors serveur : la sauvegarde applicative (php artisan evc:backup) tourne déjà chaque nuit via le planificateur.
# Ce script ajoute un dump SQL natif, à copier idéalement vers un autre stockage.
# Exemple de tâche cron :  30 2 * * * /var/www/evc-registre/deploy/sauvegarde.sh
set -euo pipefail
cd "$(dirname "$0")/.."
DEST="${BACKUP_DIR:-$HOME/sauvegardes-evc}"
STAMP=$(date +%Y-%m-%d_%H%M)
mkdir -p "$DEST"

set -a; source .env; set +a
if [ "$DB_CONNECTION" = "mysql" ]; then
  mysqldump --single-transaction -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" | gzip > "$DEST/base-$STAMP.sql.gz"
fi
tar -czf "$DEST/documents-$STAMP.tar.gz" storage/app/private/documents

# Conserve les 30 dernières sauvegardes
ls -1t "$DEST"/base-*.sql.gz 2>/dev/null | tail -n +31 | xargs -r rm
ls -1t "$DEST"/documents-*.tar.gz 2>/dev/null | tail -n +31 | xargs -r rm
echo "Sauvegarde terminée : $DEST"
