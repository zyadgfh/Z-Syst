# Prompt — Build a Complete Inventory Management System in Z-Syst

I want you to develop and add a **complete Inventory Management Module** to the existing project.

Treat this task as a **production-grade implementation**, not as a UI-only feature.

Before writing any code:

1. Inspect the entire project.
2. Understand the current Frontend, Backend, Database, and API architecture.
3. Inspect the existing products/items system.
4. Inspect purchases, sales, returns, suppliers, and customers if they already exist.
5. Inspect how quantities and prices are currently updated.
6. Inspect branches, users, roles, and permissions.
7. Inspect the current Database Schema and relationships.
8. Do not create a parallel system or duplicate existing tables/features when suitable functionality already exists.
9. Follow the project's existing architecture, coding standards, conventions, and patterns.

After understanding the project, implement a complete, integrated inventory system.

## 1. Main Inventory Dashboard

Create a professional Inventory Dashboard showing:

- Total number of items.
- Total quantities in stock.
- Low-stock items.
- Items that reached the reorder point.
- Out-of-stock items.
- Items approaching expiry, if expiry dates are supported.
- Current inventory value.
- Inventory value at cost.
- Inventory value at selling price.
- Number of inventory movements today.
- Latest inventory movements.

Add filters for:

- Branch.
- Category.
- Supplier.
- Inventory status.
- Product/item.
- Barcode.
- Date range.

## 2. Inventory Item List

Create a professional inventory table containing at least:

- Product name.
- SKU.
- Barcode.
- Category.
- Unit.
- Branch.
- Current quantity.
- Minimum stock level.
- Reorder point.
- Reserved quantity.
- Available quantity.
- Cost price.
- Selling price.
- Inventory value.
- Inventory status.
- Last updated.

Inventory statuses:

- Available
- Low Stock
- Out of Stock
- Overstock
- Expiring Soon
- Expired

Add fast search supporting:

- Product name.
- Barcode.
- SKU.

The search must also work with a Barcode Scanner acting as keyboard input.

## 3. Inventory Item Details

When opening an inventory item, provide a complete details page containing:

### Product Information

- Product name.
- Barcode.
- SKU.
- Category.
- Unit.
- Manufacturer, if available.
- Supplier.
- Minimum stock level.
- Reorder point.
- Branch/location.

### Quantities

Clearly display:

- Current Stock
- Reserved Stock
- Available Stock
- Damaged Stock
- In-Transit Stock

The default calculation should be:

`Available Stock = Current Stock - Reserved Stock`

unless the existing project has a different established business rule.

## 4. Stock Movement Ledger

Create a complete ledger for every inventory movement.

Every movement must contain:

- Movement ID.
- Date and time.
- Product.
- Barcode.
- Branch.
- Movement type.
- Quantity before movement.
- Quantity changed.
- Quantity after movement.
- Reason.
- Related reference/document.
- User who performed the operation.

Movement types:

- Purchase
- Purchase Return
- Sale
- Sales Return
- Stock Adjustment
- Stock Transfer
- Damaged
- Expired
- Opening Balance
- Stock Count
- Manual Increase
- Manual Decrease

Do not allow historical movement records to be edited directly.

Any correction must create a new adjustment movement with the reason, user, and timestamp recorded.

## 5. Sales Integration

When a sales invoice is created/confirmed according to the existing workflow:

- Deduct the sold quantity from inventory automatically.

Example:

`Stock = 20`

`Sale = 3`

`New Stock = 17`

When a sales invoice is cancelled or a sales return is processed, restore the appropriate quantity according to the existing business rules.

Do not allow selling more than the available quantity unless the existing system supports negative inventory and that setting is enabled.

Add a setting:

`Allow Negative Stock`

Default:

`false`

## 6. Purchase Integration

When a purchase invoice is approved/posted according to the existing workflow:

- Add the purchased quantities to inventory automatically.

Example:

`Current Stock = 10`

`Purchased = 50`

`New Stock = 60`

When a purchase return is processed, deduct the returned quantity.

Do not update inventory merely when a document is created if the existing workflow separates Draft from Posted/Confirmed.

The actual inventory update must happen at the status where the document becomes operationally effective according to the current architecture.

## 7. Multi-Branch Stock Transfers

If the project supports multiple branches, implement Stock Transfers.

The user must be able to:

- Select source branch.
- Select destination branch.
- Add products.
- Specify quantities.
- Create a transfer request.
- Approve the transfer.
- Ship the transfer.
- Receive the transfer.

Transfer statuses:

- Draft
- Pending
- Approved
- In Transit
- Received
- Cancelled

Do not consider the quantity received at the destination branch until the receiving operation is completed.

## 8. Stock Count / Physical Inventory

Implement a professional Stock Count system.

The user must be able to create a stock count and choose:

- Branch.
- Category.
- All products.
- Selected products.

