---
name: z-syst-pharmacy
description: "Z-Syst Pharmacy Management System - Comprehensive rules for building a professional pharmacy management system using Laravel. Covers products, stock, purchases, sales, invoices, prescriptions, finance, reports, and SaaS expansion. Follow strict engineering principles: clean architecture, service layer pattern, database-first design, and audit-trail tracking."
---

# Z-Syst Pharmacy Management System

Comprehensive development rules for building a professional pharmacy management system (PharmaMaster Pro) using Laravel. This skill ensures systematic, production-ready development following clean architecture, domain-driven design, and pharmacy-specific business logic.

## When to Use This Skill

Use this skill when working on **any aspect of a pharmacy management system**, including:

| Module | When to Activate |
|--------|-----------------|
| **Products** | Creating/managing medications, inventory items, barcodes, SKUs, categories, manufacturers, pricing |
| **Stock** | Implementing inventory tracking, stock batches, movements, FEFO, expiry alerts, low-stock warnings |
| **Purchases** | Building purchase orders, GRN (Goods Received Notes), supplier management, payment tracking |
| **Sales & Invoices** | Creating POS, invoices, returns/refunds, payment methods, discounts, taxes |
| **Prescriptions** | Managing doctors, patients, prescription items, digital scrips, refills |
| **Finance** | Implementing revenue/expense tracking, general ledger, profit/loss reports |
| **Reports** | Generating sales reports, stock reports, purchase reports, financial statements |
| **SaaS Features** | Multi-company, multi-branch, multi-warehouse support, subscription management |

Skip this skill for: non-pharmacy projects, generic CRUD apps, learning/tutorial code, or projects not using Laravel.

## Core Principles

### 1. Ultimate Goal
- Build a **professional pharmacy system** ready for real-world use
- Strong management of: Products, Stock, Purchases, Sales, Prescriptions, Finance, and Reports
- Suitable for a real pharmacy first, then expandable to SaaS / multi-branch
- Development uses **free local tools** during initial phase (Laragon, Laravel, Composer, GitHub Free, VS Code)

### 2. Business Vision
- Every business process must be **traceable and auditable**
- Every feature must solve a **real operational problem**, not be a cosmetic addition
- Never build a feature without database and business logic support first
- Support end-to-end flow: receiving goods → selling → invoicing → reporting

### 3. Engineering Principles
- Maintain **clean, modular architecture** with clear boundaries
- Use **Laravel** as the backend framework
- Separate business logic into **Services**, NOT Controllers
- Keep **Controllers thin** and high-level
- Use **Form Requests** for validation
- Use **Resources** for API responses
- Use **Policies & Middleware** for authorization
- Use **Repositories** only when complexity justifies it
- Build each module **independently** with clear boundaries

### 4. Development Order (CRITICAL)
Follow this exact order — never skip ahead:

| Phase | Module | Priority |
|-------|--------|----------|
| 1 | **Products** | Foundation |
| 2 | **Stock** | Foundation |
| 3 | **Purchases** | Operations |
| 4 | **Sales** | Operations |
| 5 | **Invoices** | Operations |
| 6 | **Prescriptions** | Pharmacy-specific |
| 7 | **Finance** | Accounting |
| 8 | **Reports** | Analytics |
| 9 | **Analytics** | Intelligence |
| 10 | **SaaS Features** | Expansion |

**Hard rule:** Do not start on Phase N+1 until Phase N is complete and tested.

## Database Design Rules

### Schema Principles
- Design tables according to **real business needs**, not just CRUD
- Every important table must support `company_id` and `branch_id` when needed
- Stock must be tracked via `stock_batches` and `stock_movements` tables
- Financial operations must be **traceable and auditable**
- Use **soft deletes** when necessary
- Add **indexes** on frequently used fields: `barcode`, `sku`, `status`, `expiry_date`, `company_id`, `branch_id`
- Avoid unnecessary duplication in tables
- Every database schema change must be **well-documented and deliberate**

### Key Tables Structure

```
products
  - id, name, generic_name, barcode, sku, category_id, manufacturer_id
  - purchase_price, selling_price, reorder_level, company_id, branch_id

stock_batches
  - id, product_id, batch_number, expiry_date, quantity, purchase_price
  - company_id, branch_id, warehouse_id

stock_movements
  - id, product_id, batch_id, type (in/out/adjustment), quantity, reference_type
  - reference_id, notes, company_id, branch_id, user_id

purchases / purchase_orders
  - id, supplier_id, status (pending/received/partial/canceled)
  - total_amount, paid_amount, company_id, branch_id

purchase_items
  - id, purchase_id, product_id, batch_id, quantity, unit_price, total

sales / invoices
  - id, customer_id, prescription_id, subtotal, discount, tax, total
  - payment_status, payment_method, company_id, branch_id

invoice_items
  - id, invoice_id, product_id, batch_id, quantity, unit_price, total

prescriptions
  - id, patient_id, doctor_id, prescription_date, notes, status

prescription_items
  - id, prescription_id, product_id, dosage, quantity, instructions

financial_transactions
  - id, type (revenue/expense), amount, reference_type, reference_id
  - description, company_id, branch_id, user_id

suppliers / customers / patients / doctors
  - Standard entity tables with company_id/branch_id support
```

