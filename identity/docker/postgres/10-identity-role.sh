#!/bin/sh
set -eu
# Only on first initialization. Password stays server-side, not in argv/output.
psql --set ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$POSTGRES_DB" <<'SQL'
SELECT format('CREATE ROLE identity LOGIN NOSUPERUSER NOCREATEDB NOCREATEROLE NOREPLICATION PASSWORD %L', trim(pg_read_file('/run/secrets/db_password')))
\gexec
ALTER DATABASE identity OWNER TO identity;
REVOKE ALL ON SCHEMA public FROM PUBLIC;
GRANT ALL ON SCHEMA public TO identity;
SQL
