# SESSION-HANDOFF

Last updated: 2026-11-23

## Current phase
Phase 1 in progress. WP 1A files delivered (not yet applied/verified in the owner's repo until the owner commits and CI runs). No application code yet.

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

## Exact next task
1. Owner: follow docs/DEV_ENVIRONMENT.md, apply the 1A files, commit on branch `phase-1/foundation`, push, and confirm the CI workflow runs (hygiene job green, backend/frontend jobs skip).
2. Then WP 1B (Docker environment): compose services app, nginx, postgres, pgbouncer (optional), redis, queue worker, scheduler, mail catcher, MinIO; `.env.example`; non-superuser DB role. Needs owner approval after 1A is confirmed.
