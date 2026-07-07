# Z-Syst Pharmacy Management SaaS — Comprehensive Gap Analysis

## Executive Summary

After exhaustive analysis of the entire codebase, your project currently has **only ~5% of a complete pharmacy management SaaS system built**. What exists is solid **infrastructure scaffolding** — multi-tenant architecture, RBAC, branch limits — but **zero pharmacy-specific business logic** has been implemented. The system cannot yet manage a single medication, process a single sale, or track inventory.

> [!CAUTION]
> **The core pharmacy domain — the entire reason this system exists — is completely absent.** Everything below represents the massive scope of missing functionality.

---

## What Currently Exists ✅

| Module | Status | Details |
|--------|--------|---------|
| **Multi-Tenant Foundation** | ✅ Built | Company, Branch, User models with relationships |
| **RBAC System** | ✅ Built | Roles, Permissions, pivot tables, middleware, policies |
| **Branch Limit Management** | ✅ Built | Limits, caching, notifications, bulk updates |
| **Activity Logging** | ✅ Built | ActivityLog model with audit trail |
| **API Authentication** | ✅ Built | Sanctum-based token auth |
| **Admin APIs** | ✅ Built | CRUD for Users, Roles, Permissions, Companies |
| **Unit Tests** | ⚠️ Partial | Only BranchLimitTest + RbacTest (2 files) |

---

## CRITICAL MISSING MODULES 🔴

These are the core pharmacy-domain features that must exist for this to be a pharmacy management system.

---

### 1. 💊 Product/Medication Management (Priority: CRITICAL)

**No models, migrations, controllers, or routes exist.**

> [!IMPORTANT]
> This is the #1 most critical missing module. A pharmacy system without products is not a pharmacy system.

#### What's Needed:

##### Database / Models
- **`products` table** — id, name, generic_name, brand_name, barcode, sku, description, category_id, manufacturer_id, dosage_form (tablet, capsule, syrup, injection, cream, etc.), strength, unit_of_measure, prescription_required (boolean), controlled_substance_schedule, storage_conditions, min_stock_level, reorder_point, max_stock_level, cost_price, selling_price, tax_rate, discount_percentage, is_active, image, company_id, created_by, updated_by, timestamps
- **`categories` table** — id, name, slug, parent_id (hierarchical), description, image, sort_order, is_active, company_id, timestamps
- **`manufacturers` table** — id, name, contact_person, email, phone, address, website, logo, is_active, company_id, timestamps
- **`product_variants` table** — For different pack sizes (strip of 10, bottle of 100ml, etc.)

##### Controllers & APIs
- Full CRUD for products, categories, manufacturers
- Product search (by name, barcode, generic name)
- Product import/export (CSV/Excel)
- Barcode scanning integration endpoint
- Low stock alerts API
- Product price history tracking

##### Missing Business Logic
- Drug interaction checking
- Prescription validation rules
- Controlled substance tracking
- Product expiry tracking per batch

---

### 2. 📦 Inventory Management (Priority: CRITICAL)

**Completely absent. No stock tracking whatsoever.**

#### What's Needed:

##### Database / Models
- **`inventory` / `stock` table** — id, product_id, branch_id, batch_number, quantity, expiry_date, manufacturing_date, cost_price, selling_price, supplier_id, purchase_id, rack_location, timestamps
- **`stock_movements` table** — id, product_id, branch_id, movement_type (in, out, transfer, adjustment, return, damage, expired), quantity, reference_type, reference_id, from_branch_id, to_branch_id, notes, performed_by, timestamps
- **`stock_adjustments` table** — id, product_id, branch_id, adjustment_type, quantity_before, quantity_after, reason, approved_by, timestamps
- **`stock_transfers` table** — id, from_branch_id, to_branch_id, status (pending, in_transit, received, cancelled), requested_by, approved_by, timestamps
- **`stock_transfer_items` table** — id, stock_transfer_id, product_id, quantity_requested, quantity_sent, quantity_received

