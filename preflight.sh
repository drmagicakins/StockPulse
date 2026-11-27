#!/usr/bin/env bash
# StockPulse pre-flight: READ-ONLY. Changes nothing. Run in the project root, paste output to your assistant.
echo "== Workspace =="; pwd; ls -la | head -40
echo; echo "== Git =="
if git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  git branch --show-current; git status --short | head -30; git log --oneline -5
else echo "Not a Git repository"; fi
echo; echo "== Versions =="
for c in "php -v" "composer --version" "node -v" "npm -v" "docker --version" "docker compose version" "psql --version" "redis-server --version" "java -version"; do
  echo "-- $c"; $c 2>&1 | head -2 || echo "not installed"
done
echo; echo "== PHP extensions needed =="
for e in pdo_pgsql pgsql mbstring intl bcmath gd redis openssl zip; do
  php -m 2>/dev/null | grep -qi "^$e$" && echo "ok  $e" || echo "MISSING $e"
done
echo; echo "== Existing docs/project files =="
ls docs composer.json package.json artisan 2>&1 | head -30
