# Items / Products Management Module — Full Specification

You are working on an existing ERP / Pharmacy Management System.

Your task is to design, implement, integrate, test, and fully polish a complete "Items / Products Management" module.

**IMPORTANT:**
Do not treat this as a simple CRUD page.
The Items module is a core business domain and must be deeply integrated with Inventory, Purchases, Sales, Purchase Returns, Sales Returns, Suppliers, Customers, Pricing, Barcode Scanning, Reports, Permissions, and all related workflows.

---

## 1. First: Full Project Audit

Before changing anything:

1. Inspect the complete repository structure.
2. Identify:
   - Frontend architecture
   - Backend architecture
   - Database schema
   - ORM
   - Existing authentication
   - Existing RBAC / permissions
   - Existing inventory logic
   - Existing purchases
   - Existing sales
   - Existing returns
   - Existing supplier/customer modules
   - Existing barcode functionality
   - Existing pricing logic
   - Existing tax logic
   - Existing reporting
   - Existing UI component system
   - Existing validation system
   - Existing API/server-action/service patterns
3. Search the codebase for existing Product / Item / Medicine / Inventory / SKU / Barcode entities before creating new ones.
4. Reuse and extend existing architecture whenever possible.
5. Do NOT duplicate existing functionality.
6. Do NOT replace existing business logic unless absolutely necessary.
7. Identify all places where item/product data is already referenced.
8. Identify potential database migration conflicts.
9. Identify missing relationships.
10. Create an implementation strategy based on the actual project instead of assuming a generic architecture.

Do not start coding until you understand how the current system works.

---

## 2. Core Objective

Create a production-grade Item Management system capable of managing every product/item sold, purchased, stocked, returned, priced, scanned, reported, or referenced by the ERP.

The module must support both:

- Manual item creation
- Barcode-based item creation / lookup

**IMPORTANT:**
The system must NOT require Batch Number / Batch as the identity of the item.

An item is a master product/entity.

Batch/Lot, expiration date, cost layers, or stock movements may exist as inventory-level concepts where necessary, but the core Item entity must remain independent from a batch number.

---

## 3. Item Master Data

Create a complete Item entity.

The exact schema must be adapted to the existing database architecture.

**Recommended fields:**

### Identity
- id
- itemCode
- sku
- internalCode
- name
- scientificName
- commercialName
- shortName
- description
- notes

### Classification
- categoryId
- subcategoryId
- brandId
- manufacturerId
- itemType
- productType
- dosageForm
- routeOfAdministration
- strength
- unitType

### Barcode
- barcode
- secondaryBarcode(s)
- GTIN / EAN where applicable
- barcodeType

### Units
- baseUnit
- purchaseUnit
- salesUnit
- conversionFactor
- allowFractionalQuantity
- unitLabel

### Pricing
- purchasePrice
- costPrice
- averageCost
- lastPurchasePrice
- sellingPrice
- wholesalePrice
- retailPrice
- minimumSellingPrice
- specialPrices
- priceList support
- tax-inclusive/exclusive configuration
- discount rules

### Inventory Behavior
- trackInventory
- minimumStock
- maximumStock
- reorderPoint
- reorderQuantity
- safetyStock
- allowNegativeStock
- stockStatus
- warehouse/location behavior

### Pharmacy-Related Data (where applicable)
- activeIngredient
- concentration
- dosage
- packageSize
- packageUnit
- prescriptionRequired
- controlledItem flag
- refrigerated flag
- temperature requirements
- storage instructions

### Expiration
- trackExpiration
- minimumRemainingShelfLife
- expirationWarningDays

### Supplier
- preferredSupplier
- supplier item code
- supplier purchase price
- supplier-specific barcode if supported

### Tax
- taxCategory
- taxRate
- taxIncluded
- taxExempt

### Status
- active
- inactive
- discontinued
- archived

