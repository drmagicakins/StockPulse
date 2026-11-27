#!/bin/bash
# Runs once, on first initialisation of an empty data volume (see: make reset-db).
# Creates the two application roles and the test database, and sets up least-privilege grants.
#   stockpulse_owner : owns the databases/schema; runs migrations (not a superuser)
#   stockpulse_app   : runtime role; no superuser, no BYPASSRLS, owns nothing, cannot create objects
set -euo pipefail
: "${DB_OWNER_PASSWORD:?DB_OWNER_PASSWORD is required}"
: "${DB_APP_PASSWORD:?DB_APP_PASSWORD is required}"

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname postgres \
     -v owner_pw="$DB_OWNER_PASSWORD" -v app_pw="$DB_APP_PASSWORD" <<'SQL'
CREATE ROLE stockpulse_owner LOGIN PASSWORD :'owner_pw'
  NOSUPERUSER NOCREATEDB NOCREATEROLE NOREPLICATION NOBYPASSRLS;
CREATE ROLE stockpulse_app LOGIN PASSWORD :'app_pw'
  NOSUPERUSER NOCREATEDB NOCREATEROLE NOREPLICATION NOBYPASSRLS;
SQL

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname postgres \
     -c "CREATE DATABASE stockpulse_test OWNER stockpulse_owner"

for db in "$POSTGRES_DB" stockpulse_test; do
  psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$db" -v dbname="$db" <<'SQL'
ALTER DATABASE :"dbname" OWNER TO stockpulse_owner;
REVOKE ALL ON DATABASE :"dbname" FROM PUBLIC;
GRANT CONNECT ON DATABASE :"dbname" TO stockpulse_owner, stockpulse_app;

CREATE EXTENSION IF NOT EXISTS pg_trgm;
CREATE EXTENSION IF NOT EXISTS citext;
CREATE EXTENSION IF NOT EXISTS btree_gin;

ALTER SCHEMA public OWNER TO stockpulse_owner;
REVOKE ALL ON SCHEMA public FROM PUBLIC;
GRANT USAGE ON SCHEMA public TO stockpulse_app;

-- Tables/sequences created by the owner role become usable by the runtime role.
-- Ledger and audit tables get narrower grants in their own migrations (WP 1D onward).
ALTER DEFAULT PRIVILEGES FOR ROLE stockpulse_owner IN SCHEMA public
  GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO stockpulse_app;
ALTER DEFAULT PRIVILEGES FOR ROLE stockpulse_owner IN SCHEMA public
  GRANT USAGE, SELECT ON SEQUENCES TO stockpulse_app;
SQL
done
