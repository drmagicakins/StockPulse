# StockPulse

**SMART INVENTORY. SEAMLESS RETAIL. TOTAL CONTROL.**

StockPulse is an enterprise supermarket management and intelligent inventory platform: multi-branch inventory, smart barcode capture, procurement, online/offline POS, payments, customers, finance and analytics. It runs as a multi-tenant cloud SaaS or as a dedicated private deployment from the same codebase.

Owned, developed and operated by **Mart Code International Technologies Limited**.

## Status
Phase 1 (Project Foundation) in progress. Work package 1A (repository and tooling) delivered. No application code yet. See `docs/SESSION-HANDOFF.md` for the exact current state and next task.

## Stack
Laravel (PHP 8.4) REST API under `/api/v1` | Vue 3 + TypeScript SPA (Vite, Tailwind, Pinia) | PostgreSQL | Redis | Docker | Android (Java) scanner app (Phase 6).

## Repository layout
| Path | Purpose |
|---|---|
| `backend/` | Laravel API (WP 1C) |
| `frontend/` | Vue 3 + TypeScript SPA (WP 1H) |
| `docker/` | Container definitions and Compose files (WP 1B) |
| `deploy/` | Provider-neutral deployment assets; provider specifics in `deploy/<provider>/` |
| `docs/` | Architecture, requirements, schema, plans, handoff |
| `.github/` | CI workflows |

## Getting started
Follow `docs/DEV_ENVIRONMENT.md` (Windows with WSL2 Ubuntu, Docker Desktop). Application setup instructions arrive with WP 1B and 1C.

## Documentation
Start with `docs/SRS.md`, `docs/ARCHITECTURE.md`, `docs/PHASE-1-PLAN.md` and `docs/SESSION-HANDOFF.md`.

## Contributing and security
See `CONTRIBUTING.md` and `SECURITY.md`.

© Mart Code International Technologies Limited. All rights reserved. Licence terms to be defined.