### Audit
- createdBy
- updatedBy
- createdAt
- updatedAt
- deletedAt where soft delete is appropriate

Do not blindly create every field above. Evaluate which ones fit the existing business model and architecture.

---

## 4. Item Creation

Implement a professional item creation workflow.

The user must be able to:

1. Create an item manually.
2. Scan a barcode.
3. Enter a barcode manually.
4. Search existing items by barcode.
5. Search by item name.
6. Search by SKU.
7. Search by internal code.
8. Automatically detect whether an item already exists.
9. Prevent accidental duplicate items.
10. Generate an internal item code when configured.
11. Validate all required fields.
12. Save the item.
13. Continue creating another item.
14. Save and immediately open the item details page.

Barcode scanning must work naturally with keyboard-based barcode scanners.

Do not assume that barcode scanning requires a camera.

If the existing project already supports hardware barcode scanners, integrate with that behavior.

---

## 5. Duplicate Prevention

Implement strong duplicate detection.

Check possible duplicates using:

- barcode
- SKU
- internal code
- normalized item name
- scientific name where applicable

The system should intelligently warn the user instead of silently creating duplicates.

However, do not make name uniqueness mandatory if the business model allows multiple items with similar names.

Provide a clear "Possible Duplicate Items" result before saving where appropriate.

---

## 6. Item List Page

Create a professional Items Management page.

**The page must include:**

- Search
- Advanced filters
- Category filter
- Brand filter
- Manufacturer filter
- Active/inactive filter
- Stock status filter
- Tax filter
- Prescription-required filter where applicable
- Expiration-related filter where applicable
- Supplier filter
- Price range
- Stock quantity range

Columns should be configurable.

**Recommended columns:**

- Item code
- SKU
- Barcode
- Name
- Category
- Brand
- Unit
- Purchase price
- Selling price
- Current stock
- Reorder point
- Status
- Supplier
- Last updated

**Support:**

- Pagination
- Sorting
- Column visibility
- Saved filters
- Export
- Bulk actions

---

## 7. Item Details Page

Create a complete item details page.

Use a structured layout with sections/tabs such as:

- Overview
- Pricing
- Inventory
- Suppliers
- Barcodes
- Units
- Taxes
- Sales History
- Purchase History
- Returns
- Stock Movements
- Expiration
- Activity / Audit Log

**Display useful KPIs:**

- Current stock
- Available stock
- Reserved stock
- Average cost
- Last purchase price
- Current selling price
- Total sold
- Total purchased
- Reorder status
- Profit margin

The information must be real and derived from the existing database.

Never display fake statistics or placeholder metrics.

---

## 8. Inventory Integration

Items must integrate with the Inventory module.

**The system must be able to determine:**

- current stock
- stock by branch
- stock by warehouse
- stock by location
- reserved quantity where supported
- available quantity
- incoming quantity
- outgoing quantity
- reorder status

Do not duplicate inventory quantities inside the Item table if the current architecture uses inventory transaction records.

Use the correct source of truth.

Every stock-changing action must be traceable.

---

## 9. Purchase Integration

Integrate Items with Purchases.

**When creating a purchase invoice:**

- Search items by name
- Search by barcode
- Scan barcode
- Select item
- Autofill item information where appropriate
- Load supplier-specific purchase information when available
- Update inventory according to the existing stock logic
- Update cost information according to the configured costing method

The Item module must not break existing purchase workflows.

---

## 10. Sales Integration

Integrate Items with Sales.

**The user must be able to:**

- Search item
- Scan barcode
- Select item
- View available stock
- Autofill price
- Apply pricing rules
- Apply discounts according to permissions
- Prevent invalid quantities
- Handle fractional units where supported

The sales system must use the Item master entity as the source for product identity.

---

## 11. Purchase Returns

Integrate Items with Purchase Returns.

The system must identify the original purchased item correctly.

**Support:**

