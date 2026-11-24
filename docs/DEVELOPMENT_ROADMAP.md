# StockPulse Development Roadmap (planning draft)

Each phase starts with: read SRS/ARCHITECTURE/DATABASE_SCHEMA/SESSION-HANDOFF, inspect repo, plan, get approval. Each ends with an implementation report (completed, partial, pending, files, migrations, endpoints, tests actually run, issues).

| Phase | Scope | Exit criteria |
|---|---|---|
| 0 | Discovery, architecture, docs (this stage) | Owner approves docs and open decisions |
| 1 | Foundation: repo, Docker, Laravel, Vue/TS, Postgres/Redis, auth + MFA, tenancy + RLS, business, branches, warehouses, RBAC, audit/security-event/notification skeleton | Tenant isolation, branch permission and login-security tests pass in CI |
| 2 | Catalogue and barcode engine, capture workflow, labels, import/export | Duplicate barcode prevention and capture verification tests pass |
| 3 | Inventory: ledger, receiving, adjustments, transfers, stocktake, batches/expiry, valuation, alerts | Concurrent deduction, transfer reconciliation, expired-stock tests pass |
| 4 | Suppliers and procurement | Partial receiving, approval workflow tests pass |
| 5 | POS and sales online: shifts, checkout, discounts, tax, payments, receipts, suspend/resume, returns/refunds | Shift reconciliation, return/refund tests pass; sale UUID design in place |
| 6 | Offline PWA, sync API, conflict handling, Android Java app | Duplicate submission, offline-sale-sync tests pass; hardware claims limited to devices tested |
| 7 | Customers, loyalty, credit, finance, journals, expenses, reports | Journals balance; reversal flow tested |
| 8 | Analytics, forecasting, expiry/reorder automation, notifications | Dashboards use real data only |
| 9 | SaaS plans, subscriptions, licensing, module gating, platform admin | Suspension/expiry and licence tests pass |
| 10 | Security audit, performance, backup/restore test, production deploy, UAT | Restore test executed, audit findings closed |

## Adjustments to the original plan
1. Audit log and notification skeleton move into Phase 1.
2. Sale UUID and outbox-friendly schema are designed in Phase 5; offline behaviour arrives in Phase 6.

## Risks
| Risk | Mitigation |
|---|---|
| Tenant leakage | Three-layer isolation plus automated cross-tenant tests on every endpoint |
| Offline oversell | Per-terminal allocations, conflict queue, manual reconciliation |
| Scanner hardware variance | Scanner abstraction, test per device model before claiming support |
| Gateway delays | Adapter pattern; Phase 5 can proceed with cash and manual transfer verification |
| Scope size | Strict phase gating, small testable increments |

## Phase 1 detailed plan
To be produced and submitted for approval before any code, listing exact files, tables, endpoints and tests.
