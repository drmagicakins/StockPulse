#!/usr/bin/env bash
# WP 1D acceptance checks (tenancy core). Prints PASS / FAIL / SKIP with the real result.
# Side effects: starts the stack, migrates the dev and test databases as the owner role, runs tests,
# inserts and deletes ONE businesses row (to check the uuidv7 default).
set -uo pipefail
cd "$(dirname "$0")/.."
[ -f .env ] || { echo "No .env found. Run: make env"; exit 2; }
[ -f backend/composer.json ] || { echo "Backend not installed. Run: make backend-install"; exit 2; }
set -a; . ./.env; set +a

DC="docker compose --profile pool --profile app"
PASS=0; FAIL=0; SKIP=0
pass() { echo "PASS  $1"; PASS=$((PASS+1)); }
fail() { echo "FAIL  $1"; [ -n "${2:-}" ] && printf '%s\n' "$2" | sed 's/^/      /'; FAIL=$((FAIL+1)); }
skip() { echo "SKIP  $1 ($2)"; SKIP=$((SKIP+1)); }
run() { $DC run --rm -T "$@" 2>/dev/null; }
runall() { $DC run --rm -T "$@" 2>&1; }
pest() { runall app ./vendor/bin/pest --colors=never "$@"; }
sql() { $DC exec -T postgres psql -U "$POSTGRES_USER" -d "$1" -X -q -tA -F'|' -c "$2" 2>&1; }

echo "== Starting the full stack =="
$DC up -d --build --quiet-pull 2>&1 | tail -n 15
ready=0
for i in $(seq 1 30); do curl -fsS -o /dev/null "http://127.0.0.1:${NGINX_PORT:-8080}/up" 2>/dev/null && { ready=1; break; }; sleep 2; done
[ "$ready" = 1 ] && echo "application is answering" || echo "application did not answer within 60s"

echo; echo "== 1. Migrations (development database) =="
out=$(runall app php artisan migrate --database=pgsql_owner --force); rc=$?
[ $rc -eq 0 ] && pass "1a. migrate (owner role) exited 0" || fail "1a. migrate failed" "$out"
st=$(run app php artisan migrate:status --database=pgsql_owner)
if echo "$st" | grep -q "create_businesses_table" && echo "$st" | grep -q "create_business_settings_table" && ! echo "$st" | grep -q "Pending"; then
  pass "1b. businesses and business_settings migrations are Ran, none pending"
else fail "1b. migrate:status" "$st"; fi

echo; echo "== 2. Migrations (test database) =="
out=$(runall -e DB_OWNER_DATABASE=stockpulse_test app php artisan migrate --database=pgsql_owner --force); rc=$?
[ $rc -eq 0 ] && pass "2. test database migrated" || fail "2. test database migration failed" "$out"

echo; echo "== 3. Database facts (read from PostgreSQL itself) =="
rows=$(sql "$POSTGRES_DB" "select relname, relrowsecurity, relforcerowsecurity from pg_class where relname in ('businesses','business_settings') and relkind='r' order by relname collate \"C\"")
echo "$rows" | sed 's/^/      /'
[ "$rows" = "$(printf 'business_settings|t|t\nbusinesses|t|f')" ] && pass "3a. business_settings: RLS on + forced; businesses: RLS on, not forced" || fail "3a. RLS flags" "$rows"
n=$(sql "$POSTGRES_DB" "select count(*) from pg_policies where tablename in ('businesses','business_settings')")
[ "$n" = "2" ] && pass "3b. one policy on each table" || fail "3b. policy count" "$n"
p=$(sql "$POSTGRES_DB" "select has_table_privilege('stockpulse_app','businesses','INSERT'), has_table_privilege('stockpulse_app','businesses','UPDATE'), has_table_privilege('stockpulse_app','business_settings','INSERT')")
[ "$p" = "f|f|t" ] && pass "3c. runtime role cannot write businesses, can write business_settings" || fail "3c. privileges" "$p"
v=$(sql "$POSTGRES_DB" "insert into businesses (name) values ('verify-1d-uuid-check') returning substr(uuid::text, 15, 1)")
sql "$POSTGRES_DB" "delete from businesses where name = 'verify-1d-uuid-check'" >/dev/null
[ "$v" = "7" ] && pass "3d. businesses.uuid defaults to a UUIDv7 (PostgreSQL uuidv7())" || fail "3d. uuid default" "version digit: $v"

echo; echo "== 4. Tenant isolation tests (database, requests, jobs, schema guards) =="
out=$(pest --group=tenancy); rc=$?
echo "$out" | tail -n 5 | sed 's/^/      /'
[ $rc -eq 0 ] && pass "4. all tenancy tests pass" || fail "4. tenancy tests" "$(echo "$out" | tail -n 60)"

echo; echo "== 5. Full Pest suite =="
out=$(pest); rc=$?
echo "$out" | tail -n 5 | sed 's/^/      /'
[ $rc -eq 0 ] && pass "5. full suite passes" || fail "5. Pest suite" "$(echo "$out" | tail -n 60)"

echo; echo "== 6. Test fixtures cleaned up =="
n=$(sql stockpulse_test "select count(*) from businesses")
[ "$n" = "0" ] && pass "6. no leftover businesses in stockpulse_test" || fail "6. leftover test businesses: $n"

echo; echo "== 7. Code style and static analysis =="
out=$(runall app ./vendor/bin/pint --test); rc=$?
[ $rc -eq 0 ] && pass "7a. Pint clean" || fail "7a. Pint (run: make format)" "$(echo "$out" | tail -n 25)"
out=$(runall app ./vendor/bin/phpstan analyse --memory-limit=1G --no-progress); rc=$?
[ $rc -eq 0 ] && pass "7b. Larastan clean" || fail "7b. Larastan" "$(echo "$out" | tail -n 40)"

echo; echo "== 8. Application still healthy through nginx =="
body=$(curl -sS "http://127.0.0.1:${NGINX_PORT:-8080}/api/v1/health" 2>&1); echo "      body: $body"
echo "$body" | grep -q '"status":"ok"' && pass "8. health endpoint ok" || fail "8. health endpoint" "$body"

echo; echo "== 9. CI =="
skip "9. CI green on GitHub" "check the Actions page after pushing"

echo; echo "== Summary: $PASS passed, $FAIL failed, $SKIP skipped =="
[ "$FAIL" -eq 0 ]