- return quantity
- return price
- supplier
- original invoice reference
- stock adjustment
- financial adjustment
- return reason
- audit trail

---

## 12. Sales Returns

Integrate Items with Sales Returns.

**Support:**

- original sales invoice lookup
- item identification
- returned quantity
- return price
- stock restoration according to business rules
- return reason
- audit trail

---

## 13. Supplier Relationships

An item may have:

- multiple suppliers
- preferred supplier
- supplier-specific item code
- supplier-specific purchase price
- supplier-specific barcode where necessary
- last purchase information

Create the correct relational model instead of storing everything inside the Item table.

---

## 14. Categories

Build or integrate a proper category system.

**Support:**

- Category
- Subcategory
- Hierarchical categories if useful
- Active/inactive
- Category-based reporting
- Category-based filtering

Avoid hard-coding categories.

---

## 15. Brands / Manufacturers

If not already available, implement reusable:

- Brands
- Manufacturers

Each item should be able to reference them.

These should have their own management screens where appropriate.

---

## 16. Units

Implement a reliable unit-management system.

**Examples:**

- Piece
- Box
- Pack
- Bottle
- Strip
- Tablet
- Capsule
- Gram
- Kilogram
- Milliliter
- Liter

Support unit conversion where necessary.

**Example:**

- 1 Box = 10 Strips
- 1 Strip = 10 Tablets

The architecture must avoid inaccurate stock calculations when conversions exist.

Do not assume every item needs unit conversion.

---

## 17. Pricing Engine

Create a scalable pricing structure.

**Support:**

- Purchase price
- Cost price
- Retail price
- Wholesale price
- Minimum allowed selling price
- Customer-specific pricing where supported
- Branch-specific pricing where supported
- Promotional pricing where supported

Do not hard-code pricing logic into UI components.

Centralize pricing rules in the correct service/domain layer.

---

## 18. Profitability

Items should be able to expose profitability information.

**Possible calculations:**

- gross profit
- gross profit margin
- estimated margin
- purchase vs selling price difference

Use the project's existing accounting/costing method.

Do NOT invent a costing methodology if one already exists.

---

## 19. Expiration Management

If expiration tracking exists or is required:

The Item should define whether expiration tracking is enabled.

Expiration itself should not incorrectly become the identity of the Item.

Where inventory-level expiration tracking is required, keep expiration data associated with inventory records rather than duplicating it in the Item master.

**Support:**

- expiration warning threshold
- near-expiry reports
- expired stock reports
- configurable expiration behavior

---

## 20. Barcode Management

Implement advanced barcode management.

**Support:**

- primary barcode
- multiple barcodes
- barcode type
- barcode uniqueness
- scanner lookup
- manual barcode input
- barcode replacement
- barcode activation/deactivation

A barcode should resolve to the correct Item immediately.

Support multiple barcodes for the same item when business rules allow it.

---

## 21. Bulk Operations

Implement safe bulk operations.

**Examples:**

- activate/deactivate items
- change category
- change brand
- update pricing
- update tax
- update reorder point
- export items
- import items

**Bulk updates must include:**

- validation
- preview
- confirmation
- permission checking
- audit logging
- transaction safety

---

## 22. Import / Export

Create robust item import/export functionality.

Support CSV/Excel where the project architecture allows it.

**Import should:**

1. Validate file format.
2. Validate columns.
3. Normalize values.
4. Detect duplicates.
5. Validate barcodes.
6. Validate categories/brands/suppliers.
7. Show errors before committing.
8. Provide a preview.
9. Allow the user to fix errors.
10. Import using a transaction-safe process.
11. Report successful and failed rows.

Never partially corrupt the database because of a malformed import.

---

## 23. Search

Implement a fast global item search.

**Search should support:**

- name
- barcode
- SKU
- internal code
- scientific name
- manufacturer code
- supplier code

Optimize database indexes appropriately.

**Search should be usable from:**

