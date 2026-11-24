# StockPulse Phase 1 Implementation Plan: Project Foundation

Status: APPROVED by owner (2026-11-23). Execution not yet started; pre-flight must run in the project workspace (Claude Code, CodeGPT or any assistant).

## 1. Scope (in)
Repository and tooling, Docker environment, Laravel API, Vue 3 + TypeScript SPA shell, PostgreSQL + Redis, authentication (with MFA, password recovery, email verification, device registration, login history), multi-tenant architecture with RLS, business management, branches, warehouses, stock locations, roles/permissions, plus the audit, security-event and notification skeleton (moved here from later phases).

## 2. Scope (out)
Products, barcode, inventory, procurement, POS, finance, subscriptions billing, Android, offline. Platform "subscription" tables are not created yet; the tenant `status` field is enough for approval/suspension in this phase.

## 3. Assumptions (overrule any of these)
| # | Assumption | Impact if changed |
|---|---|---|
| A1 | Decoupled Vue SPA (separate Vite app) calling `/api/v1` | CONFIRMED by owner |
| A2 | PHP 8.4 and latest stable Laravel, verified at install; if incompatible, I report before proceeding | Version pinning |
| A3 | Legal owner: Mart Code International Technologies Limited; public name StockPulse | CONFIRMED by owner |
| A4 | Payment gateways approved (Paystack, Flutterwave, Stripe; used from Phase 5). Hosting decided: cloud-hosted, containerised, provider-agnostic, AWS as recommended initial target; Phase 1 uses Docker Compose for local/dev only | None in Phase 1 |
| A5 | Email via SMTP configured through env; local dev uses a mail catcher | Real delivery pending credentials |
| A6 | Pest for backend tests, Vitest for frontend | Tooling only |

## 4. Pre-flight (before any code)
1. Inspect workspace; determine new vs existing; read any existing docs.
2. Check Git repo, branch, uncommitted changes. Stop and report if the tree is dirty.
3. Record versions: PHP, Composer, Node, npm, Docker, Compose, PostgreSQL.
4. Report incompatibilities. Wait for confirmation if any are material.

## 5. Work packages (executed in order, each reported before the next)
| WP | Title | Deliverables |
|---|---|---|
| 1A | Repo and tooling | Monorepo layout (`/backend`, `/frontend`, `/docs`, `/docker`), lint/format configs, static analysis, pre-commit, GitHub Actions skeleton (lint + tests) |
| 1B | Docker environment | Compose services: app (php-fpm), nginx, postgres, redis, queue worker, scheduler, mail catcher; `.env.example`; non-superuser DB app role |
| 1C | Laravel base | Module layout `app/Modules/*`, base classes (Service, FormRequest, Resource), exception handling, request-id logging, API versioning, rate limiters |
| 1D | Tenancy core | `businesses`, `BelongsToBusiness` trait + global scope, tenant-context middleware setting `app.current_business_id`, RLS policies, queue-job tenant context, deployment-mode config |
| 1E | Identity and access | Users, memberships, roles, permissions, branch restrictions, Sanctum (cookie + device tokens), MFA (TOTP), email verification, password reset, throttling/lockout, login history, device registration |
| 1F | Business structure | Business profile/settings, branches, warehouses, stock locations, document sequences |
| 1G | Audit and notifications skeleton | Append-only `audit_logs` writer, `security_events`, in-app notifications, notification preferences |
| 1H | Frontend shell | Vite + Vue 3 + TS + Tailwind theme (brand tokens), Pinia, Router, Axios client, auth screens, business/branch switcher, layout shell, admin screens for users/roles/branches/warehouses, loading/empty/error states |
| 1I | Hardening and docs | Tenant-leak test suite, security review of Phase 1 code, docs updated, SESSION-HANDOFF updated, implementation report |

## 6. Database migrations (Phase 1)
businesses, business_settings, users, business_users, roles, permissions, role_permissions, user_roles, branches, warehouses, stock_locations, user_branches, feature_modules, business_modules,. Plus: audit_logs, security_events, activity_logs, notifications, notification_preferences, system_settings, failed_login_attempts, trusted_devices (device registration), idempotency_keys, document_sequences, personal_access_tokens (Sanctum), password reset and sessions tables.
Each migration is reversible except ledger-style tables, which get no destructive down-migration in production. No destructive DB operations will be run without your explicit approval.