Display:

`System Quantity`

`Counted Quantity`

`Difference`

Example:

`System = 100`

`Counted = 96`

`Difference = -4`

After approving the stock count:

- Create a Stock Adjustment automatically instead of modifying the quantity without an audit trail.

Record:

- User who performed the count.
- User who approved it.
- Date/time.
- Reason for discrepancies.
- Quantities before and after adjustment.

## 9. Manual Stock Adjustment

Implement Stock Adjustment.

Support:

- Increase
- Decrease

Require:

- Product.
- Quantity.
- Reason.
- Branch.
- Optional notes.

Never modify inventory directly without generating a corresponding movement record.

## 10. Minimum Stock and Reorder Logic

Each product should support:

- Minimum Stock
- Reorder Point
- Reorder Quantity

Inventory status must be calculated automatically.

Example:

`Current = 5`

`Reorder Point = 10`

Status:

`Low Stock`

If:

`Current = 0`

Status:

`Out of Stock`

Create a:

`Low Stock Alerts`

section that can later be connected to notifications.

## 11. Expiry Management

If the current project supports expiry dates, make inventory capable of handling:

- Expiry Date.
- Days Until Expiry.
- Expired Quantity.
- Expiring Soon Quantity.

Add a setting:

`Expiry Warning Days`

Example:

`30 days`

Show alerts for items that will expire within the configured period.

Important:

Do not make Batch/Lot Number mandatory if the current project does not depend on batch tracking.

The inventory design must work without Batch/Lot Numbers.

If batch tracking already exists, preserve it as an optional capability rather than making it mandatory.

## 12. Barcode Support

Barcode must be a first-class part of the inventory system.

When a barcode is scanned:

1. Find the product.
2. Display its information.
3. Display the current stock.
4. Display the current branch.
5. Allow the user to perform the required operation.

Support:

- USB Barcode Scanner.
- Keyboard-based Barcode Scanner.
- Manual Barcode Entry.

## 13. Inventory Valuation

Calculate inventory value according to the valuation method already used by the project.

If no valuation method exists, design the system so it can support:

- Average Cost
- FIFO

Do not arbitrarily change the current cost calculation method.

Clearly separate:

`Quantity`

from:

`Inventory Value`

to prevent financial calculation errors.

## 14. Reports

Implement:

### Stock Report

All products and quantities.

### Stock Valuation Report

Current inventory value.

### Stock Movement Report

All movements within a selected period.

### Low Stock Report

Low-stock products.

### Out of Stock Report

Out-of-stock products.

### Expiry Report

Expiring and expired products.

### Stock Adjustment Report

All stock adjustments.

### Stock Transfer Report

All branch transfers.

### Stock Count Report

Stock count results.

Every report should support, where applicable:

- Date Range.
- Branch.
- Product.
- Category.
- Export to CSV/Excel/PDF if the existing project supports export functionality.

## 15. Permissions

Integrate the inventory system with the existing permissions system.

Create granular permissions such as:

- `inventory.view`
- `inventory.create`
- `inventory.update`
- `inventory.adjust`
- `inventory.transfer`
- `inventory.count`
- `inventory.approve`
- `inventory.reports`
- `inventory.valuation`

Do not allow every user to modify inventory.

## 16. Audit Logging

Every operation affecting inventory must be audited.

The system must answer:

- WHO
- WHAT
- WHEN
- WHERE
- WHY

Example:

```text
User: Ahmed
Action: Stock Adjustment
Product: Paracetamol
Old Quantity: 50
Adjustment: -5
New Quantity: 45
Reason: Damaged
Branch: Branch 1
Timestamp: ...
```

## 17. Database Design

Inspect the existing database schema first.

If suitable existing tables/models are available, reuse them.

If they do not exist, design a proper schema.

Depending on the existing architecture, separate concepts such as:

- Products
- Inventory
- Inventory Movements
- Stock Adjustments
- Stock Transfers
- Stock Counts

Use:

- Foreign Keys.
- Proper constraints.
- Appropriate indexes.
- Referential integrity.

Add indexes for frequently used operations such as:

- Barcode search.
- Product search.
- Branch filtering.
- Movement history.
- Date filtering.

Preserve data integrity.

## 18. Transaction Safety

This is critical.

Inventory operations must be atomic database transactions.

Example sale workflow:

1. Validate stock.
2. Create/confirm the sale.
3. Update inventory.
4. Create the inventory movement.
5. Commit.

If any step fails:

`ROLLBACK`

Never allow inconsistent states such as:

```text
Sale Created = Yes
Inventory Updated = No
```

or:

```text
Sale Created = No
Inventory Updated = Yes
```

Use the transaction mechanisms appropriate to the project's current database and technology stack.

## 19. Race Condition Prevention

Handle concurrent operations on the same inventory item.

Example:

```text
Stock = 1

User A sells 1
User B sells 1
```