- Items page
- Sales
- Purchases
- Returns
- Inventory
- Reports

---

## 24. Permissions

Integrate the module with the existing RBAC system.

**Create granular permissions such as:**

- items.view
- items.create
- items.update
- items.delete
- items.archive
- items.manage_prices
- items.manage_barcodes
- items.manage_categories
- items.manage_suppliers
- items.import
- items.export
- items.view_cost
- items.view_profit
- items.bulk_update

Use the project's existing permission architecture rather than creating a second permission mechanism.

Sensitive information such as cost and profit must respect permissions.

---

## 25. Audit Logging

**Track important changes:**

- item created
- item updated
- price changed
- barcode changed
- category changed
- status changed
- tax changed
- bulk update
- import
- archive/delete

**Audit logs should record:**

- who
- what
- when
- affected entity
- previous value
- new value
- source/action

---

## 26. Soft Delete / Archiving

Do not allow destructive deletion of items that are referenced by historical transactions.

**If an item has:**

- sales
- purchases
- returns
- inventory movements
- financial records

then use archive/inactive behavior instead of physically deleting the item.

Historical transactions must remain valid.

---

## 27. Database Design

Design the schema carefully.

**Possible entities may include:**

- Item
- ItemBarcode
- ItemSupplier
- ItemPrice
- ItemUnit
- Category
- Brand
- Manufacturer
- TaxCategory
- ItemInventory configuration

However: DO NOT automatically create all these tables if equivalent structures already exist.

Reuse existing domain models where possible.

**Ensure:**

- foreign keys
- indexes
- uniqueness constraints
- proper cascading behavior
- transaction safety
- soft-delete behavior
- branch/tenant isolation
- auditability

---

## 28. Multi-Branch / Multi-Tenant

The system must respect the existing tenant and branch architecture.

**Item management must never leak data between:**

- companies
- tenants
- branches
- warehouses

Use the project's current isolation model.

If items are globally shared while inventory is branch-specific, preserve that distinction correctly.

---

## 29. UI / UX

The UI must look like a premium modern ERP, not a generic AI-generated CRUD page.

**Requirements:**

- responsive
- desktop-first ERP layout
- mobile-friendly where appropriate
- keyboard-friendly workflows
- excellent tables
- professional forms
- contextual actions
- clear empty states
- loading states
- error states
- success feedback
- confirmation dialogs
- accessible components
- consistent spacing
- clear typography
- strong visual hierarchy
- polished micro-interactions
- smooth but restrained animations

Use the existing design system and component library where possible.

Do not introduce unnecessary UI libraries.

---

## 30. Performance

The module must be designed for large datasets.

**Avoid:**

- loading all items into memory
- unnecessary client-side filtering
- N+1 database queries
- duplicate requests
- unnecessary re-renders

**Use:**

- indexed database queries
- server-side pagination
- debounced search where appropriate
- efficient joins
- caching where appropriate
- virtualization for extremely large datasets if needed

---

## 31. Validation

Validation must exist on both client and server.

**Validate:**

- required fields
- barcode format
- uniqueness
- numeric values
- prices
- quantities
- unit conversions
- tax values
- category relationships
- supplier relationships

Never trust frontend validation alone.

---

## 32. Error Handling

Implement professional error handling.

**Handle:**

- duplicate barcode
- duplicate SKU
- invalid item
- deleted/inactive category
- invalid supplier
- invalid price
- invalid quantity
- database conflict
- concurrent update
- unauthorized action
- import failures
- network failures

Errors must be understandable to users and useful for developers.

---

## 33. API / Service Architecture

Follow the existing architecture.

Do not put business logic directly inside UI components.

**Create proper:**

- domain/service layer
- repositories/data access where applicable
- validation schemas
- API endpoints or server actions according to the project architecture

**Keep business rules reusable by:**

- UI
- sales
- purchases
- inventory
- import/export *(note: source document was truncated at this point)*
