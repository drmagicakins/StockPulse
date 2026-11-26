# StockPulse Architecture

Status: PLANNING DRAFT, awaiting approval. No code exists yet.
Public product name: StockPulse. Legal entity for legal, contractual, billing and ownership references: Mart Code International Technologies Limited (confirmed by owner, 2026-11-23). StockPulse is owned, developed and operated by that company.

## 1. Principles
- Modular monolith first; module boundaries allow later extraction.
- One codebase serves SaaS (multi-tenant) and Private Deployment (single-tenant mode).
- Ledgers (stock, financial, audit) are append-only. Corrections are reversals.
- The server is the authority. The frontend never decides permissions, prices, or stock.
- No feature is "complete" without backend, DB, validation, authorization and tests.

## 2. Logical layers
1. HTTP: routes -> Form Requests -> thin Controllers -> API Resources.
2. Application: Service/Action classes per use case (e.g. PostSaleService, ReceiveGoodsService), run inside DB transactions.
3. Domain: Eloquent models, value objects (Money, Quantity), domain events.
4. Infrastructure: provider adapters (barcode catalogues, payment gateways, SMS/WhatsApp), storage, queues.

## 3. Module map (Laravel `app/Modules/*`)
Platform, Business, Branches, Auth/Access, Catalogue, Barcode, Inventory, Suppliers, Procurement, Sales/POS, Sync, Customers, Payments, Finance, Promotions, Reporting, Notifications, Audit/Security.
Rule: modules talk through services and events, never by reaching into another module's tables directly.

## 4. Multi-tenancy
- SaaS: single PostgreSQL database; `business_id NOT NULL` on every tenant table.
- Enforcement in three layers:
  1. Eloquent global scope (BelongsToBusiness trait).
  2. Policies on every action.
  3. PostgreSQL RLS using `current_setting('app.current_business_id')`, set per request/job by middleware.
- Queued jobs, exports and file URLs carry business_id explicitly and re-establish tenant context.
- Private deployment: `DEPLOYMENT_MODE=private` fixes one business, own DB, license file validated at boot and on a schedule.
- Platform super-admin is a separate guard and user table scope, not a tenant role.

## 5. Authentication and authorization
- Sanctum: cookie SPA auth (web), personal access tokens bound to a registered device (Android, POS terminals).
- MFA (TOTP) mandatory for privileged roles. Argon2id hashing. Throttled login, lockout, security_events.
- Users can belong to many businesses via `business_users`; roles are per membership; branch restriction via `user_branches`.
- `X-Business-Id` is validated against membership, never trusted alone.

## 6. Inventory engine
- `stock_movements`: immutable, signed quantity, reason/type, source document (polymorphic ref), batch, location, unit cost.
- `stock_balances`: derived/cached, updated only inside `InventoryService` in the same transaction as the movement, with `SELECT ... FOR UPDATE` (or atomic conditional UPDATE) on the balance row.
- Costing: FIFO via `stock_valuation_layers`; weighted average selectable per business inventory policy.
- Issuing: FEFO for expiry-tracked products. Expired/quarantined stock is excluded from sellable quantity.
- Reconciliation job recomputes balances from the ledger and reports drift.

## 7. POS and offline sync
- Sale identity: client-generated UUID, created once, persisted in IndexedDB before any network attempt.
- Server upsert on unique `(business_id, uuid)`; replays return the original acknowledgement.
- Outbox -> `POST /api/v1/sync/batches` (Idempotency-Key = batch UUID) -> per-operation result (accepted, duplicate, conflict, rejected).
- Offline stock: per-terminal quota in `offline_stock_allocations`; overselling beyond quota is flagged as a conflict for manual reconciliation, not silently dropped.
- Offline payment rule: cash auto-accepted; transfer/card stored `pending_verification`.
- Catalogue delta sync tracked by `device_catalogue_versions`.

## 8. Barcode and product capture
- Pipeline: scan -> validate checksum -> internal lookup -> supplier catalogue -> external adapters -> suggestions (unverified) -> staff review -> authorised verification -> product saved.
- `BarcodeProvider` interface with ordered, configurable adapters; results cached in `barcode_lookup_cache` honouring provider licence terms.
- AI/OCR output only populates `product_capture_suggestions`; it never writes verified product data.
- Scanner abstraction on every client: keyboard-wedge (USB/Bluetooth HID), camera, vendor SDK/intent behind one `ScannerProvider` interface. Hardware compatibility is untested until physically verified.

## 9. Finance
- Double-entry journals, posted journals immutable, DB-level balance check (debits = credits per entry).
- Sales, receipts, supplier payments, COGS and refunds post via domain events to `PostingService`.
- Corrections = reversing entry + new entry.

## 10. Payments
- Gateway adapters for Paystack, Flutterwave and Stripe (owner-approved), selectable per business; each adapter implements init, verify, webhook-verify, refund and reconcile. Availability of each provider per tenant country/currency must be confirmed at integration time. Nothing is operational until real credentials and integration tests exist. No card data stored.
- Webhooks: signature verification, timestamp tolerance, stored in `webhook_events` with unique event id for replay protection; payment state changes only from verified events or explicit staff verification.

## 11. Infrastructure
Docker Compose (app, queue worker/Horizon, scheduler, nginx, Postgres, Redis); Ubuntu LTS; TLS; S3-compatible storage; automated backups plus restore drills; GitHub Actions (lint, static analysis, tests, tenant-leak suite); error tracking and monitoring. Hosting target to be decided.

