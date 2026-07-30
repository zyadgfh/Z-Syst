# Gap Analysis — Z-Syst Pharmacy Management System

> **Generated:** 2026-07-29  
> **Scope:** Laravel Backend (`app/`, `Modules/`) ↔ Flutter Mobile App (`pharmacy-store-app-codecanyon-main/lib/`)

---

## 1. Overview

This document maps every feature available in the **Laravel backend** (API + Admin) against the **Flutter mobile app** to identify what is implemented, partially implemented, or missing on each side.

| Side | Status |
|------|--------|
| **Laravel API (Backend)** | ✅ Most features fully implemented |
| **Laravel Admin (Blade)** | ✅ Admin panel complete for core CRUD |
| **Flutter Mobile App** | ⚠️ Core pharmacy features present; newer features (Audit, Insurance) missing |

---

## 2. Feature Comparison Matrix

### 2.1 Core Pharmacy Operations

| Module | Backend API | Flutter App | Notes |
|--------|:-----------:|:-----------:|-------|
| **Authentication** (login, OTP, register, forgot password) | ✅ | ✅ | Complete on both sides |
| **Profile Management** | ✅ | ✅ | |
| **Products** (CRUD, barcode, stock update) | ✅ | ✅ | Full CRUD + barcode generation |
| **Categories** | ✅ | ✅ | |
| **Units** | ✅ | ❌ | No UI in Flutter (managed via web) |
| **Manufacturers** | ✅ | ❌ | No UI in Flutter |
| **Medicine Types** | ✅ | ❌ | No UI in Flutter |
| **Box Sizes** | ✅ | ❌ | No UI in Flutter |
| **Taxes** (single + group) | ✅ | ✅ | |
| **Parties** (Customers/Suppliers) | ✅ | ✅ | CRUD + details |
| **Purchases** (add, list, details) | ✅ | ✅ | |
| **Purchase Returns** | ✅ | ✅ | |
| **Sales** (add, list, details) | ✅ | ✅ | |
| **Sale Returns** | ✅ | ✅ | |
| **Stocks** (list, by product) | ✅ | ✅ | Stock list screen exists |
| **Stock Movements** | ✅ | ❌ | **Missing** — No Flutter screen for movement log |

### 2.2 Financial Features

| Module | Backend API | Flutter App | Notes |
|--------|:-----------:|:-----------:|-------|
| **Expense Categories** | ✅ | ✅ | |
| **Expenses** (add, list) | ✅ | ✅ | |
| **Income Categories** | ✅ | ✅ | |
| **Incomes** (add, list) | ✅ | ✅ | |
| **Due Collections** | ✅ | ✅ | Due list + collection screen |

### 2.3 Advanced Pharmacy Features

| Module | Backend API | Flutter App | Notes |
|--------|:-----------:|:-----------:|-------|
| **Prescriptions** (upload, link to sale) | ✅ | ✅ | List screen exists; no upload screen |
| **Drug Interactions** (check, bulk import) | ✅ | ✅ | Check + list screens |
| **FEFO System** (settings, suggestions, logs, report) | ✅ | ✅ | Settings, report, logs screens |
| **Expiry Alerts** (stats, list) | ✅ | ✅ | Alert screen + notification bell |
| **AI Sales Predictions** (settings, forecast, demand report, reorder point) | ✅ | ✅ | Settings + forecast screens |
| **Auto-Order System** (rules, suggestions, approve/reject) | ✅ | ✅ | Suggestions screen exists |
| **Inventory Turnover Analysis** | ✅ | ✅ | Full screen with tabs (summary, products, slow-moving, ABC) |

### 2.4 Audit & Compliance (NEW — Added July 2026)

| Module | Backend API | Flutter App | Notes |
|--------|:-----------:|:-----------:|-------|
| **Stock Audit** (create, start, complete, auto-populate, variance, reconciliation) | ✅ | ❌ | **CRITICAL GAP** — Completely missing from Flutter |
| **Stock Reconciliation** (create, post, update, delete) | ✅ | ❌ | **CRITICAL GAP** — No Flutter screens |
| **Financial Audit** (create, execute, complete, variance report, comparative) | ✅ | ❌ | **CRITICAL GAP** — Completely missing from Flutter |

