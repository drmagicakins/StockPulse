#!/usr/bin/env bash
# One-time installer for the Laravel backend (WP 1C). Run with: make backend-install
# Installs Laravel 13 into backend/ using Composer INSIDE the PHP container, applies the StockPulse
# overlay (scripts/backend-overlay), removes unused skeleton files and formats the code.
set -euo pipefail
cd "$(dirname "$0")/.."

[ -f .env ] || { echo "Missing .env - run: make env"; exit 1; }
if [ -f backend/composer.json ]; then
  echo "backend/ already contains a Laravel project - nothing to do."
  exit 0
fi
[ -d scripts/backend-overlay ] || { echo "scripts/backend-overlay is missing"; exit 1; }

DC="docker compose --profile pool --profile app"

echo "== 1/6 Starting infrastructure and building the PHP image =="
$DC up -d postgres redis
$DC build app

echo; echo "== 2/6 Installing Laravel 13 and packages (this downloads many packages) =="
$DC run --rm --no-deps -T app bash -euo pipefail -c '
composer create-project "laravel/laravel:^13.0" /tmp/lv --no-interaction --no-scripts --prefer-dist
cp -a /tmp/lv/. /var/www/html/
cd /var/www/html
composer config --no-plugins allow-plugins.pestphp/pest-plugin true
composer require laravel/horizon "league/flysystem-aws-s3-v3:^3.0" brick/math --no-interaction
composer require --dev pestphp/pest pestphp/pest-plugin-laravel larastan/larastan --with-all-dependencies --no-interaction
'

echo; echo "== 3/6 Applying the StockPulse overlay and removing unused skeleton files =="
cp -a scripts/backend-overlay/. backend/
rm -f backend/database/migrations/0001_01_01_*.php
rm -f backend/routes/web.php backend/vite.config.js backend/package.json
rm -f backend/resources/views/welcome.blade.php
rm -f backend/tests/Feature/ExampleTest.php backend/tests/Unit/ExampleTest.php
rm -rf backend/resources/js backend/resources/css

echo; echo "== 4/6 Creating backend/.env =="
bash scripts/backend-env.sh

echo; echo "== 5/6 Application key, Horizon scaffolding, code formatting =="
$DC run --rm --no-deps -T app bash -euo pipefail -c '
php artisan key:generate --force
php artisan horizon:install
./vendor/bin/pint
echo "---------- installed versions ----------"
php artisan --version
composer show --direct
'

echo; echo "== 6/6 Cleaning up =="
rm -rf scripts/backend-overlay

echo
echo "Backend installed. Next:"
echo "  make migrate      # run migrations as the owner role"
echo "  make verify-1c    # run the WP 1C acceptance checks"
