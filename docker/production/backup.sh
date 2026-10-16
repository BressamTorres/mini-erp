#!/bin/sh
set -eu

umask 077

retention_days="${BACKUP_RETENTION_DAYS:-14}"
timestamp="$(date -u +%Y%m%dT%H%M%SZ)"
backup_file="/backups/${MYSQL_DATABASE}_${timestamp}.sql"
temporary_file="${backup_file}.partial"

trap 'rm -f "$temporary_file"' EXIT

export MYSQL_PWD="${MYSQL_PASSWORD}"

mysqldump \
    --host=mysql \
    --user="${MYSQL_USER}" \
    --single-transaction \
    --quick \
    --no-tablespaces \
    --routines \
    --triggers \
    --set-gtid-purged=OFF \
    "${MYSQL_DATABASE}" > "$temporary_file"

mv "$temporary_file" "$backup_file"
trap - EXIT

find /backups -type f -name '*.sql' -mtime "+${retention_days}" -delete

printf 'Backup criado: %s\n' "$backup_file"
