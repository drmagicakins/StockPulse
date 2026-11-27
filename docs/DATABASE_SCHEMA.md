# StockPulse Database Schema (planning draft)

Engine: PostgreSQL (supported current release, verified at install). No migrations exist yet.

## 1. Global conventions
- PK: `id BIGINT GENERATED ALWAYS AS IDENTITY`. Public/offline ID: `uuid UUID NOT NULL UNIQUE` (UUIDv7).
- Tenant tables: `business_id BIGINT NOT NULL REFERENCES businesses`, indexed; included in composite uniques, e.g. UNIQUE(business_id, sku).
- Money: `NUMERIC(18,4)`. Quantities: `NUMERIC(18,3)`. Rates: `NUMERIC(9,6)`. No floats.
- Timestamps in UTC (`timestamptz`); business time zone applied at presentation/reporting.
- Soft delete (`deleted_at`) only for master data; transactional and ledger tables are never deleted.
- CHECK constraints: non-negative prices, quantity rules per movement type, status enums.
- RLS: enabled on all tenant tables, policy `business_id = current_setting('app.current_business_id')::bigint`; app DB role is not a superuser and does not bypass RLS. Platform operations use a separate audited role.
- Ledgers (`stock_movements`, `journal_entry_lines`, `audit_logs`, `loyalty_transactions`, `customer_account_transactions`, `cash_transactions`): INSERT/SELECT only, enforced by grants and update/delete-blocking triggers.
- Journal balance: constraint trigger ensures SUM(debit)=SUM(credit) per posted entry.
- Document numbers from `document_sequences` with row locking.

## 2. Migration order (grouped by phase)
| Phase | Tables |
|---|---|
| 1 | businesses, business_settings, users, business_users, roles, permissions, role_permissions, user_roles, branches, warehouses, stock_locations, user_branches, audit_logs, security_events, activity_logs, notifications, notification_preferences, system_settings, failed_login_attempts, trusted_devices, idempotency_keys, document_sequences, feature_modules, business_modules |
| 2 | categories, brands, manufacturers, units, products, product_barcodes, product_variants, product_images, product_attributes, product_unit_conversions, product_prices, price_histories, barcode_scans, barcode_lookup_cache, product_capture_sessions, product_capture_suggestions, scanning_devices, barcode_label_templates |
| 3 | stock_balances, stock_movements, stock_reservations, stock_adjustments(+items), stock_counts(+items), stock_transfers(+items), stock_valuation_layers, product_batches, batch_stock_balances, batch_movements |
| 4 | suppliers, supplier_contacts, product_suppliers, purchase_requisitions(+items), purchase_orders(+items), goods_received_notes(+items), supplier_invoices, supplier_payments, purchase_returns(+items) |
| 5 | pos_terminals, pos_registers, cashier_shifts, sales, sale_items, sale_item_batches, sale_discounts, suspended_sales, sales_returns(+items), receipts, payment_methods, payments, payment_transactions, payment_reconciliations, refunds(+items), webhook_events |
| 6 | sync_devices, sync_batches, sync_operations, sync_conflicts, offline_stock_allocations, device_catalogue_versions |
| 7 | customers, customer_groups, customer_group_members, loyalty_accounts, loyalty_transactions, customer_credit_accounts, customer_account_transactions, chart_of_accounts, journal_entries, journal_entry_lines, expenses, expense_categories, cash_accounts, cash_transactions, tax_rates, tax_transactions |
| 9 | subscription_plans, subscriptions, subscription_payments, usage_records, licenses |

Note: the sales `uuid` and sync-friendly columns (client_uuid, device_id, synced_at) are designed in Phase 5 so Phase 6 needs no rewrite.

