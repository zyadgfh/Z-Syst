# Z-Syst Pharmacy Management SaaS — Architecture Overview

## 1. Executive Summary

This repository is a Laravel-based multi-tenant SaaS platform for pharmacy management with a strong backend scaffolding already present.

Current status:
- Backend: Laravel 12, Sanctum auth, Spatie RBAC, branch limit management, activity logging.
- Frontend: no modern Next.js frontend present in the root; current frontend assets are legacy Laravel/Vite and third-party app folders.
- Pharmacy domain: partial implementation exists, but the core product/inventory/sales/prescription workflow is incomplete and scattered across legacy resource names.

This architecture document captures what currently exists, how tenant isolation works, the API structure, and the gap between the current codebase and a production-ready pharmacy SaaS.

---

## 2. Architecture Summary

### Backend Platform
- Laravel 12 application
- PHP 8.2+ compatible
- Authentication: Laravel Sanctum
- Authorization: Spatie `laravel-permission`
- Multi-tenancy: custom tenant scope + middleware
- Queue support: Laravel Horizon
- Debugging: Laravel Telescope
- CORS: `fruitcake/laravel-cors`

### Data Storage
- Relational DB compatible with MySQL/MariaDB in current setup
- Redis is expected via configuration, but not enforced in code review yet
- Activity log and auditing are present

### Modules and Domain
- Multi-tenant support via `company_id` bound to tenant context
- Branch limits and company-level branch management
- Basic business entities present: products, categories, manufacturers, purchases, sales, stock, expenses, income, prescriptions, insurance, and more
- Existing controllers expose API endpoints for inventory and financial reporting

---

## 3. Current Folder / Module Layout

### Root-level
- `app/` — core Laravel code
- `routes/` — `api.php`, `web.php`
- `config/` — Laravel configuration
- `database/` — migrations, seeders, factories
- `tests/` — PHPUnit tests
- `public/` — web assets
- `resources/`, `assets/` — frontend asset sources
- `vendor/` — dependencies

### Important subfolders
- `app/Models/` — domain models (Company, Branch, Product, Sale, Purchase, Prescription, etc.)
- `app/Http/Controllers/Api/` — main API controllers
- `app/Http/Middleware/` — tenant middleware, auth middleware, branch limit middleware
- `app/Traits/HasCompany.php` — tenant-aware model trait
- `app/Scopes/TenantScope.php` — global query scope for tenant isolation
- `app/Services/` — service layer used by analytics and other features

---

## 4. Multi-Tenant Architecture

### Tenant resolution
- `App\Http\Middleware\TenantMiddleware` resolves tenant from request via `TenantManager`
- When a tenant company is identified, `TenantManager` binds `tenant.company_id` into the container

### Tenant scoping
- `App\Traits\HasCompany` applies `TenantScope` globally for models that use it
- On create, models automatically inject `company_id` from the bound tenant
- Query scope methods:
  - `withAllTenants()` to bypass tenant filtering
  - `forCompany($companyId)` to explicitly query by company

### Isolation coverage
- Models with `HasCompany` appear to be tenant-aware, but a complete model audit is required to ensure all key business tables use the trait.

---

## 5. Authentication and Authorization

### Auth flow
- API auth routes under `routes/api.php` support:
  - `POST /api/v1/sign-in`
  - `POST /api/v1/sign-up`
  - `POST /api/v1/submit-otp`
  - password reset endpoints
- Login is token-based using Sanctum personal access tokens
- OTP email verification is implemented in `App\Http\Controllers\Api\Auth\AuthController`

### Authorization
- Spatie permission middleware aliases registered in `App\Http\Kernel`
- Roles and permissions are managed via `spatie/laravel-permission`
- Controllers use permission-protected routes in API resources, but explicit route middleware is not visible in `api.php`; need review of controller constructors and route groups.

---

## 6. API Architecture

### Versioning
- API is namespaced under `/api/v1`

### Key endpoint groups
- Authentication and session management
- Inventory and products:
  - `products`, `categories`, `manufacturer`, `stocks`, `box-sizes`, `medicine-types`
- Finance:
  - `purchase`, `sales`, `sales-return`, `purchases-return`, `dues`, `expense-categories`, `expenses`, `income-categories`, `incomes`
- Reporting:
  - purchase-report, sales-report, due-collects-report, loss-profit-report, income-report, expense-report, low-stock-report, taxes-report, sale-return-report, purchase-return-report
- Settings/resources:
  - `business`, `business-categories`, `plans`, `subscribes`, `currencies`, `taxes`, `profile`, `lang`, `banners`
- Session actions:
  - sign-out, refresh-token, change-password, new-invoice

### Current coverage
- API layer covers a broad set of domains, but many underlying business rules are likely partial or inconsistent.
- There is evidence of pharmacy-related entities, yet the endpoint set does not fully conform to the complete domain specification needed for MENA pharmacy SaaS.

