#!/bin/sh
set -eu
export KC_DB_PASSWORD="$(cat /run/secrets/db_password)"
if [ -s /run/secrets/bootstrap_secret ]; then
    export KC_BOOTSTRAP_ADMIN_CLIENT_ID=identity-bootstrap
    export KC_BOOTSTRAP_ADMIN_CLIENT_SECRET="$(cat /run/secrets/bootstrap_secret)"
fi
exec /opt/keycloak/bin/kc.sh "$@"
