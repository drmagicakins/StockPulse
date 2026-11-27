# StockPulse ER Diagram (planning draft)

Conventions: every tenant table also has `business_id`; branch-scoped tables have `branch_id`. Full column lists are in DATABASE_SCHEMA.md. Diagrams are split by domain for readability.

## Platform and access
```mermaid
erDiagram
  businesses ||--o{ business_settings : has
  businesses ||--o{ subscriptions : has
  subscription_plans ||--o{ subscriptions : defines
  subscriptions ||--o{ subscription_payments : paid_by
  feature_modules ||--o{ business_modules : enabled_as
  businesses ||--o{ business_modules : has
  businesses ||--o{ licenses : holds
  businesses ||--o{ usage_records : metered
  businesses ||--o{ branches : has
  branches ||--o{ warehouses : has
  warehouses ||--o{ stock_locations : has
  users ||--o{ business_users : joins
  businesses ||--o{ business_users : has
  business_users ||--o{ user_roles : assigned
  roles ||--o{ user_roles : granted
  roles ||--o{ role_permissions : contains
  permissions ||--o{ role_permissions : in
  business_users ||--o{ user_branches : restricted_to
  branches ||--o{ user_branches : allows
```

## Catalogue and barcode
```mermaid
erDiagram
  categories ||--o{ products : classifies
  brands ||--o{ products : brands
  manufacturers ||--o{ products : makes
  units ||--o{ products : measured_in
  products ||--o{ product_barcodes : identified_by
  products ||--o{ product_variants : has
  products ||--o{ product_images : has
  products ||--o{ product_attributes : has
  products ||--o{ product_unit_conversions : converts
  products ||--o{ product_prices : priced
  product_prices ||--o{ price_histories : audited_by
  products ||--o{ product_suppliers : supplied_by
  suppliers ||--o{ product_suppliers : supplies
  product_capture_sessions ||--o{ product_capture_suggestions : yields
  product_capture_sessions ||--o{ barcode_scans : contains
  scanning_devices ||--o{ barcode_scans : performed
  barcode_lookup_cache }o--|| product_barcodes : may_match
  barcode_label_templates ||--o{ products : prints
```

## Inventory
```mermaid
erDiagram
  products ||--o{ stock_balances : held
  stock_locations ||--o{ stock_balances : holds
  products ||--o{ product_batches : batched
  product_batches ||--o{ batch_stock_balances : balance
  stock_balances ||--o{ stock_movements : derived_from
  stock_movements ||--o{ batch_movements : splits
  stock_movements ||--o{ stock_valuation_layers : costs
  stock_adjustments ||--o{ stock_adjustment_items : lists
  stock_counts ||--o{ stock_count_items : lists
  stock_transfers ||--o{ stock_transfer_items : lists
  stock_reservations }o--|| stock_balances : reserves
```

## Procurement
```mermaid
erDiagram
  suppliers ||--o{ supplier_contacts : has
  purchase_requisitions ||--o{ purchase_requisition_items : lists
  purchase_requisitions ||--o| purchase_orders : becomes
  suppliers ||--o{ purchase_orders : receives
  purchase_orders ||--o{ purchase_order_items : lists
  purchase_orders ||--o{ goods_received_notes : fulfilled_by
  goods_received_notes ||--o{ goods_received_items : lists
  suppliers ||--o{ supplier_invoices : bills
  supplier_invoices ||--o{ supplier_payments : settled_by
  suppliers ||--o{ purchase_returns : receives
  purchase_returns ||--o{ purchase_return_items : lists
```

## Sales, payments, customers
```mermaid
erDiagram
  pos_terminals ||--o{ pos_registers : has
  pos_registers ||--o{ cashier_shifts : opens
  cashier_shifts ||--o{ sales : records
  customers ||--o{ sales : makes
  sales ||--o{ sale_items : contains
  sale_items ||--o{ sale_item_batches : allocated
  sales ||--o{ sale_discounts : discounted
  sales ||--o{ receipts : prints
  sales ||--o{ payments : settled_by
  payment_methods ||--o{ payments : used
  payments ||--o{ payment_transactions : gateway_events
  payments ||--o{ refunds : refunded
  refunds ||--o{ refund_items : lists
  sales ||--o{ sales_returns : returned
  sales_returns ||--o{ sales_return_items : lists
  payment_reconciliations ||--o{ payment_transactions : matches
  customer_groups ||--o{ customer_group_members : has
  customers ||--o{ customer_group_members : in
  customers ||--o{ loyalty_accounts : has
  loyalty_accounts ||--o{ loyalty_transactions : ledger
  customers ||--o{ customer_credit_accounts : has
  customer_credit_accounts ||--o{ customer_account_transactions : ledger
```

## Finance
```mermaid
erDiagram
  chart_of_accounts ||--o{ journal_entry_lines : posted_to
  journal_entries ||--o{ journal_entry_lines : balances
  expense_categories ||--o{ expenses : classifies
  cash_accounts ||--o{ cash_transactions : ledger
  tax_rates ||--o{ tax_transactions : applied
```

## Offline sync
```mermaid
erDiagram
  sync_devices ||--o{ sync_batches : uploads
  sync_batches ||--o{ sync_operations : contains
  sync_operations ||--o| sync_conflicts : may_raise
  sync_devices ||--o{ offline_stock_allocations : allocated
  sync_devices ||--o{ device_catalogue_versions : tracks
```

## Administration
```mermaid
erDiagram
  users ||--o{ notifications : receives
  users ||--o{ notification_preferences : sets
  businesses ||--o{ audit_logs : generates
  businesses ||--o{ security_events : generates
  businesses ||--o{ activity_logs : generates
```

## Additional tables proposed (beyond baseline)
- idempotency_keys: replay protection for write endpoints.
- webhook_events: payment callback signature/replay records.
- document_sequences: per-business, gap-free document numbering.
- failed_login_attempts, trusted_devices: brute-force tracking, MFA/device trust.
