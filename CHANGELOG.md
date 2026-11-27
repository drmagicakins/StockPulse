# Changelog

All notable changes are documented here. Format based on Keep a Changelog; versioning to be defined before first release.

## [Unreleased]
### Added
- WP 1D: tenancy core. `businesses` and `business_settings` tables with row-level security (forced on tenant tables), `TenantContext` (one transaction per tenant request with a transaction-local setting), `BelongsToBusiness` + fail-closed `TenantScope`, `tenant` route middleware, job tenant restoration, `RowLevelSecurity` migration helper, private-mode validation, schema/model guard tests, `scripts/verify-1d.sh`, `make migrate-test`, CI migrates the test database.
- WP 1C: Laravel 13 API skeleton (installed via `make backend-install`), API-only `/api/v1` routing, request ids, one JSON error format, rate limiting, real health endpoint, `Core` module base classes, Horizon (replaces the plain queue worker service), failed_jobs migration, Pest (unit, feature, architecture, integration), Larastan level 6, Pint with strict types, CI backend job with PostgreSQL 18 and Redis services, `scripts/verify-1c.sh`.
- WP 1B: Docker Compose dev stack (PostgreSQL 18, optional PgBouncer, Redis, RustFS S3-compatible dev storage, Mailpit, PHP 8.4 php-fpm image, nginx, queue/scheduler/node services behind the `app` profile), least-privilege database roles, `.env.example`, Makefile, `scripts/verify-1b.sh` acceptance checks, `docker/README.md`.
- WP 1A: repository tooling (`.gitattributes`, `.editorconfig`, `.gitignore`, pre-commit configuration), CI workflow skeleton, `CONTRIBUTING.md`, `SECURITY.md`, extended `README.md`, folder structure (`backend/`, `frontend/`, `docker/`, `deploy/`), `docs/DEV_ENVIRONMENT.md`.
- Planning documentation set (SRS, architecture, ER diagram, database schema, roadmap, Phase 1 plan, session handoff).

### Changed
- Dev object storage: MinIO replaced by RustFS (MinIO community images withdrawn). PostgreSQL pinned to major 18 (19.0 released 2026-11-19; stable 19 Docker image not yet confirmed).

### Fixed
- WP 1D: a tenant request that ended in an error response used to COMMIT its writes (Laravel's routing pipeline renders exceptions into responses before the middleware sees them). `SetTenantContext` now rolls the request back for any status >= 400. Covered by new tests (exception, 403, 422) plus a test that successful requests still commit.
- Larastan: `TenantScope` generics (`@implements Scope<Model>`); `trait.unused` ignored for `RunsInTenantContext` until the first job uses it.
- Docker: PgBouncer removes a stale pid file on start (it crash-looped after Docker Desktop restarts); the `app` service restarts with Docker.
