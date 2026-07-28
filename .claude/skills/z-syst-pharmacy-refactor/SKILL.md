---
name: z-syst-pharmacy-refactor
description: "Refactor Z-Syst Pharmacy Laravel code toward service-driven stock allocation, module provider consistency, and production-ready module routing. Use when replacing legacy Stock/ProductStock writes, fixing module auto-registration, or improving pharmacy business logic separation."
argument-hint: "[task]"
license: MIT
metadata:
  author: claudekit
  version: "1.0.0"
---

# Z-Syst Pharmacy Refactor

Refactor helper for the Z-Syst Pharmacy management app. Focuses on converting legacy inventory mutations to `StockAllocationService`, enforcing thin controllers, preserving auditability, and ensuring Laravel module/provider routing works correctly.

## When to Use

- Replacing direct `Stock` or `ProductStock` writes with `StockAllocationService`
- Fixing module service provider discovery in `app/Modules`
- Verifying route loading for modular Laravel providers
- Refactoring pharmacy stock, sales, GRN, prescription, purchase, and return workflows
- Ensuring pessimistic locking and consistent audit trail for stock operations
- Cleaning up legacy stock mutation code paths in controllers and services

## Core Focus

- Keep Controllers thin: validate, delegate, return response
- Centralize stock mutation in `App\Services\Stock\StockAllocationService`
- Preserve business rules in Services, not Controllers
- Use pessimistic locking for stock allocation and release
- Maintain stock audit trail and avoid inconsistent direct model updates
- Support real pharmacy flows: purchases, GRN, sales, returns, prescriptions

## Primary Patterns

### Stock Allocation

Always prefer `StockAllocationService` over direct `Stock` / `ProductStock` mutation:

- `StockAllocationService::addToProductStock($payload)`
- `StockAllocationService::allocateToProductStock($payload)`
- `StockAllocationService::addToLegacyStock($payload)`
- `StockAllocationService::releaseFromProductStock($payload)`
- `StockAllocationService::allocate($payload)`
- `StockAllocationService::release($payload)`

Use these methods for:

- GRN receipt
- Sales and POS deductions
- Prescription dispensing
- Purchase returns and sale returns
- Stock transfers and legacy stock updates

### Service Layer

- Controllers should only orchestrate requests and responses
- Business logic should live in Service classes such as:
  - `GoodsReceivedNoteService`
  - `PrescriptionService`
  - `PurchaseOrderReturnService`
  - `SaleService`
  - `InvoiceOcrService`
- Use dependency injection and dedicated request validation classes

### Provider Auto-Discovery

For modular route/provider loading, ensure each module exposes a valid provider class in `app/Modules/{ModuleName}`. Prefer a fallback resolution strategy when module names do not match provider class names exactly.

### Route Validation

After provider fixes, verify module routes using:

```bash
php artisan route:list --name=auth
php artisan route:list --path=api
```

and clear caches when needed:

```bash
php artisan config:clear
php artisan route:clear
php artisan cache:clear
composer dump-autoload
```

## Recommended Workflow

1. Search for legacy stock model usage:
   ```bash
grep -R "new Stock\|ProductStock::\|Stock::" app/Http app/Services
```
2. Replace direct writes with `StockAllocationService` calls
3. Keep controller methods thin and move business logic to service methods
4. Clear Laravel caches and confirm routes/providers load
5. Run focused tests for stock movement and prescription flows

## Avoid

- Direct `Stock::create`, `Stock::update`, or `ProductStock::create` for write operations
- Business logic scattered across controllers
- Stale or misnamed module provider classes
- Ignoring `company_id`, `branch_id`, or warehouse scope in stock workflows
- Bypassing audit trail on inventory changes

## Example Replacement

```php
// BAD
Stock::where('id', $stockId)->decrement('quantity', $qty);

// GOOD
StockAllocationService::allocateToProductStock([
    'product_id' => $productId,
    'branch_id' => $branchId,
    'quantity' => $qty,
    'reference_type' => Sale::class,
    'reference_id' => $saleId,
]);
```

## Scope

This skill applies to the Z-Syst Pharmacy Laravel codebase, especially the following areas:

- `app/Http/Controllers/API/AcnooSaleController.php`
- `app/Http/Controllers/API/GoodsReceivedNoteController.php`
- `app/Http/Controllers/API/V1/PrescriptionController.php`
- `app/Services/PrescriptionService.php`
- `app/Services/InvoiceOcrService.php`
- `app/Services/Stock/StockAllocationService.php`
- `app/Shared/Providers/ModuleServiceProvider.php`

## When Not to Use

- Generic Laravel apps unrelated to pharmacy or inventory management
- Tasks that do not involve stock workflows, provider registration, or modular route loading
- UI-only work without backend inventory/business logic changes
