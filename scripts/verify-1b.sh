#!/usr/bin/env bash
# WP 1B acceptance checks. Prints PASS / FAIL / SKIP per check with the real result.
# Side effects: starts the dev stack, builds the app image, creates and drops ONE probe table in stockpulse_test.
set -uo pipefail
cd "$(dirname "$0")/.."
[ -f .env ] || { echo "No .env found. Run: make env   (then review the values)"; exit 2; }
set -a; . ./.env; set +a

DC="docker compose"
PASS=0; FAIL=0; SKIP=0
pass() { echo "PASS  $1"; PASS=$((PASS+1)); }
fail() { echo "FAIL  $1"; [ -n "${2:-}" ] && printf '      %s\n' "$2"; FAIL=$((FAIL+1)); }
skip() { echo "SKIP  $1 ($2)"; SKIP=$((SKIP+1)); }

psql_exec() { # usage: psql_exec <password> <host> <user> <db> [psql args...]
  local pw=$1 host=$2 user=$3 db=$4; shift 4
  $DC exec -T -e PGPASSWORD="$pw" postgres psql -h "$host" -U "$user" -d "$db" -X "$@"
}

echo "== 1. docker compose config validates =="
if out=$($DC --profile pool --profile app config -q 2>&1); then pass "1. compose config valid"; else fail "1. compose config" "$out"; fi

echo; echo "== Starting stack (postgres, pgbouncer, redis, rustfs, mailpit, storage-init) =="
$DC --profile pool up -d --build postgres pgbouncer redis rustfs mailpit storage-init 2>&1

wait_healthy() { # returns 0 when healthy within ~2 minutes
  local svc=$1 id st i
  for i in $(seq 1 60); do
    id=$($DC --profile pool ps -q "$svc" 2>/dev/null)
    if [ -n "$id" ]; then
      st=$(docker inspect -f '{{if .State.Health}}{{.State.Health.Status}}{{else}}{{.State.Status}}{{end}}' "$id" 2>/dev/null)
      [ "$st" = "healthy" ] && return 0
    fi
    sleep 2
  done
  return 1
}

echo; echo "== 2. Infrastructure services healthy =="
unhealthy=""
for s in postgres redis rustfs pgbouncer; do wait_healthy "$s" || unhealthy="$unhealthy $s"; done
mp=$($DC ps -q mailpit); mps=$(docker inspect -f '{{.State.Status}}' "$mp" 2>/dev/null)
[ "$mps" = "running" ] || unhealthy="$unhealthy mailpit"
if [ -z "$unhealthy" ]; then pass "2. postgres, redis, rustfs, pgbouncer healthy; mailpit running"; else fail "2. not healthy:$unhealthy" "see: docker compose --profile pool ps ; docker compose logs <service>"; fi

echo; echo "== 3. PostgreSQL direct, wrong-password rejection, PgBouncer and SET LOCAL =="
o=$(psql_exec "$DB_APP_PASSWORD" postgres stockpulse_app "$POSTGRES_DB" -tAq -c "select current_user" 2>&1)
[ "$o" = "stockpulse_app" ] && pass "3a. direct login as stockpulse_app (password auth)" || fail "3a. direct login" "$o"
o=$(psql_exec "wrong-password" postgres stockpulse_app "$POSTGRES_DB" -tAq -c "select 1" 2>&1)
echo "$o" | grep -qi "password authentication failed" && pass "3b. wrong password is rejected" || fail "3b. wrong password was not rejected" "$o"
if [ -n "$($DC --profile pool ps -q pgbouncer)" ]; then
  o=$(psql_exec "$DB_APP_PASSWORD" pgbouncer stockpulse_app "$POSTGRES_DB" -p 6432 -tAq <<'SQL' 2>&1
BEGIN;
SET LOCAL app.current_business_id = '42';
SELECT 'in_tx=' || current_setting('app.current_business_id', true);
COMMIT;
SELECT 'after_tx=' || coalesce(current_setting('app.current_business_id', true), '');
SQL
)
  if echo "$o" | grep -qx "in_tx=42" && echo "$o" | grep -qx "after_tx="; then
    pass "3c. via PgBouncer: SET LOCAL visible in transaction, gone after COMMIT"
  else fail "3c. PgBouncer / SET LOCAL" "$o"; fi
else skip "3c. PgBouncer SET LOCAL test" "pgbouncer not running"; fi

echo; echo "== 4. Runtime role cannot bypass RLS or create roles/objects =="
SU="$POSTGRES_USER"
$DC exec -T postgres psql -U "$SU" -d stockpulse_test -X -v ON_ERROR_STOP=1 -q <<'SQL' >/dev/null 2>&1
DROP TABLE IF EXISTS rls_probe;
CREATE TABLE rls_probe (id int, business_id int);
INSERT INTO rls_probe VALUES (1, 1), (2, 2);
ALTER TABLE rls_probe ENABLE ROW LEVEL SECURITY;
CREATE POLICY probe_isolation ON rls_probe
  USING (business_id = nullif(current_setting('app.current_business_id', true), '')::int);