## Stock Management Rules

- Stock must **always reflect real movement**
- Sales must **automatically decrease** stock
- Purchases must **automatically increase** stock
- Returns must **restore stock** correctly
- **Batch number and expiry date** must always be tracked
- Use **FEFO (First Expiry, First Out)** for dispensing/selling medications
- **Negative stock is NOT allowed** except in special justified cases
- Every stock change must be recorded in `stock_movements` log
- Support **low stock alerts** and **near-expiry alerts**
- Stock quantities are tied to specific branches/warehouses

### FEFO Implementation Pattern
```php
// When picking stock for sale/dispensing:
// 1. Filter available batches with quantity > 0
// 2. Order by expiry_date ASC
// 3. Pick from the soonest-expiring batch first

$batches = StockBatch::where('product_id', $productId)
    ->where('quantity', '>', 0)
    ->where('expiry_date', '>=', now())
    ->orderBy('expiry_date', 'asc')
    ->get();
```

## Purchase Management Rules

- Purchases must link to **suppliers, batches, and stock**
- Every purchase must update stock **upon receipt**
- Support **Purchase Orders (PO)** and **Goods Received Notes (GRN)**
- Track **supplier payments** and running balance
- PO statuses: `pending`, `received`, `partial`, `canceled`
- Partial receipts must be supported (some items received, others pending)

## Sales & Invoice Rules

- Every invoice must link to **real products and stock batches**
- Sales must be **auditable** and reversible via returns
- Handle **discounts, taxes, and payment status** correctly
- Record **payment methods** for reconciliation
- Invoice items must link to a real product and stock batch
- Returns must **correctly reverse** both financial and stock impact

## Prescription Management Rules

- Products must support: name, generic name, barcode, SKU, category, manufacturer, price
- Support **prescriptions, patients, and doctors**
- Prescription items must link to **real products**
- Dispensing/sales based on prescription must be **traceable**
- Always respect: **expiry date, batch number, and stock availability**

## Financial Management Rules

- All financial entries must be **accurate and auditable**
- Support **revenue, expenses, and general ledger**
- Sales, purchases, returns, and payments must appear in appropriate financial reports
- **Cash and payment tracking** must be organized and maintainable
- System must support basic **Profit & Loss (P&L) reports**

## Reports Rules

- Reports must be **useful for management and operations**
- Support: sales reports, stock reports, purchase reports, financial reports
- Support **export to Excel and PDF** where possible
- Reports must be based on **reliable, aggregated data**
- Reports must respect **company and branch scope**

## Security Rules

- Never expose **sensitive data** via API or UI
- Every incoming request must be **validated**
- Every protected action must go through **authentication and authorization**
- **Never trust client-side data**
- Never store passwords in plain text
- Protect endpoints and admin areas with **proper permissions**
- Use Laravel's built-in authorization (Policies + Gates + Middleware)

## Code Quality Rules

- Write **clean, readable, maintainable code**
- Use clear names for: classes, methods, variables, migrations, tables
- Keep methods **small and focused** (single responsibility)
- Follow **Laravel conventions** strictly
- NEVER put business logic inside Controllers
- Use **Services** for workflows, **Models** for data relationships
- Small, testable changes > large, risky changes
- Add comments **only when necessary** (code should be self-documenting)

### Service Layer Pattern (MUST FOLLOW)

```php
// ❌ BAD - Logic in Controller
class ProductController extends Controller
{
    public function store(Request $request)
    {
        // validation, business logic, stock creation, response formatting
        // ALL in one place - DO NOT DO THIS
    }
}

// ✅ GOOD - Thin Controller
class ProductController extends Controller
{
    public function store(StoreProductRequest $request, ProductService $service)
    {
        $result = $service->create($request->validated());
        return new ProductResource($result);
    }
}
```

## Testing Rules

- Test **core operations** before adding new features
- Minimum coverage: stock movement, sales, purchase receipt, returns, invoice creation
- Add **feature tests** for important workflows
- Prefer testing **real behavior** over mock-heavy tests
- Keep tests **simple and reliable**
- Every important module must have: `Unit tests for Services`, `Feature tests for API endpoints`

### Minimum Test Scenarios

```php
// Stock Movement Test
public function test_sale_decreases_stock()
{
    // Create product with stock batch
    // Create sale invoice
    // Assert stock decreased correctly
}

// Purchase Receipt Test
public function test_purchase_increases_stock()
{
    // Create purchase order
    // Receive goods
    // Assert stock increased
}

// Return Test
public function test_return_restores_stock()
{
    // Create sale
    // Process return
    // Assert stock restored
}
```

## MVP Strategy

- Build a **usable first version** before adding advanced features
- Focus first on: **1 company, 1 branch, 1 warehouse**
- Add multi-branch/company support only after the core flow is stable
- Do not over-engineer early
- Keep MVP **practical and useful** for a real pharmacy

## UI Guidelines

