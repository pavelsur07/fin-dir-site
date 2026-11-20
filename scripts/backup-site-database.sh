#!/bin/sh
# Fail closed: deployment must not migrate unless pg_dump and archive validation succeed.
set -eu
umask 077

: "${VF_BACKUP_DIR:?VF_BACKUP_DIR is required}"
backup_project=${VF_BACKUP_PROJECT:-vashfindir}
backup_release=${VF_BACKUP_RELEASE:-release}
case "$backup_release" in *[!a-zA-Z0-9_-]*|'') echo 'Invalid backup release identifier' >&2; exit 1;; esac

backup_ids=$(docker ps -q --filter "label=com.docker.compose.project=$backup_project" --filter 'label=com.docker.compose.service=site-postgres')
set -- $backup_ids
if [ "$#" -ne 1 ]; then
    echo 'Expected exactly one running PostgreSQL container for the selected Compose project' >&2
    exit 1
fi
backup_postgres=$1

mkdir -p -- "$VF_BACKUP_DIR"
chmod 700 -- "$VF_BACKUP_DIR"
backup_directory=$(cd "$VF_BACKUP_DIR" && pwd -P)
backup_temporary=$(mktemp "$backup_directory/.database-backup.XXXXXX")
trap 'rm -f -- "$backup_temporary"' EXIT HUP INT TERM

# Credentials stay inside the PostgreSQL container and never enter logs or host arguments.
docker exec "$backup_postgres" sh -ec 'pg_dump --format=custom --no-owner --no-acl --username="$POSTGRES_USER" --dbname="$POSTGRES_DB"' > "$backup_temporary"
test -s "$backup_temporary"
docker exec -i "$backup_postgres" pg_restore --list < "$backup_temporary" > /dev/null

backup_name="site-$(date -u +%Y%m%dT%H%M%SZ)-$backup_release-$$.dump"
test ! -e "$backup_directory/$backup_name"
mv -- "$backup_temporary" "$backup_directory/$backup_name"
chmod 600 -- "$backup_directory/$backup_name"
(cd "$backup_directory" && sha256sum "$backup_name" > "$backup_name.sha256" && sha256sum --check "$backup_name.sha256")
printf 'Database backup verified: %s/%s\n' "$backup_directory" "$backup_name"