GRANT SELECT ON rls_probe TO stockpulse_app;
SQL
o=$(psql_exec "$DB_APP_PASSWORD" postgres stockpulse_app stockpulse_test -tAq <<'SQL' 2>&1
BEGIN;
SET LOCAL app.current_business_id = '1';
SELECT 'tenant1_rows=' || count(*) FROM rls_probe;
COMMIT;
SELECT 'no_context_rows=' || count(*) FROM rls_probe;
SELECT 'attrs super=' || rolsuper || ' bypassrls=' || rolbypassrls || ' createrole=' || rolcreaterole || ' createdb=' || rolcreatedb
  FROM pg_roles WHERE rolname = current_user;
SQL
)
echo "$o" | grep -qx "tenant1_rows=1" && echo "$o" | grep -qx "no_context_rows=0" \
  && pass "4a. RLS enforced for stockpulse_app (1 row with tenant context, 0 without)" || fail "4a. RLS probe" "$o"
echo "$o" | grep -qx "attrs super=false bypassrls=false createrole=false createdb=false" \
  && pass "4b. stockpulse_app has no superuser/BYPASSRLS/CREATEROLE/CREATEDB" || fail "4b. role attributes" "$o"
o=$(psql_exec "$DB_APP_PASSWORD" postgres stockpulse_app stockpulse_test -v ON_ERROR_STOP=1 -c "CREATE ROLE probe_should_fail" 2>&1)
echo "$o" | grep -qi "permission denied" && pass "4c. CREATE ROLE denied for stockpulse_app" || fail "4c. CREATE ROLE was not denied" "$o"
o=$(psql_exec "$DB_APP_PASSWORD" postgres stockpulse_app stockpulse_test -v ON_ERROR_STOP=1 -c "CREATE TABLE public.probe_should_fail (id int)" 2>&1)
echo "$o" | grep -qi "permission denied" && pass "4d. CREATE TABLE denied for stockpulse_app" || fail "4d. CREATE TABLE was not denied" "$o"
$DC exec -T postgres psql -U "$SU" -d stockpulse_test -X -q -c "DROP TABLE IF EXISTS rls_probe" >/dev/null 2>&1

echo; echo "== 5. Redis requires the password =="
o=$($DC exec -T redis redis-cli ping 2>&1)
echo "$o" | grep -q "NOAUTH" && pass "5a. unauthenticated request rejected (NOAUTH)" || fail "5a. unauthenticated request not rejected" "$o"
o=$($DC exec -T -e REDISCLI_AUTH="$REDIS_PASSWORD" redis redis-cli ping 2>&1)
[ "$o" = "PONG" ] && pass "5b. authenticated PING returns PONG" || fail "5b. authenticated ping" "$o"

echo; echo "== 6. Object-storage bucket and Mailpit UI =="
o=$($DC run --rm --no-deps --entrypoint aws storage-init --endpoint-url http://rustfs:9000 s3api head-bucket --bucket "$S3_BUCKET" 2>&1)
[ $? -eq 0 ] && pass "6a. bucket '$S3_BUCKET' exists" || fail "6a. bucket check" "$o"
if command -v curl >/dev/null 2>&1; then
  curl -fsS -o /dev/null "http://127.0.0.1:${MAILPIT_UI_PORT}/" && pass "6b. Mailpit UI reachable on :${MAILPIT_UI_PORT}" || fail "6b. Mailpit UI not reachable"
else skip "6b. Mailpit UI" "curl not installed (open http://127.0.0.1:${MAILPIT_UI_PORT} in a browser)"; fi

echo; echo "== 7. Published ports are loopback-only =="
ports=$($DC --profile pool ps --format '{{.Name}} {{.Ports}}' 2>&1)
echo "$ports"
if echo "$ports" | grep -Eq '0\.0\.0\.0:|\[::\]:|:::'; then fail "7. some port is exposed beyond 127.0.0.1"; else pass "7. all published ports bound to 127.0.0.1"; fi

echo; echo "== 8. app image: PHP 8.4 and required extensions =="
if $DC build app >/tmp/verify-1b-build.log 2>&1; then
  v=$($DC run --rm --no-deps -T app php -v 2>/dev/null | head -n 1)
  echo "$v" | grep -q '^PHP 8\.4\.' && pass "8a. $v" || fail "8a. PHP version" "$v"
  mods=$($DC run --rm --no-deps -T app php -m 2>/dev/null | tr 'A-Z' 'a-z')
  missing=""
  for e in pdo_pgsql pgsql mbstring intl bcmath gd redis openssl zip pcntl; do echo "$mods" | grep -qx "$e" || missing="$missing $e"; done
  [ -z "$missing" ] && pass "8b. all required extensions present" || fail "8b. missing extensions:$missing"
else fail "8. app image build failed" "see /tmp/verify-1b-build.log (last lines: $(tail -n 5 /tmp/verify-1b-build.log | tr '\n' ' '))"; fi

echo; echo "== Summary: $PASS passed, $FAIL failed, $SKIP skipped =="
[ "$FAIL" -eq 0 ]