- UI must be **simple, fast, and clear**
- Prioritize usability for: pharmacy owners, cashiers, management, accountants
- Use a **unified design system** and **responsive interface**
- Do not start building complex UI until the **workflow is clear**
- Support Arabic/RTL layout where appropriate

## Documentation Rules

- Update documentation when **any feature changes**
- Use clear names for migrations, modules, and services
- Document important business rules in docs or comments
- Keep setup/installation instructions **simple and clear**

## What to AVOID (Hard Rules)

| Avoid | Why |
|-------|-----|
| Starting with UI before logic is clear | Wastes effort on unstable foundations |
| Building features without database support | Creates untracked, inconsistent data |
| Business logic in Controllers | Violates separation of concerns, untestable |
| Ignoring stock/sales integrity | Breaks audit trail, loses money |
| Duplicate modules or inconsistent names | Creates confusion, maintenance nightmare |
| Adding complexity before core flow works | Increases risk, delays delivery |
| Ignoring traceability and audit | Makes debugging and reconciliation impossible |

## Release Readiness Checklist

Before saying the project is ready, verify:

- [ ] **Database** - Clear and complete schema with proper indexes
- [ ] **Products** - Full product management with barcode/SKU/category/manufacturer
- [ ] **Stock** - Complete stock management with batches, expiry, FEFO, alerts
- [ ] **Purchases** - Full purchase workflow with PO, GRN, supplier management
- [ ] **Sales & Invoices** - POS, invoicing, returns, payment tracking
- [ ] **Prescriptions** - Doctor/patient management, prescription tracking
- [ ] **Finance** - Revenue, expenses, P&L reports
- [ ] **Reports** - Sales, stock, purchase, financial reports with export
- [ ] **Permissions & Security** - Proper authentication, authorization, role management
- [ ] **Tests** - Basic test coverage for core workflows
- [ ] **Documentation** - Simple operational docs and setup instructions
- [ ] **Expandability** - Architecture supports future multi-branch/SaaS expansion

## Rules for AI Agents

When generating code for this project, ALWAYS follow these rules in priority order:

### Priority 1: Database First
- Always design/check migrations before writing any business logic
- Always check if the table has `company_id` and `branch_id` support
- Always add proper indexes on frequently queried columns

### Priority 2: Service Layer
- Create a Service class for every module's business logic
- Controllers should only: validate input, call service, return response
- Services should handle: business rules, workflows, data persistence

### Priority 3: Audit Trail
- All stock movements must be logged in `stock_movements`
- All financial transactions must be traceable
- Never delete records that are part of financial/stock audit trail (use soft deletes)

### Priority 4: Follow Laravel Conventions
- PSR-4 autoloading
- Laravel naming conventions (snake_case tables, CamelCase classes)
- Resource controllers for RESTful APIs
- Eloquent relationships for data modeling
- Form Request classes for validation

### Priority 5: Keep It Simple
- Don't add complexity before the basic flow works
- Don't use packages/modules that aren't strictly necessary
- Don't build for multi-branch until single-branch is stable

## Module Development Template

When creating a new module, follow this structure:

```
app/Modules/{ModuleName}/
├── Controllers/
│   └── {ModuleName}Controller.php       # Thin controller
├── Models/
│   └── {ModuleName}.php                 # Eloquent model
├── Services/
│   └── {ModuleName}Service.php          # Business logic
├── Requests/
│   └── Store{ModuleName}Request.php     # Validation
├── Resources/
│   └── {ModuleName}Resource.php         # API response
├── Policies/
│   └── {ModuleName}Policy.php           # Authorization
└── routes/
    └── api.php                          # API routes
```

### Example: Product Module

```php
// app/Modules/Products/Controllers/ProductController.php
namespace App\Modules\Products\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Products\Requests\StoreProductRequest;
use App\Modules\Products\Resources\ProductResource;
use App\Modules\Products\Services\ProductService;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService
    ) {}

    public function store(StoreProductRequest $request): ProductResource
    {
        $product = $this->productService->create($request->validated());
        return new ProductResource($product);
    }

    public function index(): ProductResource
    {
        $products = $this->productService->list();
        return ProductResource::collection($products);
    }

    public function show(int $id): ProductResource
    {
        $product = $this->productService->findOrFail($id);
        return new ProductResource($product);
    }

    public function update(UpdateProductRequest $request, int $id): ProductResource
    {
        $product = $this->productService->update($id, $request->validated());
        return new ProductResource($product);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->productService->delete($id);
        return response()->json(['message' => 'Product deleted successfully']);
    }
}
```

## Final Instruction

When working on Z-Syst Pharmacy:
1. **Start from the database** - Design schema first
2. **Follow the development order** - Products → Stock → Purchases → Sales → Invoices → Prescriptions → Finance → Reports → SaaS
3. **Keep business logic in Services** - NOT Controllers
4. **Ensure every operation is traceable** - Stock movements, financial transactions
5. **Test core workflows** - Stock change, sale, purchase receipt, return
6. **Keep it simple** - Don't over-engineer, build for a real pharmacy first
7. **Follow Laravel conventions** - Naming, structure, patterns
8. **Document what matters** - Business rules, schema changes, important decisions