---

## 7. Business Domain Entities Present Today

### Product and inventory entities
- `Product`, `Category`, `Manufacturer`, `ProductStock`, `Stock`, `StockTransfer`, `StockTransferItem`
- `MedicineType`, `BoxSize`

### Sales and purchasing
- `Sale`, `SaleItem`, `SaleReturn`, `SaleReturnDetails`
- `Purchase`, `PurchaseDetails`, `PurchaseOrder`, `PurchaseOrderItem`, `PurchaseOrderReturn`, `PurchaseOrderReturnItem`
- `Invoice`, `DueCollect`

### Customers, suppliers, and people
- `Party`, `Supplier`, `Patient`, `Doctor`
- `InsuranceCompany`, `InsurancePlan`, `InsuranceClaim`

### Finance and operations
- `Expense`, `ExpenseCategory`, `Income`, `IncomeCategory`, `CashRegister`
- `Company`, `Branch`, `Business`, `BusinessCategory`

### Compliance and pharmacy-specific
- `Prescription`, `PrescriptionItem`

---

## 8. Current Gaps vs Production-Ready Pharmacy SaaS

### Major gaps
- No dedicated frontend in modern stack (Next.js/React) in the root of repository.
- The existing API/auth flow is not complete for a polished SaaS onboarding, 2FA, or subscription-based plan enforcement.
- Tenant isolation is implemented at model level but not yet audited across all domain tables.
- There is no clear system-wide policy for branch-level permissions or plan-based rate limiting in API routes.
- Core pharmacy workflows such as FEFO inventory allocation, expiry enforcement, prescription dispensing workflow, and controlled substance audit are not fully defined.

### Compliance and security gaps
- Current auth uses OTP via email, but no TOTP/2FA implementation.
- No explicit encryption or GDPR-style data export flow visible in current code.
- No formal audit log design beyond activity logging.

### DevOps and deployment gaps
- No Docker / CI/CD manifests in the root repo.
- No explicit production-ready deployment docs, health checks, or performance strategy documents.

---

## 9. Proposed Architecture Decisions

### Backend
- Retain Laravel 12 and Sanctum for API platform.
- Continue using Spatie permission package for RBAC.
- Expand tenant isolation into a stronger tenant service and middleware, with explicit route-level enforcement.
- Add a service layer for core domain actions: ProductService, InventoryService, SaleService, PurchaseService, PrescriptionService.
- Introduce Form Requests and API Resources uniformly for validation and response shaping.

### Data model strategy
- Centralize tenant-aware entities behind `HasCompany`.
- Normalize domain tables into:
  - `products`, `product_variants`, `categories`, `manufacturers`
  - `inventory_batches`, `stock_movements`, `stock_adjustments`, `stock_transfers`
  - `sales`, `sale_items`, `sale_returns`
  - `purchase_orders`, `purchase_order_items`, `goods_received_notes`
  - `prescriptions`, `prescription_items`, `patients`, `doctors`
  - `insurance_companies`, `insurance_plans`, `insurance_claims`

### Frontend
- Build a separate Next.js app in `frontend/` or a dedicated folder.
- Use TypeScript strict mode, TailwindCSS, Shadcn/ui, React Query, Zustand, Zod.
- Support Arabic RTL to match MENA target audiences.

### Infrastructure
- Add Docker Compose for local dev and production.
- Add GitHub Actions pipelines for tests, static analysis, and build.
- Use Redis for sessions, cache, queues.
- Add Sentry and observability stubs for production readiness.

---

## 10. Recommended Immediate Next Steps

1. Create `IMPLEMENTATION_ROADMAP.md` with phased milestones and delivery scope.
2. Audit all existing models for tenant-awareness and add `HasCompany` where required.
3. Add `api/v1` route groups with explicit middleware and versioned exception handling.
4. Add `App\Http\Requests` for all current API actions and convert controllers to use them.
5. Define missing pharmacy domain tables and implement the first set of migrations for products, stock, and sales.

---

## 11. Notes on Current Implementation

- `routes/api.php` currently exposes many resource controllers and reports, which means the project already has a broad shape of a pharmacy backend.
- `App\Http\Controllers\Api\Auth\AuthController` implements email OTP signup/login, not yet classic password-reset flow.
- `App\Models\Company` includes branch limit helpers and branch usage metrics, indicating a SaaS plan/limit mindset.
- `App\Services\AnalyticsService` shows existing analytics endpoints but likely depends on incomplete stock/sales data.

---

## 12. Verdict

This codebase is a strong architectural scaffold for a pharmacy SaaS but is not currently a production-ready system. The missing work is concentrated in domain completeness, frontend modernization, tenant policy enforcement, and deployment quality.

The next task should be to lock down the architecture in `IMPLEMENTATION_ROADMAP.md` and start implementing the foundation fixes in Phase 1.