### 2.5 Insurance System (NEW — Added July 2026)

| Module | Backend API | Flutter App | Notes |
|--------|:-----------:|:-----------:|-------|
| **Insurance Companies** (CRUD) | ✅ | ❌ | **CRITICAL GAP** — No Flutter UI |
| **Insurance Policies** (CRUD, validation, expiry tracking) | ✅ | ❌ | **CRITICAL GAP** — No Flutter UI |
| **Insurance Claims** (create, submit, approve, reject, pay) | ✅ | ❌ | **CRITICAL GAP** — No Flutter UI |
| **Insurance Coverage Rules** (product/category, pre-auth) | ✅ | ❌ | **CRITICAL GAP** — No Flutter UI |
| **Insurance Summary/Dashboard** | ✅ | ❌ | **CRITICAL GAP** — No Flutter UI |

### 2.6 Reports

| Report Type | Backend API | Flutter App | Notes |
|-------------|:-----------:|:-----------:|-------|
| Sales Report | ✅ | ✅ | |
| Purchase Report | ✅ | ✅ | |
| Due Collect Report | ✅ | ✅ | |
| Loss/Profit Report | ✅ | ✅ | |
| Income Report | ✅ | ✅ | |
| Expense Report | ✅ | ✅ | |
| Stock Report | ✅ | ✅ | |
| Tax Report | ✅ | ✅ | |
| Sale Return Report | ✅ | ✅ | |
| Purchase Return Report | ✅ | ✅ | |
| **Stock Audit Report** | ✅ | ❌ | Backend API exists; no Flutter report screen |
| **Financial Audit Report** | ✅ | ❌ | Backend API exists; no Flutter report screen |

### 2.7 Admin / Business Management

| Module | Backend API | Flutter App | Notes |
|--------|:-----------:|:-----------:|-------|
| Business Info (read/update) | ✅ | ✅ | |
| Business Categories | ✅ | ✅ | |
| Subscriptions & Plans | ✅ | ✅ | Plan list + subscribe |
| Banners | ✅ | ✅ | |
| Languages | ✅ | ✅ | Multi-language support |
| Currencies | ✅ | ✅ | |
| Roles & Permissions | ✅ | ❌ | No Flutter screens (admin web only) |
| Users (staff management) | ✅ | ❌ | No staff CRUD in Flutter |
| Settings (system) | ✅ | ✅ | Feature status screen exists |

### 2.8 Missing Backend Features (Not Yet Implemented Anywhere)

| Feature | Status | Priority |
|---------|--------|----------|
| **Multi-Warehouse** (warehouses, warehouse_stocks, stock_transfers) | ❌ Not started | Medium |
| **Drug Recall / Traceability** (batch_serial_numbers, recall_events, lot tracking) | ❌ Not started | Medium |
| **Loyalty / CRM** (points, rewards, customer tiers) | ❌ Not started | Low |
| **Insurance Claims Integration with Sales** (auto-claim from sale) | ❌ Not started | Medium |
| **Receipt Printing Templates** (customizable) | ⚠️ Basic thermal only | Low |

---

## 3. Flutter Mobile App — Detailed Gap Analysis

### 3.1 Critical Missing Screens

These modules have **complete backend APIs** but **zero Flutter implementation**:

| Missing Screen | Backend Routes | Estimated Effort |
|----------------|----------------|:----------------:|
| **Stock Audit — List Screen** | `GET /api/v1/stock-audits` | Medium |
| **Stock Audit — Create Screen** | `POST /api/v1/stock-audits` | Medium |
| **Stock Audit — Detail/Execute Screen** | `GET /api/v1/stock-audits/{id}`, `POST .../start`, `POST .../complete` | Large |
| **Stock Audit — Add Items Screen** | `POST .../details`, `POST .../bulk-details`, `POST .../auto-populate` | Medium |
| **Stock Audit — Variance Report** | `GET .../variance-report` | Small |
| **Stock Reconciliation — Create/Post** | `POST .../details/{detail}/reconcile`, `POST .../reconciliations/{id}/post` | Medium |
| **Financial Audit — List/Create** | `GET /api/v1/financial-audits`, `POST /api/v1/financial-audits` | Medium |
| **Financial Audit — Execute/Report** | `POST .../{audit}/execute`, `GET .../{audit}/report` | Large |
| **Insurance — Company CRUD** | `GET/POST /api/v1/insurance/companies`, `PUT/DELETE .../{company}` | Medium |
| **Insurance — Policy CRUD** | `GET/POST /api/v1/insurance/policies`, `PUT/DELETE .../{policy}` | Medium |
| **Insurance — Claims Lifecycle** | All `POST .../claims/{claim}/{submit,approve,reject,pay}` | Large |
| **Insurance — Coverage Rules** | `GET/POST /api/v1/insurance/coverages` | Medium |
| **Insurance — Summary Dashboard** | `GET /api/v1/insurance/summary` | Small |

### 3.2 Partially Implemented / Needs Enhancement

| Feature | Current State | What's Missing |
|---------|---------------|----------------|
| **Home Screen Grid Items** | 16 items (Parties, Sales, Purchase, Products, Due List, Sales List, Purchase List, Stock, Ledger, Loss/Profit, Expiring, Reports, Income, Expense, Tax) | Prescriptions, Drug Interactions, FEFO, Predictions, Inventory Turnover, Stock Audit, Insurance links not in grid |
| **Prescriptions** | List screen only | No add/upload screen; no link-to-sale flow |
| **Navigation** | 5 bottom tabs (Home, Dashboard, Add Product, Reports, Profile) | No deep navigation to newer features |
| **Reports Screen** | 9 report types listed | Stock Audit Report, Financial Audit Report missing |

### 3.3 Code Quality Issues (from Flutter analysis)

| Issue Type | Count | Severity |
|------------|:-----:|:--------:|
| `avoid_print` (use `debugPrint` instead) | ~200 | 💡 Info |
| `use_build_context_synchronously` | ~130 | ⚠️ Medium |
| `deprecated_member_use` (withOpacity, WillPopScope) | ~35 | ⚠️ High (will break in future Flutter) |
| `unused_import` | ~50 | 💡 Low |
| `unused_result` | ~50 | 💡 Low |

---

## 4. Action Plan — Priority Order

### Phase 1: Critical (Week 1-2)
| # | Task | Area |
|---|------|------|
| 1 | Create Stock Audit Flutter screens (list, detail, items, reconciliation) | Mobile |
| 2 | Create Financial Audit Flutter screens (list, execute, report) | Mobile |
| 3 | Add Stock Audit & Financial Audit report entries to Reports screen | Mobile |
| 4 | Add navigation links in Home grid for Stock Audit, Financial Audit | Mobile |

### Phase 2: Insurance System (Week 3-4)
| # | Task | Area |
|---|------|------|
| 5 | Create Insurance Company Flutter screens (list, add, edit) | Mobile |
| 6 | Create Insurance Policy Flutter screens (list, add, edit, detail) | Mobile |
| 7 | Create Insurance Claim Flutter screens (list, create, submit, approve, reject, pay) | Mobile |
| 8 | Create Insurance Coverage rules screen | Mobile |
| 9 | Add Insurance summary card to Dashboard | Mobile |

### Phase 3: Enhancements (Week 5-6)
| # | Task | Area |
|---|------|------|
| 10 | Add missing grid items to Home screen (Prescriptions, Drug Interactions, FEFO, Predictions, Inventory Turnover, Stock Audit, Insurance) | Mobile |
| 11 | Add Prescription upload screen in Flutter | Mobile |
| 12 | Create Stock Movement log viewer | Mobile |
| 13 | Add Stock Audit and Financial Audit reports to Reports screen | Mobile |

### Phase 4: Future Features (Long-term)
| # | Task | Area |
|---|------|------|
| 14 | Multi-Warehouse system (DB + API + Flutter) | Backend + Mobile |
| 15 | Drug Recall / Traceability system | Backend + Mobile |
| 16 | Loyalty / CRM module | Backend + Mobile |
| 17 | Fix deprecated Flutter API usage (withOpacity → withValues, WillPopScope → PopScope) | Mobile |