##### Controllers & APIs
- Stock level queries per branch
- Stock adjustment workflow
- Inter-branch transfer workflow (request → approve → ship → receive)
- Expiry tracking and alerts
- Batch/lot number tracking
- Stock take / physical inventory count
- FIFO/FEFO stock rotation logic
- Low stock and out-of-stock notifications

##### Missing Business Logic
- Automatic reorder point calculations
- Expiry date enforcement (cannot sell expired products)
- FEFO (First Expiry, First Out) dispensing logic
- Wastage tracking and reporting
- Dead stock identification

---

### 3. 🛒 Sales / Point of Sale (POS) (Priority: CRITICAL)

**No sales processing capability exists.**

#### What's Needed:

##### Database / Models
- **`sales` table** — id, invoice_number (auto-generated), customer_id, branch_id, user_id (cashier), subtotal, discount_amount, discount_type, tax_amount, total_amount, amount_paid, change_amount, payment_method (cash, card, insurance, mixed), payment_status (paid, partial, pending, refunded), sale_type (walk-in, prescription, insurance), prescription_id, insurance_claim_id, notes, company_id, timestamps
- **`sale_items` table** — id, sale_id, product_id, batch_number, quantity, unit_price, discount, tax, total, timestamps
- **`sale_returns` table** — id, sale_id, return_number, reason, total_amount, returned_by, approved_by, status, timestamps
- **`sale_return_items` table** — id, sale_return_id, sale_item_id, product_id, quantity, amount

##### Controllers & APIs
- POS sale creation (with real-time stock deduction)
- Invoice generation (PDF)
- Sale returns/refunds workflow
- Daily/shift sales summary
- Payment processing
- Receipt printing integration
- Hold/park sale and resume
- Discount application (per item, per sale, coupon-based)

##### Missing Business Logic
- Invoice number auto-generation (per branch)
- Stock deduction on sale confirmation
- Stock restoration on return
- Cash register management (open/close shift)
- Daily reconciliation

---

### 4. 🧾 Purchase Management (Priority: HIGH)

**No purchasing workflow exists.**

#### What's Needed:

##### Database / Models
- **`suppliers` table** — id, name, contact_person, email, phone, address, tax_id, payment_terms, credit_limit, balance, is_active, company_id, timestamps
- **`purchase_orders` table** — id, po_number, supplier_id, branch_id, status (draft, sent, partial, received, cancelled), subtotal, discount, tax, total, expected_delivery_date, notes, created_by, approved_by, company_id, timestamps
- **`purchase_order_items` table** — id, purchase_order_id, product_id, quantity_ordered, quantity_received, unit_cost, discount, tax, total
- **`goods_received_notes` table** — id, grn_number, purchase_order_id, supplier_id, branch_id, received_by, notes, timestamps
- **`grn_items` table** — id, grn_id, product_id, batch_number, quantity_received, expiry_date, manufacturing_date, cost_price, rack_location

##### Controllers & APIs
- Purchase order CRUD and workflow (draft → approve → send → receive)
- Goods receiving with batch/expiry tracking
- Supplier management CRUD
- Purchase return to supplier
- Supplier payment tracking
- Purchase order history per supplier

---

### 5. 📋 Prescription Management (Priority: HIGH)

**No prescription handling exists — critical for pharmacy compliance.**

#### What's Needed:

##### Database / Models
- **`prescriptions` table** — id, prescription_number, patient_id, doctor_id, branch_id, status (pending, dispensed, partially_dispensed, cancelled, expired), prescribed_date, expiry_date, notes, image_path, company_id, created_by, timestamps
- **`prescription_items` table** — id, prescription_id, product_id, dosage, frequency, duration, quantity, dispensed_quantity, instructions, substitution_allowed
- **`doctors` table** — id, name, specialization, license_number, clinic_name, phone, email, address, is_active, company_id
- **`patients` / `customers` table** — id, name, phone, email, address, date_of_birth, gender, blood_group, allergies (JSON), medical_history, insurance_id, insurance_number, loyalty_points, company_id, timestamps