## 12. Frontend
Decoupled Vue 3 + TS + Vite + Tailwind + Pinia + Vue Router SPA (recommended; pending owner confirmation). Feature modules lazy-loaded. POS is a separate route tree/layout and installable PWA with service worker scope limited to POS assets and catalogue.

## 13. Decisions log
| # | Decision | Status |
|---|---|---|
| D1 | Modular monolith | Proposed |
| D2 | Shared DB + RLS multi-tenancy | Proposed |
| D3 | Bigint PK + UUIDv7 public ID | Proposed |
| D4 | Decoupled SPA: Laravel API + Vue 3/TS SPA, versioned REST under /api/v1, Redis for cache, queues and sessions | Approved by owner (2026-11-23) |
| D5 | Payment gateways: Paystack, Flutterwave and Stripe, each behind a `PaymentGateway` adapter interface; enabled per business | Approved by owner (2026-11-23) |
| D6 | Cloud-hosted, containerised, provider-agnostic (AWS recommended initial target; DigitalOcean/Azure/others must remain possible); managed or self-hosted Postgres/Redis; S3-compatible storage | Approved by owner (2026-11-23) |
| D7 | Enterprise scale targets (section 14) are design targets, validated by load tests, not performance claims | Approved by owner (2026-11-23) |
| D8 | Development environment: Windows + WSL2 Ubuntu + Docker Desktop; repo in the Ubuntu filesystem; runtimes in containers (see DEV_ENVIRONMENT.md) | Approved by owner (2026-11-23) |
| D9 | PostgreSQL major 18 for development and initial production (18.7 current). 19.0 was released 2026-11-19; adopt after a stable official Docker image and PgBouncer/driver compatibility are confirmed. Version is set by POSTGRES_VERSION. | Owner asked for latest working version; 18 chosen on evidence (2026-11-24) |
| D10 | Dev S3-compatible storage: RustFS (pre-1.0, dev only). MinIO community images were withdrawn. Application uses only the S3 API; production uses a managed or self-hosted S3-compatible service. | Proposed 2026-11-24 |
| D11 | Laravel 13 (current stable; supports PHP 8.3-8.5; Laravel 12 bug fixes ended 2026-08, Laravel 14 expected 2027) | Approved by owner (2026-11-25) |
| D12 | Runtime DB connection via PgBouncer as `stockpulse_app`; migrations on a separate `pgsql_owner` connection direct to PostgreSQL (session-level locks) | Approved by owner (2026-11-25) |
| D13 | Migrations stay in the central `database/migrations`, named by module | Approved by owner (2026-11-25) |
| D14 | Static analysis starts at Larastan level 6 and is raised toward 8 as the code matures | Approved by owner (2026-11-25) |

## 14. Scale targets and design consequences
Capacity targets per business (design targets, not proven performance): 1 to 500+ branches; 10,000 to 1,000,000+ SKUs; 1,000 to 100,000+ transactions per day across all branches; many concurrent users. Platform: hundreds to thousands of tenants, scalable further.

| Concern | Design response |
|---|---|
| Stateless app tier | No local state; sessions, cache and queues in Redis; horizontal scaling of web and worker containers |
| Connection pooling | PgBouncer-compatible design: tenant context via `SET LOCAL` inside transactions, no session-level state |
| Large ledgers | `stock_movements`, `audit_logs`, `sales`, `sale_items` designed partition-ready (monthly range by date, tenant-leading indexes); partitioning applied when validated by load tests |
| 1M+ SKUs | Keyset/cursor pagination, trigram/full-text indexes for search, dedicated search path for POS (local catalogue on terminals, delta sync) |
| 500+ branches | Branch-scoped indexes and queries; cached permission and branch maps; per-branch aggregates via summary tables |
| Heavy reports | Run asynchronously on queues, read from summary tables or a read replica, exports streamed to object storage |
| Hot rows | Atomic conditional stock updates, short transactions, per-location balance rows; contention tested |
| Noisy tenants | Per-tenant rate limits and queue fairness; usage metering |
| Large tenants | Path to a dedicated database (the private deployment model) for very large customers |
| Validation | Load/performance test suite (k6 or equivalent) built from Phase 3 onward and run before each major release; targets agreed and recorded in TESTING_STRATEGY.md |

## 15. Hosting and portability
- Everything runs as Docker containers; Docker Compose for development and single-host production; orchestration (e.g. ECS/Kubernetes) possible later without code change.
- Cloud-agnostic rules: Laravel Filesystem S3-compatible driver (no provider SDK calls in domain code); queue/cache/session via Redis; secrets via environment or a secrets manager behind an interface; email/SMS/payment through adapters; infrastructure described as code with provider-specific parts isolated in `/deploy/<provider>`.
- AWS is the recommended initial production target; no AWS-only services in application code.
- Required: Nginx reverse proxy, HTTPS, automated and tested backups, object storage for images/documents/backups, CI/CD with controlled promotion (staging then production).

## 16. Payment gateway interface (Paystack, Flutterwave, Stripe)
Unified `PaymentGateway` contract: `initiate`, `verify`, `handleWebhook` (signature + timestamp + replay checks), `refund`, `reconcile`, `supportedCurrencies/Countries`. Idempotency keys on initiation and refund; provider reference and internal reference stored on `payment_transactions`; amounts as integer minor units at the provider boundary; currency handled per business and per transaction. Per-business enablement and routing rules (e.g. NGN to Paystack/Flutterwave, international to Stripe) are configuration, subject to each provider's country, currency and account eligibility. Adding a provider means adding an adapter only.
