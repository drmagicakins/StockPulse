# StockPulse Software Requirements Specification (planning draft)

Public product name: StockPulse. Legal owner for legal, contractual, billing and ownership references: Mart Code International Technologies Limited (confirmed).

## 1. Purpose and scope
StockPulse is an enterprise supermarket management platform: inventory, smart barcode capture, procurement, POS (online/offline), payments, customers, finance, analytics, SaaS subscriptions and private deployment.

## 2. Users
Platform super admin; business owner/admin; branch manager; inventory/stock clerk; procurement officer; cashier; accountant; auditor (read-only); customer (loyalty/credit, indirect).

## 3. Deployment models
- A: Cloud SaaS, many tenants, strict isolation, subscriptions and module gating.
- B: Private, single business, dedicated DB/config/branding, license activation, independent backup/restore.

## 4. Functional requirements
The 19 modules from the master specification are the functional baseline. Requirement IDs follow `FR-<module>-<n>`; each is detailed in the phase plan before implementation. Highest-risk requirements:
- FR-INV: every stock change has a ledger movement; no direct quantity edits.
- FR-POS/SYNC: idempotent sales; client UUID never regenerated on retry; cash-only offline auto-accept.
- FR-FIN: balanced, immutable journals; reversal-based corrections.
- FR-BAR: AI/OCR suggestions never become verified data without authorised review.
- FR-SEC: tenant isolation at scope, policy and RLS levels.

## 5. Non-functional requirements
| Area | Requirement (targets to be agreed with the owner) |
|---|---|
| Security | OWASP ASVS-aligned; MFA for privileged users; audit of sensitive actions |
| Integrity | ACID transactions on stock and finance; concurrency-safe stock deduction |
| Availability | POS usable offline; documented RTO/RPO to be agreed |
| Scale (design targets) | Per business: 1-500+ branches, 10,000-1,000,000+ SKUs, 1,000-100,000+ transactions/day; platform: hundreds to thousands of tenants. Validated by load testing; not a performance claim |
| Performance | Specific latency targets (e.g. barcode-to-cart, checkout) to be agreed, then load-tested |
| Accessibility | Responsive, keyboard and touch operation, accessible contrast |
| Maintainability | Modular code, automated tests, documented decisions |
| Portability | Same codebase for both deployment models; Docker-based; cloud-provider agnostic (AWS recommended initially) |

## 6. External interfaces
Barcode catalogue providers (adapter), payment gateways Paystack, Flutterwave and Stripe (unified adapter; eligibility per country/currency), SMS/email/WhatsApp (adapter, pending credentials), S3-compatible storage, label printers and receipt printers, USB/Bluetooth/camera/industrial scanners.

## 7. Assumptions and constraints
- Stack per master spec; versions verified at install.
- Currency, tax rules and receipt legal requirements are configurable and must be confirmed per market (initial market: Nigeria).
- Hardware and payment integrations are not considered working until tested with real devices/credentials.

## 8. Acceptance
Each phase is accepted by implementation report, executed test evidence and owner sign-off. Mandatory test scenarios are listed in TESTING_STRATEGY.md (to be produced in Phase 1).

## 9. Open questions
Retention periods; tax rules and receipt legal requirements; specific latency/RTO/RPO targets.