##### Controllers & APIs
- Prescription entry and validation
- Prescription dispensing workflow
- Patient/customer management CRUD
- Doctor management CRUD
- Drug-allergy checking
- Prescription refill tracking
- Controlled substance dispensing log

##### Missing Business Logic
- Prescription validity checking (not expired)
- Controlled substance regulations
- Patient allergy cross-referencing
- Prescription image upload and storage
- Refill limit enforcement

---

### 6. 💰 Financial / Accounting Module (Priority: HIGH)

**No financial tracking beyond basic sale totals.**

#### What's Needed:

##### Database / Models
- **`expenses` table** — id, category, amount, description, receipt_path, branch_id, approved_by, company_id, timestamps
- **`expense_categories` table** — id, name, description, company_id
- **`payment_records` table** — id, payable_type, payable_id, amount, payment_method, reference_number, notes, timestamps
- **`cash_registers` table** — id, branch_id, user_id, opening_balance, closing_balance, total_sales, total_returns, total_expenses, status (open, closed), opened_at, closed_at
- **`accounts` table** — For basic double-entry bookkeeping

##### Controllers & APIs
- Expense tracking CRUD
- Cash register open/close
- Daily financial summary
- Profit/loss calculations
- Accounts receivable (customer credit)
- Accounts payable (supplier payments)
- Tax calculations and reporting

---

### 7. 📊 Reporting & Analytics (Priority: HIGH)

**No reporting exists. Only raw data endpoints.**

#### What's Needed:

##### Reports
- **Sales Reports**: Daily, weekly, monthly, annual; by branch, by product, by category, by cashier
- **Inventory Reports**: Current stock, stock valuation, stock movement, expiry alerts, slow-moving items, dead stock
- **Purchase Reports**: Purchase orders, supplier-wise purchases, pending orders
- **Financial Reports**: Profit/loss, expense breakdown, cash flow, tax reports
- **Customer Reports**: Top customers, purchase history, loyalty points
- **Employee Reports**: Sales per employee, attendance, performance
- **Pharmacy-Specific Reports**: Prescription dispensing log, controlled substance log, drug expiry report, near-expiry products

##### Controllers & APIs
- Report generation endpoints with date filters
- PDF/Excel export for all reports
- Dashboard widgets with KPIs
- Chart data APIs for frontend visualization

---

### 8. 🏥 Insurance Management (Priority: MEDIUM)

**No insurance integration exists.**

#### What's Needed:

##### Database / Models
- **`insurance_companies` table** — id, name, contact, phone, email, contract_terms, discount_percentage, payment_terms, is_active, company_id
- **`insurance_claims` table** — id, claim_number, sale_id, insurance_company_id, patient_id, amount_claimed, amount_approved, status (pending, approved, rejected, paid), submitted_at, resolved_at
- **`insurance_plans` table** — id, insurance_company_id, plan_name, coverage_percentage, max_coverage, is_active

##### Controllers & APIs
- Insurance company management
- Claim submission and tracking
- Claim approval/rejection workflow
- Insurance sales processing
- Insurance settlement reports

---

### 9. 🔔 Notification System (Priority: MEDIUM)

**Only branch limit notifications exist. No pharmacy-specific notifications.**

#### What's Needed:
- Low stock alerts
- Expiry date warnings (30/60/90 days before)
- Purchase order status updates
- Payment due reminders
- Prescription refill reminders
- System maintenance notifications
- Multi-channel: in-app, email, SMS, push notifications
- Notification preferences per user

---

### 10. ⚙️ Settings & Configuration (Priority: MEDIUM)

**No settings management exists.**

#### What's Needed:

##### Database / Models
- **`settings` table** — id, company_id, key, value, group, type
- **`tax_rates` table** — id, name, rate, is_default, company_id
- **`payment_methods` table** — id, name, is_active, company_id
- **`invoice_settings` table** — Template, prefix, starting number, terms
- **`currency_settings`** — Currency, format, symbol position