---

## 5. API Route Coverage Summary

| Prefix | Routes Exist | Flutter Integration | Coverage |
|--------|:-----------:|:-------------------:|:--------:|
| `/api/v1/auth` | ✅ 7 routes | ✅ | 100% |
| `/api/v1/parties` | ✅ Full CRUD | ✅ | 100% |
| `/api/v1/products` | ✅ Full CRUD + stock | ✅ | 100% |
| `/api/v1/purchase` | ✅ Full CRUD | ✅ | 100% |
| `/api/v1/sales` | ✅ Full CRUD | ✅ | 100% |
| `/api/v1/sales-return` | ✅ Index, store, show | ✅ | 100% |
| `/api/v1/purchases-return` | ✅ Index, store, show | ✅ | 100% |
| `/api/v1/stocks` | ✅ Index | ✅ | 100% |
| `/api/v1/prescriptions` | ✅ Full CRUD + review + link | ⚠️ Partial (list only) | 50% |
| `/api/v1/drug-interactions` | ✅ Full CRUD + check + bulk | ✅ | 100% |
| `/api/v1/expiry-alerts` | ✅ Stats + index | ✅ | 100% |
| `/api/v1/fefo` | ✅ 8 routes | ✅ | 100% |
| `/api/v1/predictions` | ✅ 8 routes | ✅ | 100% |
| `/api/v1/auto-order` | ✅ 9 routes | ✅ | 100% |
| `/api/v1/inventory-turnover` | ✅ 7 routes | ✅ | 100% |
| `/api/v1/stock-audits` | ✅ **20 routes** | ❌ **0%** | **0%** |
| `/api/v1/financial-audits` | ✅ **11 routes** | ❌ **0%** | **0%** |
| `/api/v1/insurance` | ✅ **19 routes** | ❌ **0%** | **0%** |
| `/api/v1/reports` | ✅ 12 report types | ⚠️ 10/12 implemented | 83% |

---

## 6. Technical Debt & Observations

### Backend
- ✅ Error handling system well-implemented (`ErrorCode` enum, exception classes, `TransactionHelper`)
- ✅ Service layer pattern consistently used
- ✅ Form request validation classes exist
- ✅ Proper multi-tenant isolation via `business_id`
- ⚠️ Some controllers still use inline JSON responses instead of exceptions (needs refactoring per `ERROR_HANDLING_STRATEGY.md`)

### Flutter
- ⚠️ No repository pattern for Audit or Insurance APIs (need `stock_audit_repo.dart`, `financial_audit_repo.dart`, `insurance_repo.dart`)
- ⚠️ No models for Audit or Insurance data
- ⚠️ `withOpacity()` deprecated in Flutter 3.27+ — should use `withValues(alpha:)`
- ⚠️ `WillPopScope` deprecated — should use `PopScope`
- ⚠️ ~130 `use_build_context_synchronously` warnings — potential crashes after async operations
- ⚠️ No centralized API error handling matching `ERROR_HANDLING_STRATEGY.md` patterns

---

## 7. Recommendations

1. **Immediate**: Build Stock Audit and Financial Audit Flutter screens — these are compliance-critical features already fully functional on the backend.
2. **Immediate**: Add Insurance Flutter screens — the backend has a complete insurance system with 19 API routes that are completely inaccessible from mobile.
3. **Short-term**: Add navigation entries for all advanced features in the Home screen grid.
4. **Short-term**: Create Flutter models and repositories for Audit and Insurance following the existing `prediction_repo.dart` pattern.
5. **Medium-term**: Implement remaining backend features (Multi-Warehouse, Drug Recall).
6. **Ongoing**: Address Flutter technical debt (deprecated APIs, context usage warnings, print statements).

---

*This analysis reflects the state of the repository as of 2026-07-29. The backend has outpaced the mobile app in feature development, particularly for modules added in late July 2026 (Audit, Insurance).*

