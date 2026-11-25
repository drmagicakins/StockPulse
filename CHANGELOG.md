# Changelog

All notable changes are documented here. Format based on Keep a Changelog; versioning to be defined before first release.

## [Unreleased]
### Added
- WP 1B: Docker Compose dev stack (PostgreSQL 18, optional PgBouncer, Redis, RustFS S3-compatible dev storage, Mailpit, PHP 8.4 php-fpm image, nginx, queue/scheduler/node services behind the `app` profile), least-privilege database roles, `.env.example`, Makefile, `scripts/verify-1b.sh` acceptance checks, `docker/README.md`.
- WP 1A: repository tooling (`.gitattributes`, `.editorconfig`, `.gitignore`, pre-commit configuration), CI workflow skeleton, `CONTRIBUTING.md`, `SECURITY.md`, extended `README.md`, folder structure (`backend/`, `frontend/`, `docker/`, `deploy/`), `docs/DEV_ENVIRONMENT.md`.
- Planning documentation set (SRS, architecture, ER diagram, database schema, roadmap, Phase 1 plan, session handoff).

### Changed
- Dev object storage: MinIO replaced by RustFS (MinIO community images withdrawn). PostgreSQL pinned to major 18 (19.0 released 2026-11-19; stable 19 Docker image not yet confirmed).
