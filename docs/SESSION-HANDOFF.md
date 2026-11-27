# SESSION-HANDOFF

Last updated: 2026-11-23

## Current phase
Phase 1 in progress. WP 1A, 1B, 1C verified (1C: make verify-1c 14 passed, 0 failed, 1 skipped; CI green). WP 1D (tenancy core) VERIFIED by the owner (make verify-1d: 13 passed, 0 failed, 1 skipped = CI check). No business features yet.

## Environment checks (owner-run preflight, 2026-11-23, Windows host)
New project; repo has one initial commit on `main`; no app code. PHP 8.4.14 (Herd), Composer 2.8.12, Node 20.19.6, npm 11.7.0, Docker 29.7.2, Compose v5.4.0, psql not installed (fine, Postgres runs in Docker), Java 1.8 (Android phase needs 17+), WSL had only docker-desktop (Ubuntu to be installed). All required PHP extensions present on host. No blocking incompatibilities. Note: Node 20 EOL; use Node 22+ in containers/CI (CI skeleton already uses 22). Laravel stable version to be verified at install in WP 1C.

## Completed
- WP 1A deliverables (tooling, CI skeleton, community docs, folder structure, DEV_ENVIRONMENT.md). Syntax of YAML files checked locally; CI run on GitHub not yet observed.
- ARCHITECTURE.md, ER_DIAGRAM.md, DATABASE_SCHEMA.md, SRS.md, DEVELOPMENT_ROADMAP.md, SESSION-HANDOFF.md (planning drafts).

## Pending
- README, API_SPECIFICATION, UI_UX_GUIDELINES, SECURITY_POLICY, OFFLINE_SYNC, BARCODE_INTEGRATION, DEPLOYMENT_GUIDE, TESTING_STRATEGY, CHANGELOG, CONTRIBUTING, SECURITY.
- All implementation phases (1-10).

## Files changed
Only the six documents above (new).

## Migrations / tests
None created; none executed.

## Known bugs
None (no code).

## Architectural decisions (proposed, not yet approved)
Modular monolith; shared DB + RLS tenancy; bigint PK + UUIDv7; immutable ledgers; FIFO plus configurable weighted average; client-generated sale UUID with idempotent sync; adapter pattern for barcode and payment providers.

## Open decisions for the owner
1. (RESOLVED) Legal entity: Mart Code International Technologies Limited; public name StockPulse.
2. PHP/Laravel versions (verify at install).
3. (RESOLVED) Decoupled SPA approved.
4. (RESOLVED) Payment gateways: Paystack, Flutterwave, Stripe.
5. (RESOLVED) Cloud-hosted, containerised, provider-agnostic; AWS recommended initial target.
6. (RESOLVED) Scale targets in ARCHITECTURE section 14. Still open: specific latency targets and RTO/RPO.

## Decision log additions
D8: Development in WSL2 Ubuntu + Docker Desktop (owner-approved).

## WP 1B status
VERIFIED 2026-11-24: `make verify` 16 passed, 0 failed (PostgreSQL 18, PgBouncer, Redis 8, RustFS, Mailpit, PHP 8.4.28 image). Committed and pushed; CI green. Not yet exercised: nginx, horizon, scheduler, node services (they start in WP 1C).

## WP 1C status
VERIFIED 2026-11-26 on the owner's machine: 14 passed, 0 failed, 1 skipped (CI, since confirmed green). Pest 27 tests. Laravel 13.44.1, Horizon 5.52.0, Pest 4.7.8, PHPUnit 12.5.33, Larastan 3.15.0 (level 6), Pint 1.32.3, brick/math 1.0.0. Committed as 93b82cb. Skeleton AI-instruction files (backend/AGENTS.md, CLAUDE.md) were replaced with StockPulse rules.

## WP 1D status
Delivered, unverified by the owner. Author-side checks: php -l on every PHP file; the exact SQL produced by the RowLevelSecurity helper plus the repo's init script were run against a real PostgreSQL 16 (23 isolation checks passed: cross-tenant read/insert/update/delete blocked, no-context returns zero rows, owner sees no tenant rows, businesses write-protected, setting cleared after commit/rollback, schema guard query flags unprotected tables). NOT run by the author: any PHP/Laravel code, Pest tests, Pint, Larastan, PostgreSQL 18 (so uuidv7() is unverified), PgBouncer.
Decisions D15-D19 in ARCHITECTURE.md. Limits in ARCHITECTURE.md section 17.

## Exact next task
1. Owner: unzip wp-1d.zip into the repo root, `make format`, `make verify-1d`, paste the output tail. Fix failures from real output.
2. When green: commit, push, confirm CI (the backend job now migrates the test database).
3. Then plan WP 1E (identity and access: users, memberships, roles/permissions, Sanctum, MFA, the real TenantResolver, platform role `stockpulse_platform`). Needs owner approval of its plan first.

## WP 1D verification (2026-11-27)
VERIFIED on the owner's machine: `make verify-1d` = 13 passed, 0 failed, 1 skipped (CI). Pest: 54 tests, 103 assertions (18 tenancy tests). Pint and Larastan clean. uuidv7() confirmed on PostgreSQL 18.
Found by the owner's first run and fixed: (1) a failed tenant request committed its writes because Laravel's routing pipeline renders exceptions into responses; SetTenantContext now rolls back on any status >= 400; (2) Larastan generics on TenantScope.
Docker fixes made the same day: PgBouncer removes a stale pid file on start; the app service has restart: unless-stopped; nginx mounts the docker/nginx folder instead of a single file (a stale single-file bind mount broke nginx after a Docker Desktop restart). After a Docker Desktop restart, if the app misbehaves: `make down` then `make up-app`.
Known item for WP 1E: the API currently answers with Access-Control-Allow-Origin: * (Laravel default); restrict CORS to the frontend origins before login exists.
Pending: first CI run of the tenancy tests (confirm on GitHub).