##### Configuration Scopes
- Company-level settings (logo, address, tax registration, invoice format)
- Branch-level settings (working hours, contact info, receipt format)
- System-level settings (date format, currency, timezone, language)
- POS settings (receipt template, default payment method, auto-print)

---

## MISSING INFRASTRUCTURE ⚠️

### 11. Authentication & Session Management
- **No login/logout endpoints** — Routes exist for `auth:sanctum` but no `AuthController` with login/register/logout/forgot-password
- **No email verification flow**
- **No password reset flow**
- **2FA fields exist on User model but no implementation**
- **No session management** (active sessions, force logout)

### 12. Frontend / UI
- **No frontend application exists** — Only raw JSON API responses
- No admin dashboard
- No POS interface
- No inventory management UI
- No reporting dashboard
- No mobile app or responsive web app

### 13. Multi-Tenancy Completeness
- **No `companies` base migration** — Migration 1 adds columns to `companies` table but the `CREATE TABLE companies` migration is missing
- **No `branches` base migration** — Referenced in migrations but `CREATE TABLE branches` is missing
- **No `departments` base migration** — `CREATE TABLE departments` is also missing
- **No `users` base migration** — The base `users` table creation is missing (only RBAC fields are added)
- **No tenant isolation middleware** — Users can potentially access data across companies
- **No data scoping** — Models don't scope queries to the current user's company

### 14. Subscription / Billing (SaaS)
- **`SubscriptionChanged` event exists but no subscription model, plans, or billing**
- No subscription plans table
- No billing integration (Stripe, PayPal, etc.)
- No plan feature limits
- No trial period management
- No invoice generation for SaaS billing

### 15. File/Document Management
- **No file upload handling**
- No document storage service
- No image optimization
- No S3/cloud storage integration

### 16. Import/Export
- **Permissions reference import/export but no implementation exists**
- No CSV/Excel import for products
- No data export functionality
- No backup/restore capability

### 17. API Versioning & Documentation
- No API versioning (`/api/v1/...`)
- No Swagger/OpenAPI documentation
- No Postman collection
- No API rate limit customization per plan

### 18. Testing
- **Only 2 test files exist** with ~9 test methods total
- No Feature (integration) tests
- No API endpoint tests
- No middleware tests
- No policy tests
- No test coverage for any pharmacy module (since none exist)

### 19. Localization / i18n
- No multi-language support
- No Arabic/RTL support (critical for Middle East pharmacy market)
- No currency localization

### 20. DevOps & Deployment
- No Dockerfile for the main Laravel app
- No docker-compose.yml
- No CI/CD pipeline (.github/workflows)
- No staging/production environment configs
- No health check endpoint

---

## MISSING DATABASE TABLES SUMMARY

The following tables are referenced or required but have **no migration**:

| Table | Status | Required For |
|-------|--------|-------------|
| `companies` (base) | ❌ Missing CREATE | Core multi-tenancy |
| `branches` (base) | ❌ Missing CREATE | Core multi-tenancy |
| `departments` (base) | ❌ Missing CREATE | Core multi-tenancy |
| `users` (base) | ❌ Missing CREATE | Core authentication |
| `products` | ❌ Missing | Medication management |
| `categories` | ❌ Missing | Product categorization |
| `manufacturers` | ❌ Missing | Product sourcing |
| `inventory` / `stock` | ❌ Missing | Stock tracking |
| `stock_movements` | ❌ Missing | Stock audit trail |
| `stock_transfers` | ❌ Missing | Inter-branch transfers |
| `sales` | ❌ Missing | POS / Sales |
| `sale_items` | ❌ Missing | Sale line items |
| `sale_returns` | ❌ Missing | Returns/Refunds |
| `suppliers` | ❌ Missing | Purchase management |
| `purchase_orders` | ❌ Missing | Purchasing workflow |
| `goods_received_notes` | ❌ Missing | Stock receiving |
| `prescriptions` | ❌ Missing | Prescription handling |
| `patients` / `customers` | ❌ Missing | Patient records |
| `doctors` | ❌ Missing | Prescriber records |
| `insurance_companies` | ❌ Missing | Insurance claims |
| `insurance_claims` | ❌ Missing | Insurance processing |
| `expenses` | ❌ Missing | Financial tracking |
| `cash_registers` | ❌ Missing | POS cash management |
| `settings` | ❌ Missing | Configuration |
| `tax_rates` | ❌ Missing | Tax management |
| `notifications` | ❌ Missing | Notification storage |
| `subscriptions` | ❌ Missing | SaaS billing |
| `subscription_plans` | ❌ Missing | Plan management |

