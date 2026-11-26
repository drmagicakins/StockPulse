# backend

Laravel 13 API for StockPulse (PHP 8.4). API-only, versioned under `/api/v1`.

- Module code: `app/Modules/*` (WP 1C provides `Core`: error format, request ids, health, base classes).
- Database: runtime connection `pgsql` (role `stockpulse_app`, via PgBouncer); migrations use `pgsql_owner` (role `stockpulse_owner`, direct). Migrations live in `database/migrations`, named by module.
- Run everything through the root Makefile: `make test`, `make lint`, `make format`, `make migrate`, `make artisan c="..."`.
- Never commit `.env`.
