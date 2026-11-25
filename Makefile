COMPOSE ?= docker compose
.DEFAULT_GOAL := help

.PHONY: help env config up up-pool down ps logs build shell psql psql-app verify reset-db

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*## "}; {printf "  %-10s %s\n", $$1, $$2}'

env: ## Create .env from .env.example (never overwrites an existing .env)
	@test -f .env && echo ".env already exists, left unchanged" || (cp .env.example .env && echo "Created .env - review the values")

config: ## Validate the Compose file
	$(COMPOSE) --profile pool --profile app config -q && echo "compose config OK"

up: ## Start infrastructure: postgres, redis, rustfs, mailpit
	$(COMPOSE) up -d

up-pool: ## Start infrastructure plus PgBouncer
	$(COMPOSE) --profile pool up -d

down: ## Stop everything (data volumes are kept)
	$(COMPOSE) --profile pool --profile app down

ps: ## Show service status
	$(COMPOSE) --profile pool --profile app ps

logs: ## Follow logs (usage: make logs s=postgres)
	$(COMPOSE) --profile pool --profile app logs -f $(s)

build: ## Build the PHP app image
	$(COMPOSE) build app

shell: ## Open a shell in the PHP container (needs the image from 'make build')
	$(COMPOSE) run --rm --no-deps app bash

psql: ## psql as the owner role (migrations role)
	$(COMPOSE) exec -e PGPASSWORD="$$(grep ^DB_OWNER_PASSWORD= .env | cut -d= -f2-)" postgres psql -h postgres -U stockpulse_owner -d stockpulse

psql-app: ## psql as the runtime application role
	$(COMPOSE) exec -e PGPASSWORD="$$(grep ^DB_APP_PASSWORD= .env | cut -d= -f2-)" postgres psql -h postgres -U stockpulse_app -d stockpulse

verify: ## Run the WP 1B acceptance checks and save output to verify-1b.log
	bash scripts/verify-1b.sh 2>&1 | tee verify-1b.log

reset-db: ## DESTROY the PostgreSQL data volume (asks for confirmation)
	@printf "This permanently deletes ALL local database data. Type 'yes' to continue: " && read ans && [ "$$ans" = "yes" ]
	$(COMPOSE) --profile pool stop postgres pgbouncer
	$(COMPOSE) --profile pool rm -f postgres pgbouncer
	docker volume rm $$(docker volume ls -q --filter label=com.docker.compose.project=stockpulse --filter label=com.docker.compose.volume=pg_data)
	@echo "Database volume removed. Run 'make up' to re-initialise."
