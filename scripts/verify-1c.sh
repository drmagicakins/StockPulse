#!/usr/bin/env bash
# WP 1C acceptance checks. Prints PASS / FAIL / SKIP with the real result.
# Side effects: starts the full stack (infrastructure + app, nginx, horizon, scheduler, node),
# runs migrations as the owner role, runs the test suites.
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
run() { $DC run --rm -T "$@" 2>/dev/null; }          # stdout only
runall() { $DC run --rm -T "$@" 2>&1; }              # stdout + stderr
pest() { runall app ./vendor/bin/pest --colors=never "$@"; }

echo "== Starting the full stack =="
$DC up -d --build --quiet-pull 2>&1 | tail -n 25

echo; echo "== Waiting for the application to answer on port ${NGINX_PORT:-8080} =="
ready=0
for i in $(seq 1 30); do
  if curl -fsS -o /dev/null "http://127.0.0.1:${NGINX_PORT:-8080}/up" 2>/dev/null; then ready=1; break; fi
  sleep 2
done
[ "$ready" = 1 ] && echo "application is answering" || echo "application did not answer within 60s (checks below will show details)"

echo; echo "== 1. Laravel 13 installed =="
v=$(run --no-deps app php artisan --version | head -n 1)
if echo "$v" | grep -q '^Laravel Framework 13\.' && [ -f backend/composer.lock ]; then pass "1. $v (composer.lock present)"; else fail "1. Laravel version" "got: $v"; fi
echo "Direct dependencies:"; run --no-deps app composer show --direct | sed 's/^/      /'

echo; echo "== 2. Migrations run as the owner role =="
out=$(runall app php artisan migrate --database=pgsql_owner --force); rc=$?
if [ $rc -eq 0 ]; then pass "2a. migrate (owner role) exited 0"; else fail "2a. migrate failed" "$out"; fi
st=$(run app php artisan migrate:status --database=pgsql_owner)
echo "$st" | grep -q "create_failed_jobs_table" && echo "$st" | grep -q "Ran" && pass "2b. failed_jobs migration shows as Ran" || fail "2b. migrate:status" "$st"

echo; echo "== 3. Database roles through the pooler =="
out=$(pest tests/Integration/DatabaseRolesTest.php); rc=$?
[ $rc -eq 0 ] && pass "3. runtime role limited, owner role can migrate, SET LOCAL pooling-safe" || fail "3. DatabaseRolesTest" "$(echo "$out" | tail -n 30)"

echo; echo "== 4. Health endpoint through nginx =="
hdr=$(mktemp); body=$(curl -sS -D "$hdr" "http://127.0.0.1:${NGINX_PORT:-8080}/api/v1/health" 2>&1)
echo "      body: $body"
if echo "$body" | grep -q '"status":"ok"' && echo "$body" | grep -q '"storage":true' && grep -qi '^x-request-id:' "$hdr"; then
  pass "4. /api/v1/health ok (database, redis, storage all true; X-Request-Id header present)"
else fail "4. health endpoint" "$(cat "$hdr")"; fi
rm -f "$hdr"

echo; echo "== 5. Redis (cache and queue round trips) =="
out=$(pest tests/Integration/RedisTest.php); rc=$?
[ $rc -eq 0 ] && pass "5. Redis set/get, cache store, queue push/pop" || fail "5. RedisTest" "$(echo "$out" | tail -n 30)"

echo; echo "== 6. S3-compatible storage =="
out=$(pest tests/Integration/StorageTest.php); rc=$?
[ $rc -eq 0 ] && pass "6. S3 put/get/delete on RustFS" || fail "6. StorageTest" "$(echo "$out" | tail -n 30)"

echo; echo "== 7. JSON error format and rate limit =="
out=$(pest tests/Feature/ErrorFormatTest.php); rc=$?
[ $rc -eq 0 ] && pass "7a. error format tests (404, 405, 422, 500, 429, request id)" || fail "7a. ErrorFormatTest" "$(echo "$out" | tail -n 30)"
live=$(curl -sS -w '\n%{http_code}' "http://127.0.0.1:${NGINX_PORT:-8080}/api/v1/does-not-exist")
echo "      live response: $(echo "$live" | head -n 1)"
if echo "$live" | head -n 1 | grep -q '"code":"not_found"' && [ "$(echo "$live" | tail -n 1)" = "404" ]; then pass "7b. live 404 through nginx uses the JSON error format"; else fail "7b. live 404" "$live"; fi

echo; echo "== 8. Code style and static analysis =="
out=$(runall app ./vendor/bin/pint --test); rc=$?
[ $rc -eq 0 ] && pass "8a. Pint clean" || fail "8a. Pint reports style issues (run: make format)" "$(echo "$out" | tail -n 20)"
out=$(runall app ./vendor/bin/phpstan analyse --memory-limit=1G --no-progress); rc=$?
[ $rc -eq 0 ] && pass "8b. Larastan (level 6) clean" || fail "8b. Larastan" "$(echo "$out" | tail -n 40)"

echo; echo "== 9. Full Pest suite (unit, feature, architecture, integration) =="
out=$(pest); rc=$?
echo "$out" | tail -n 6 | sed 's/^/      /'
[ $rc -eq 0 ] && pass "9. all Pest tests pass" || fail "9. Pest suite" "$(echo "$out" | tail -n 40)"

echo; echo "== 10. CI =="
skip "10. CI green on GitHub" "check the Actions page after pushing"

echo; echo "== 11. Horizon and scheduler =="
hz=""
for i in $(seq 1 10); do
  hz=$($DC exec -T horizon php artisan horizon:status 2>/dev/null)
  echo "$hz" | grep -qi "running" && break
  sleep 3
done
echo "$hz" | grep -qi "running" && pass "11a. Horizon is running" || fail "11a. Horizon status" "$hz"
sid=$($DC ps -q scheduler); ss=$(docker inspect -f '{{.State.Status}}' "$sid" 2>/dev/null)
[ "$ss" = "running" ] && pass "11b. scheduler container running" || fail "11b. scheduler state: $ss" "$($DC logs --tail=20 scheduler 2>&1)"

echo; echo "== Summary: $PASS passed, $FAIL failed, $SKIP skipped =="
[ "$FAIL" -eq 0 ]
