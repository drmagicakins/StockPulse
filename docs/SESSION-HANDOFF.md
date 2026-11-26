# SESSION-HANDOFF

Last updated: 2026-11-23

## Current phase
Phase 1 in progress. WP 1A and WP 1B verified (1B: 16/16 checks passed on the owner's machine; CI green). WP 1C verified by the owner (make verify-1c: 14 passed, 0 failed, 1 skipped = CI check). No business features yet.

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
Delivered, unverified: scripts/install-backend.sh (+ backend-env.sh, backend-overlay), verify-1c.sh, updated docker-compose.yml (queue replaced by horizon), Makefile, CI backend job, docs. The author could not run Composer or Docker; only PHP syntax (php -l), YAML/XML/JSON parsing, bash syntax and the .env generator (scratch copy) were checked. Everything else is unrun until the owner runs `make backend-install` and `make verify-1c`. Expect first-run issues (Laravel 13 package compatibility, skeleton differences, Pint/Larastan findings).
Decisions: D11 Laravel 13, D12 runtime via PgBouncer + owner connection for migrations, D13 central migrations, D14 Larastan level 6.

## Exact next task
1. Owner: unzip wp-1c.zip into the repo root, `chmod +x scripts/*.sh`, `make backend-install`, `make migrate`, `make verify-1c`, paste the output. Fix failures from real output.
2. When all checks pass: commit (pre-commit hooks may modify skeleton files once; re-add and re-commit), push, confirm CI (backend job now runs for real).
3. Then WP 1D (tenancy core: businesses table, tenant scope, RLS policies, tenant context middleware, tenant-leak tests). Needs owner approval of its plan first.

## WP 1C verification (2026-11-26)
VERIFIED on the owner's machine: `make verify-1c` = 14 passed, 0 failed, 1 skipped (CI). Pest: 27 tests, 54 assertions. Installed: Laravel 13.44.1, Horizon 5.52.0, Pest 4.7.8, PHPUnit 12.5.33, Larastan 3.15.0 (level 6), Pint 1.32.3, brick/math 1.0.0.
Fixes applied after the first run: request-id middleware made global (unmatched routes now carry a request id); Redis queue test payload given an id (Horizon requires it).
Pending: first real CI run of the backend job (confirm on GitHub).
