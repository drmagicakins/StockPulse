COMPOSE ?= docker compose
DC = $(COMPOSE) --profile pool --profile app
.DEFAULT_GOAL := help

.PHONY: help env config up up-pool up-app infra down ps logs build shell psql psql-app verify reset-db \
        backend-install artisan composer migrate migrate-test test lint format stan verify-1c verify-1d

help: ## Show this help
	@grep -E '^[a-zA-Z0-9_-]+:.*## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*## "}; {printf "  %-16s %s\n", $$1, $$2}'

env: ## Create .env from .env.example (never overwrites an existing .env)
	@test -f .env && echo ".env already exists, left unchanged" || (cp .env.example .env && echo "Created .env - review the values")

config: ## Validate the Compose file
	$(DC) config -q && echo "compose config OK"

up: ## Start infrastructure: postgres, redis, rustfs, mailpit
	$(COMPOSE) up -d

up-pool: ## Start infrastructure plus PgBouncer
	$(COMPOSE) --profile pool up -d

infra: ## Infrastructure + PgBouncer (what tests need)
	$(COMPOSE) --profile pool up -d postgres pgbouncer redis rustfs mailpit storage-init

up-app: ## Start everything: infrastructure, PgBouncer, php-fpm, nginx, horizon, scheduler
	$(DC) up -d

down: ## Stop everything (data volumes are kept)
	$(DC) down

ps: ## Show service status
	$(DC) ps

logs: ## Follow logs (usage: make logs s=postgres)
	$(DC) logs -f $(s)

build: ## Build the PHP app image
	$(COMPOSE) build app

shell: ## Open a shell in the PHP container
	$(COMPOSE) run --rm --no-deps app bash

psql: ## psql as the owner role (migrations role)
	$(COMPOSE) exec -e PGPASSWORD="$$(grep ^DB_OWNER_PASSWORD= .env | cut -d= -f2-)" postgres psql -h postgres -U stockpulse_owner -d stockpulse

psql-app: ## psql as the runtime application role
	$(COMPOSE) exec -e PGPASSWORD="$$(grep ^DB_APP_PASSWORD= .env | cut -d= -f2-)" postgres psql -h postgres -U stockpulse_app -d stockpulse

verify: ## Run the WP 1B acceptance checks (output saved to verify-1b.log)
	bash scripts/verify-1b.sh 2>&1 | tee verify-1b.log

reset-db: ## DESTROY the PostgreSQL data volume (asks for confirmation)
	@printf "This permanently deletes ALL local database data. Type 'yes' to continue: " && read ans && [ "$$ans" = "yes" ]
	$(COMPOSE) --profile pool stop postgres pgbouncer
	$(COMPOSE) --profile pool rm -f postgres pgbouncer
	docker volume rm $$(docker volume ls -q --filter label=com.docker.compose.project=stockpulse --filter label=com.docker.compose.volume=pg_data)
	@echo "Database volume removed. Run 'make up' to re-initialise."

backend-install: ## One-time: install Laravel 13 + packages into backend/ and apply the StockPulse overlay
	bash scripts/install-backend.sh

artisan: infra ## Run artisan (usage: make artisan c="route:list")
	$(COMPOSE) run --rm app php artisan $(c)

composer: ## Run composer (usage: make composer c="show --direct")
	$(COMPOSE) run --rm --no-deps app composer $(c)

migrate: infra ## Run migrations as the owner role
	$(COMPOSE) run --rm app php artisan migrate --database=pgsql_owner --force

migrate-test: infra ## Migrate the test database (stockpulse_test) as the owner role
	$(COMPOSE) run --rm -e DB_OWNER_DATABASE=stockpulse_test app php artisan migrate --database=pgsql_owner --force

test: infra migrate-test ## Run the Pest test suites (migrates the test database first)
	$(COMPOSE) run --rm app ./vendor/bin/pest

lint: infra ## Check code style (Pint) and static analysis (Larastan)
	$(COMPOSE) run --rm --no-deps app ./vendor/bin/pint --test
	$(COMPOSE) run --rm --no-deps app ./vendor/bin/phpstan analyse --memory-limit=1G

format: ## Fix code style with Pint
	$(COMPOSE) run --rm --no-deps app ./vendor/bin/pint

stan: ## Static analysis only
	$(COMPOSE) run --rm --no-deps app ./vendor/bin/phpstan analyse --memory-limit=1G

verify-1c: ## Run the WP 1C acceptance checks (output saved to verify-1c.log)
	bash scripts/verify-1c.sh 2>&1 | tee verify-1c.log

verify-1d: ## Run the WP 1D acceptance checks (output saved to verify-1d.log)
	bash scripts/verify-1d.sh 2>&1 | tee verify-1d.log
