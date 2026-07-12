# Z-Syst Implementation Roadmap

## Overview
This roadmap defines a phased execution plan for turning the current Laravel scaffold into a production-ready pharmacy management SaaS for MENA.

The plan is organized into discrete phases with clear goals, deliverables, and immediate Phase 1 actions.

---

## Phase 1 — Foundation Fixes

### Goal
Establish a stable, secure backend foundation before implementing pharmacy domain logic.

### Deliverables
- API versioning and standardized route structure
- Tenant isolation enforced across authenticated APIs
- Auth controller completed for login, register, password reset, and token refresh
- Core database migration sanity check and missing foundation migrations identified
- Global JSON error handling and API response standardization
- Rate limiting and plan-aware branch limit enforcement

### Tasks
1. Create `api/v1` route structure
2. Register `tenant` middleware and apply it to authenticated API routes
3. Harden `AuthController` and implement missing auth flows
4. Verify `company_id` on tenant-aware models and auto-bind on create
5. Add or update migrations for foundational tables (`companies`, `branches`, `users`, `plans`, `subscriptions`, `permissions`)
6. Add API resource route conventions and response resources
7. Add `App\Http\Requests` for validation

---

## Phase 2 — Core Pharmacy Domain

### Goal
Implement product management, suppliers, patients, doctors, and pharmacy-specific catalog models.

### Deliverables
- Product / medication catalog
- Suppliers and manufacturer modules
- Patient / customer records and doctor registry
- Inventory batch and expiry tracking foundations
- Basic import/export support for products

### Tasks
1. Build product, category, manufacturer, and variant models and migrations
2. Add advanced product search and barcode support
3. Create patient, doctor, and supplier APIs
4. Add drug classification and pharmacy-specific product metadata
5. Add import/export endpoints for medications and master data

---

## Phase 3 — Inventory & Purchasing

### Goal
Implement true inventory control and purchase workflows with batch-level tracking.

### Deliverables
- Inventory batch tables and stock movements
- Purchase orders and GRN workflows
- Stock adjustments, transfers, and low-stock alerts
- FEFO/expiry-aware stock allocation

### Tasks
1. Add inventory batch and movement models/migrations
2. Implement purchase order CRUD and receive workflows
3. Add stock transfer request/receive lifecycle
4. Add expiry alert reports and low stock notifications
5. Add stock take / physical count support

---

## Phase 4 — Sales & POS

### Goal
Deliver performant pharmacy sales workflows and POS integration.

### Deliverables
- Sales order and invoice management
- Real-time stock deduction and sale returns
- Cash register shift management
- Receipt/thermal printing support
- Mixed payment support (cash/card/insurance)

### Tasks
1. Implement sale, sale item, and invoice models and endpoints
2. Add sale return and refund workflows
3. Add cash register open/close and reconciliation
4. Add POS-ready APIs and endpoints for fast scanning
5. Add offline sync and receipt generation scaffolding

---

## Phase 5 — Pharmacy Compliance

### Goal
Implement prescription workflows, controlled substance tracking, and compliance checks.

### Deliverables
- Prescription entry and dispensing workflow
- Prescriber/doctor and patient allergy checks
- Controlled substance audit trail
- Prescription validity and refill limits
- Compliance-focused reports

### Tasks
1. Build prescription and prescription item models
2. Add doctor registry and controlled substance metadata
3. Add patient allergy and medical history validation
4. Add prescription dispensing states and audit log
5. Add compliance reporting endpoints

---

## Phase 6 — Financials & Reporting

### Goal
Deliver pharmacy financial intelligence and reporting.

### Deliverables
- Expense, income, and payment tracking
- Financial summaries and P&L reporting
- Sales and inventory analytics dashboards
- PDF/Excel export for all reports

### Tasks
1. Implement expense and income models and APIs
2. Add customer credit, supplier payment, and cash register reports
3. Build financial reporting queries and export endpoints
4. Add dashboard KPI aggregation services

---

## Phase 7 — Advanced Features

### Goal
Add tenant-level SaaS capabilities, insurance, notifications, and system settings.

### Deliverables
- Insurance company and claims workflow
- Multi-channel notifications
- Company/branch settings and plan limits
- Subscription/billing support

### Tasks
1. Build insurance company, plan, and claim models
2. Add notification queueing and channel configuration
3. Add settings persistence and feature toggles
4. Add SaaS plan enforcement and subscription metadata

---

## Phase 8 — Frontend & UX

### Goal
Build a modern multilingual UI using Next.js and Tailwind designed for MENA pharmacies.

### Deliverables
- Landing pages and auth screens
- Onboarding wizard and setup flow
- Dashboards for Super Admin, Company Admin, Pharmacist/Cashier
- Inventory, POS, reporting, and settings apps
- Arabic RTL support and accessibility

### Tasks
1. Scaffold Next.js app in `frontend/`
2. Build shared design system and component library
3. Implement authentication and onboarding flows
4. Build core pharmacy pages and POS experience
5. Add localization and RTL support

---

## Phase 9 — Quality, DevOps & Release

### Goal
Ship a production-quality, test-covered, documented, containerized platform.

### Deliverables
- PHPUnit, feature, and E2E tests
- Docker Compose for dev and prod
- GitHub Actions CI pipeline
- Swagger/OpenAPI docs and README updates
- Performance and security hardening

### Tasks
1. Add tests for all core APIs and services
2. Add Docker and CI configuration
3. Add API documentation and ADRs
4. Add deployment guides and runbooks
5. Run security review and address OWASP concerns

---

## Immediate Next Work (Phase 1)

- Register `tenant` middleware alias
- Apply tenant isolation to authenticated API routes
- Create shared request validation classes for auth and core resources
- Harden responses for API version `v1`
- Begin Phase 1 migration audit and fix missing foundation tables

---

## Notes
- The current codebase already includes domain scaffolding with products, prescriptions, stock, and reporting models.
- The immediate priority is not to add more domain features until foundation stability is confirmed.
- This roadmap is intended to be iterative and may be updated after the first architectural refactor.
