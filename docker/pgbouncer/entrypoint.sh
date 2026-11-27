#!/bin/sh
set -eu
: "${DB_OWNER_PASSWORD:?DB_OWNER_PASSWORD is required}"
: "${DB_APP_PASSWORD:?DB_APP_PASSWORD is required}"
umask 077
# Plaintext entries let PgBouncer perform SCRAM both toward clients and toward PostgreSQL.
{
  printf '"stockpulse_owner" "%s"\n' "$DB_OWNER_PASSWORD"
  printf '"stockpulse_app" "%s"\n' "$DB_APP_PASSWORD"
} > /etc/pgbouncer/userlist.txt
rm -f /var/run/pgbouncer/pgbouncer.pid
exec pgbouncer /etc/pgbouncer/pgbouncer.ini