The system must not incorrectly produce:

```text
Stock = -1
```

because of a race condition.

Use the appropriate concurrency-control mechanism supported by the existing database and architecture.

## 20. UX Requirements

Make the inventory interface:

- Fast.
- Clear.
- Professional.
- Responsive.
- Suitable for desktop and tablet.
- Practical for pharmacies and warehouses.
- Keyboard-friendly wherever appropriate.
- Equipped with proper toast notifications.
- Equipped with loading states.
- Equipped with empty states.
- Equipped with error states.
- Protected against duplicate submissions.

After every successful inventory update, display a clear notification at the bottom of the page explaining what happened.

Example:

> Inventory updated successfully — Current quantity: 125

## 21. Do Not Break the Existing System

This is a strict requirement.

Do not rewrite stable parts of the application without a real technical reason.

Do not delete existing data.

Do not change existing APIs in ways that break the current frontend.

Do not make destructive database changes.

If a migration is required:

- Create a safe migration.
- Preserve existing data.
- Use backward compatibility where necessary.
- Provide a safe migration/rollback strategy when supported by the project.

## 22. Testing Requirements

After implementation, test at least these scenarios:

1. Purchase a product.
2. Sell a product.
3. Process a purchase return.
4. Process a sales return.
5. Increase stock manually.
6. Decrease stock manually.
7. Perform a matching stock count.
8. Perform a stock count with a discrepancy.
9. Transfer stock between branches.
10. Attempt to sell unavailable stock.
11. Execute concurrent operations against the same inventory item.
12. Search by barcode.
13. Search by product name.
14. Trigger Low Stock.
15. Trigger Out of Stock.
16. Trigger Expired.
17. Trigger Expiring Soon.
18. Verify user permissions.
19. Verify audit logs.
20. Verify transaction rollback when a transaction step fails.

## 23. Critical Requirement — Do Not Build a Fake UI

I do **not** want:

- Mock data.
- Non-functional buttons.
- Static pages.
- Fake inventory state.
- Local state pretending to be the database.
- UI-only implementations.

The inventory system must be genuinely connected to:

- The Backend.
- The Database.
- The existing APIs/services.
- The existing business logic.

Every operation must be real and testable.

## 24. Implementation Process

Implement the feature in the following phases:

### Phase 1 — Project Analysis

Inspect the current project and architecture.

### Phase 2 — Database Analysis

Inspect the database schema and relationships.

### Phase 3 — Gap Analysis

Identify what already exists and what is missing.

### Phase 4 — Inventory Domain Design

Design the inventory domain around the existing architecture.

### Phase 5 — Database/Migrations

Implement the required schema changes safely.

### Phase 6 — Backend

Implement services, business logic, validation, transactions, and APIs.

### Phase 7 — Frontend

Implement the inventory interface and user workflows.

### Phase 8 — Purchase Integration

Connect inventory to purchases.

### Phase 9 — Sales Integration

Connect inventory to sales.

### Phase 10 — Returns

Connect inventory to purchase and sales returns.

### Phase 11 — Stock Transfers

Implement branch-to-branch transfers.

### Phase 12 — Stock Count

Implement physical inventory counting and adjustments.

### Phase 13 — Reports

Implement inventory reports and exports supported by the project.

### Phase 14 — Permissions and Audit Logs

Integrate granular permissions and auditing.

### Phase 15 — Testing

Run comprehensive functional, integration, transaction, and permission tests.

### Phase 16 — Final QA

Perform final regression testing and verify the entire inventory workflow.

After each phase, verify that the implementation works correctly before proceeding to the next phase.

## 25. Before You Start

Do not ask me questions that can be answered by inspecting the project.

First inspect:

- The source code.
- The database schema.
- Existing pages.
- Existing APIs.
- Existing services.
- Existing business logic.
- Existing permissions.
- Existing authentication.
- Existing branch management.
- Existing purchase/sales workflows.

If multiple implementation approaches are possible, choose the approach that is:

1. Most compatible with the existing architecture.
2. Safest for existing data.
3. Most maintainable.
4. Most scalable.
5. Most consistent with the current coding standards.
6. Least likely to introduce regressions.

Do not start by building the UI.

Start by understanding the architecture, database, existing business logic, and integration points.

## 26. Final Implementation Report

When the implementation is complete, provide a concise technical report containing:

- What you discovered in the project.
- What already existed.
- What you added.
- Files modified.
- Files created.
- Database migrations added.
- New APIs/services.
- New pages/components.
- New permissions.
- New business rules.
- Tests executed.
- Test results.
- Bugs discovered and fixed.
- Technical debt discovered.
- Remaining limitations.
- Any decisions that require my approval before further implementation.

**Start now by inspecting the project first, then implement the complete inventory system. Do not begin with UI development before understanding the existing architecture, database, and business logic.**