---

## PRIORITY IMPLEMENTATION ORDER

> [!IMPORTANT]
> Recommended build order based on dependencies and business value:

### Phase 1 — Foundation Fixes (1-2 weeks)
1. Fix missing base migrations (`companies`, `branches`, `departments`, `users`)
2. Build `AuthController` (login, register, logout, password reset)
3. Add multi-tenant data scoping (company_id on all queries)
4. Add API versioning

### Phase 2 — Core Pharmacy Domain (3-4 weeks)
5. Products/Medications module (models, migrations, CRUD, search)
6. Categories & Manufacturers
7. Suppliers module
8. Customer/Patient module

### Phase 3 — Inventory & Purchasing (2-3 weeks)
9. Inventory/Stock management
10. Stock movements & adjustments
11. Purchase orders & goods receiving
12. Inter-branch stock transfers

### Phase 4 — Sales & POS (2-3 weeks)
13. Sales/POS system
14. Invoice generation
15. Returns/refunds
16. Cash register management

### Phase 5 — Pharmacy Compliance (2 weeks)
17. Prescription management
18. Doctor management
19. Drug interaction / allergy checking
20. Controlled substance tracking

### Phase 6 — Financial & Reporting (2 weeks)
21. Expense tracking
22. Financial reports
23. Sales/inventory/purchase reports
24. Dashboard analytics APIs

### Phase 7 — Advanced Features (2-3 weeks)
25. Insurance management
26. Notification system expansion
27. Settings & configuration module
28. Import/Export functionality

### Phase 8 — SaaS & Frontend (3-4 weeks)
29. Subscription/billing system
30. Frontend dashboard application
31. POS frontend interface
32. Mobile-responsive design

### Phase 9 — Quality & Deployment (1-2 weeks)
33. Comprehensive test suite
34. API documentation (Swagger)
35. Docker setup & CI/CD
36. Localization support

---

## Open Questions

> [!IMPORTANT]
> These decisions will significantly impact architecture and implementation:

1. **Frontend Technology**: Will you build the frontend in Laravel Blade/Livewire, or as a separate SPA (React/Vue/Next.js) consuming the API?
2. **Target Market**: Is this targeting a specific region (e.g., Egypt/Middle East)? This affects regulatory compliance, RTL support, and language requirements.
3. **POS Hardware**: Will this integrate with barcode scanners, receipt printers, or cash drawers? This affects the POS module architecture.
4. **Insurance Integration**: Do you need integration with specific insurance providers or a generic claims system?
5. **Payment Gateway**: Which payment processors do you want to support for SaaS billing (Stripe, PayPal, local gateways)?
6. **Regulatory Compliance**: Which pharmaceutical regulations must the system comply with (e.g., Egyptian Drug Authority, FDA, etc.)?
7. **Base Migrations**: The project has migrations that ALTER tables (`companies`, `branches`, `users`) but the original CREATE migrations are missing — were these from a previous project or are they expected to exist already?

---

## Conclusion

The existing codebase provides a **clean, well-structured foundation** with good patterns (service layer, caching, events, policies). However, the **entire pharmacy business domain** — the core value proposition — needs to be built from scratch. This represents approximately **25-30 additional database tables**, **15-20 new models**, **10-15 new controllers**, and **hundreds of API endpoints** to reach a production-ready pharmacy management SaaS.