## 3. Key table sketches (Phase 1)
**businesses**: id, uuid, name, legal_name, registration_no, status (pending/active/suspended/expired), currency, timezone, tax_settings jsonb, deployment_mode, created_at.
**users**: id, uuid, name, email UNIQUE, password_hash, mfa_secret (encrypted), mfa_enabled_at, email_verified_at, is_platform_admin (or separate table), last_login_at.
**business_users**: id, business_id, user_id, status; UNIQUE(business_id, user_id).
**roles**: id, business_id NULL (system roles) , name, is_system; **permissions**: id, key UNIQUE (e.g. `inventory.adjust`); **role_permissions**, **user_roles** (business_user_id, role_id).
**branches**: id, business_id, code, name, address, is_active; UNIQUE(business_id, code).
**warehouses**: id, business_id, branch_id, code, name, type (store/warehouse/quarantine/damaged).
**audit_logs**: id, business_id, actor_id, action, subject_type, subject_id, old/new jsonb, ip, user_agent, created_at (append-only).

## 4. Key table sketches (later phases)
**stock_movements**: id, uuid, business_id, product_id, variant_id, location_id, batch_id NULL, movement_type, quantity (signed), unit_cost, source_type, source_id, occurred_at, created_by. Never updated.
**stock_balances**: UNIQUE(business_id, product_id, variant_id, location_id); on_hand, reserved, version.
**sales**: id, uuid (client-generated, UNIQUE per business), reference, branch_id, shift_id, terminal_id, customer_id NULL, status, subtotal, discount_total, tax_total, grand_total, origin (online/offline), occurred_at, synced_at.
**payments**: id, uuid, sale_id, method_id, amount, status (pending_verification/verified/failed/refunded), reference, verified_by, verified_at.
**sync_operations**: id, batch_id, operation_uuid UNIQUE per business, type, payload, status, result, error.

## 5. Indexing guidance (to validate with real query plans)
- (business_id, ...) leading column on all tenant indexes.
- product_barcodes: UNIQUE(business_id, barcode).
- stock_movements: (business_id, product_id, occurred_at), (source_type, source_id).
- sales: (business_id, branch_id, occurred_at), UNIQUE(business_id, uuid).
- Design stock_movements, audit_logs, sales, sale_items, journal_entry_lines as partition-ready from the first migration (date column in key, tenant-leading indexes). Apply monthly range partitioning once load tests justify it.
- Search: pg_trgm and full-text indexes on product name/SKU for large catalogues; keyset pagination everywhere on large tables.
- Summary/aggregate tables for dashboards (per branch, per day) populated by queued jobs.
- Pooling: schema and app must work behind PgBouncer transaction mode (SET LOCAL only).

## 6. Open items
- Confirm UUIDv7 generation approach (PHP library vs PG extension).
- Decide per-business vs per-branch document sequences.
- Tax model: inclusive/exclusive, multi-rate per product.

## 7. Implemented in WP 1D (authoritative where it differs from the sketches above)
**businesses**: id bigserial PK; uuid UUIDv7 default `uuidv7()` (PostgreSQL 18), unique; name; legal_name null; registration_no null unique; status varchar(20) default 'pending' with CHECK in (pending, active, suspended, expired); currency char(3) default 'NGN'; timezone default 'Africa/Lagos'; timestamptz created_at/updated_at. RLS enabled (not forced): policy `tenant_own_row` FOR SELECT TO stockpulse_app USING (id = current tenant). INSERT/UPDATE/DELETE revoked from stockpulse_app (writes via owner now, platform role from WP 1E; own-profile updates with column grants are planned for the business-profile work package).
**business_settings**: id; business_id FK -> businesses (ON DELETE RESTRICT); key varchar(100); value jsonb; timestamptz created_at/updated_at; UNIQUE (business_id, key). RLS enabled and FORCED: policy `tenant_isolation` FOR ALL TO stockpulse_app USING and WITH CHECK (business_id = current tenant). The owner role has no policy, so it sees no tenant rows.
**Tenant predicate** (single source: App\Modules\Core\Database\RowLevelSecurity): `business_id = nullif(current_setting('app.current_business_id', true), '')::bigint`. Unset or empty means NULL, which matches nothing (fails closed).
**Rule for every future tenant table**: create it in a migration, then call `RowLevelSecurity::tenantTable('<table>')`, and put `BelongsToBusiness` on its model. The test suite fails if either is forgotten.