## 7. API endpoints (all under /api/v1)
**Auth:** POST auth/login, auth/logout, auth/forgot-password, auth/reset-password, auth/email/verify, auth/mfa/enroll, auth/mfa/confirm, auth/mfa/verify, auth/mfa/disable; GET auth/me, auth/sessions; DELETE auth/sessions/{id}; POST/GET devices.
**Platform (super-admin guard):** POST/GET platform/businesses; POST platform/businesses/{uuid}/approve | suspend | reactivate.
**Business:** GET/PATCH business; GET/PATCH business/settings.
**Org structure:** CRUD branches, warehouses, stock-locations.
**Users and access:** CRUD users (invite), PUT users/{id}/roles, PUT users/{id}/branches; CRUD roles; GET permissions.
**Audit/notifications:** GET audit-logs, GET security-events; GET notifications, POST notifications/{id}/read, GET/PUT notification-preferences.
Every write endpoint: Form Request validation, Policy authorization, API Resource response, audit entry.

## 8. Authorization model
Permission keys like `branches.manage`, `users.manage`, `roles.manage`, `audit.view`, `business.settings`. System roles seeded (Owner, Admin, Branch Manager, Auditor read-only); custom roles allowed. Privileged roles require MFA before sensitive actions.

## 9. Tests (written with the code; reported only if actually executed)
- Tenant isolation: user in business A cannot read/write/list business B data on every endpoint; RLS blocks cross-tenant queries even with application scope disabled.
- Queue jobs restore tenant context.
- Branch-level permissions: restricted user cannot access other branches.
- Auth: lockout after repeated failures, MFA required for privileged roles, password reset token single-use, session revocation.
- Multi-business membership: same user, different roles per business.
- Audit: sensitive changes produce append-only entries; update/delete on audit_logs is rejected.
- Platform vs tenant admin separation.
- Frontend: component tests for auth forms and guards.
Evidence to be pasted in the implementation report with exact commands and results.

## 10. Security checklist for Phase 1
Argon2id hashing; HTTPS-only cookies in non-dev; CSRF for SPA; rate limits per IP/user; secrets only in env; RLS with non-superuser DB role; no tenant id accepted from request body; generic login error messages; encrypted MFA secrets and recovery codes; dependency audit run in CI.

## 11. Definition of done
All WPs complete; migrations apply cleanly on empty database; tests green in CI and locally with recorded output; tenant-leak suite passing; docs and SESSION-HANDOFF updated; implementation report lists completed, partial and pending items honestly. Pending integrations (SMTP, etc.) clearly marked "pending configuration".

## 12. Risks
| Risk | Mitigation |
|---|---|
| RLS plus connection pooling leaks tenant context | Set context per transaction (`SET LOCAL`), test under queue workers and Octane-style reuse |
| Laravel/PHP version mismatch | Verify at install; report before proceeding |
| Scope creep into catalogue | Strict gating; next phase needs separate approval |

## 13. Approval requested
Please reply with one of:
- "Approved" to start pre-flight and WP 1A in your project workspace (Claude Code, CodeGPT or any assistant), or
- changes to assumptions A1-A6 or the work-package order.

## 14. Phase 1 additions driven by owner decisions (2026-11-23)
These are small, additive changes to the approved work packages, not new scope:
- 1B: Redis configured for cache, queue and session drivers; app containers stateless; optional PgBouncer service in Compose to prove pooling compatibility.
- 1C: Filesystem access only through Laravel's S3-compatible disk abstraction (MinIO in dev); no provider-specific SDK calls in domain code; secrets via env behind a config interface.
- 1D: Tenant context uses `SET LOCAL` per transaction (pooling-safe); tests run through PgBouncer in transaction mode.
- 1D/1F: Migrations use tenant-leading indexes and keyset pagination conventions from day one; a `docs/PERFORMANCE_NOTES.md` stub records scale targets (ARCHITECTURE section 14).
- 1A: CI includes deploy-path scaffolding under `/deploy` (provider-neutral); AWS specifics isolated in `/deploy/aws` later.
- Payment adapter interface is documented now (ARCHITECTURE section 16) but implemented in Phase 5.
- Load testing starts in Phase 3; Phase 1 only establishes conventions.
