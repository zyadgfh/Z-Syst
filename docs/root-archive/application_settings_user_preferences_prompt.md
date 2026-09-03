# Prompt — Build a Complete Application Settings & User Preferences System

I want you to develop and implement a complete **Application Settings & User Preferences** system inside the application.

This must NOT be a simple settings page with random toggles.

The goal is to build a scalable, centralized configuration system that supports settings at multiple scopes:

- System
- Organization
- Branch
- Role
- User/Employee
- Module

The system must also support inheritance, overrides, permissions, validation, caching, auditing, and actual integration with business logic.

---

## 1. Before Changing Anything

Before writing or modifying code:

1. Inspect the entire project and understand its current architecture.
2. Identify:
   - Frontend
   - Backend
   - Database
   - Authentication
   - Authorization/RBAC
   - Existing settings/preferences systems
   - API architecture
   - State management
   - Caching
   - Audit logging
3. Inspect all existing modules, including:
   - Sales
   - Purchases
   - Inventory
   - Products
   - Suppliers
   - Customers
   - Employees
   - Branches
   - Invoices
   - Reports
   - Expenses
   - Treasury/Cashier
   - Payments
   - Notifications
   - Printing
   - Barcode/Scanner
   - Security
4. If an existing Settings or Preferences system exists, extend and integrate it instead of creating a competing system.
5. Do not break existing functionality.
6. Follow the existing architecture, coding standards, UI system, naming conventions, and design system.

---

# 2. Main Objective

Build a centralized settings architecture that allows configuration by:

- Organization
- Branch
- Role
- User/Employee
- Module

Settings must follow this inheritance priority:

```text
USER
↓
ROLE
↓
BRANCH
↓
ORGANIZATION
↓
SYSTEM DEFAULT
```

The most specific configuration must always override the less specific configuration.

Example:

If:

```text
System Default:
sales.autoPrint = false

Branch:
sales.autoPrint = true

Role:
sales.autoPrint = false

User:
sales.autoPrint = true
```

The effective value for that user must be:

```text
true
```

because the User setting has the highest priority.

---

# 3. Main Settings Page

Create a professional Settings section.

Do not place every setting on one enormous page.

Organize settings into logical categories.

Required categories:

- General
- Organization
- Branches
- Users & Employees
- Roles & Permissions
- Sales
- Purchases
- Inventory
- Products
- Customers
- Suppliers
- Invoices
- Payments
- Expenses
- Treasury
- Reports
- Notifications
- Printing
- Barcode
- POS
- Security
- Audit Log

The UI must be scalable so new categories can be added later without redesigning the entire system.

---

# 4. General Settings

Include settings such as:

- Organization name
- Logo
- Currency
- Language
- Time zone
- Date format
- Time format
- Number format
- Currency format
- Decimal places
- Tax settings
- Invoice settings
- Printing settings
- Default branch
- General notification preferences

Use appropriate controls based on the data type.

---

# 5. Employee/User Settings

Administrators must be able to open:

```text
Users → Employee → Settings
```

and configure user-specific preferences.

Possible settings include:

### Personal

- Language
- Theme
- Dark mode
- Default landing page
- Default branch
- Default warehouse
- Default printer
- Default page size
- Items per page
- Table density
- Table column visibility
- Table column order
- Product display mode
- Search behavior

### Sales

- Auto-open payment dialog
- Default payment method
- Allow payment method changes
- Auto-print invoice
- Show confirmation dialogs
- Allow discount entry
- Allow returns
- POS preferences

### Purchases

- Default supplier behavior
- Default purchase workflow
- Auto-print purchase invoice
- Purchase preferences

### Inventory

- Inventory display preferences
- Barcode scanner behavior
- Stock warning display
- Inventory workflow preferences

### Printing

- Default printer
- Invoice printer
- Receipt printer
- Barcode printer
- Copies
- Paper size
- Orientation
- Print preview
- Auto print

### Barcode

- Scanner enabled
- Auto focus
- Auto submit
- Enter after scan
- Beep
- Duplicate scan prevention
- Scan timeout

### Notifications

- In-app notifications
- Email notifications
- Sound notifications
- Low stock alerts
- Sales notifications
- Purchase notifications
- Return notifications
- Payment notifications
- Security alerts

The user must only be able to change settings that their permissions allow them to change.

---

# 6. Sales Settings

Create a comprehensive Sales Settings section.

Examples:

- Allow selling unavailable products
- Allow selling below purchase cost
- Allow manual price editing
- Allow manual discounts
- Maximum discount percentage
- Discount calculation method
- Tax calculation method
- Allow credit sales
- Customer credit limit
- Allow exceeding customer credit limit
- Product search by name
- Product search by SKU
- Product search by barcode
- Auto-open payment dialog
- Default payment method
- Allow changing payment method
- Allow split payments
- Allow cash payment
- Allow Orange Cash
- Allow Vodafone Cash
- Allow Etisalat Cash
- Allow electronic payments
- Auto-print sales invoice
- Print preview
- Automatically open a new invoice after completion
- Require confirmation before deleting an invoice
- Require confirmation before cancelling an invoice
- Allow sales returns
- Allow editing previous invoices
- Maximum invoice editing age
- Maximum return age

Every setting must actually affect the relevant business logic.

---

# 7. Purchase Settings

Create a comprehensive Purchases Settings section.

Examples:

- Allow creating a supplier during purchase creation
- Allow editing purchase price
- Allow editing quantity
- Allow purchase discounts
- Allow purchase taxes
- Cost calculation method
- Automatically update product cost
- Automatically update selling price
- Allow adding new products during purchasing
- Allow quantity overrides
- Allow purchase without supplier
- Allow purchase returns
- Allow editing previous purchase invoices
- Auto-print purchase invoice
- Automatic purchase invoice numbering
- Invoice numbering strategy
- Supplier payment settings
- Allow partial supplier payments
- Allow credit purchases

---

# 8. Inventory Settings

Create Inventory Settings.

Examples:

- Allow negative stock
- Low stock alert
- Low stock threshold
- Reorder point
- Automatically update inventory after sales
- Automatically update inventory after purchases
- Automatically update inventory after returns
- Allow manual inventory adjustments
- Allow stock counting
- Allow editing stock count results
- Allow inter-branch transfers
- Require transfer approval
- Show available quantity
- Show reserved quantity
- Show physical quantity
- Unit of measurement settings
- Barcode settings

Important:

Do not make Batch/Lot Number mandatory in the core product/inventory architecture.

Products must be independent entities.

Batch/Lot functionality may be supported as an optional future capability if required by the application.

---

# 9. Product Settings

Examples:

- Allow duplicate SKU
- Auto-generate SKU
- Auto-generate barcode
- Allow barcode editing
- Allow multiple barcodes per product
- Default unit
- Default category
- Default tax
- Default profit margin
- Default selling price
- Price rounding
- Show inactive products
- Allow selling inactive products

---

# 10. Invoice Settings

Create Invoice Settings.

Examples:

- Invoice prefix
- Invoice numbering strategy
- Starting invoice number
- Invoice number length
- Reset numbering per branch
- Reset numbering per year
- Show organization logo
- Show branch address
- Show branch phone
- Show tax number
- Show employee name
- Show customer name
- Show discount
- Show tax
- Show subtotal
- Show total
- Show paid amount
- Show remaining amount
- Show payment method
- Invoice notes
- Footer
- Invoice template

---

# 11. Printing Settings

Build real printer preferences.

For each user, support:

- Default printer
- Printer name
- Printer type
- Paper size
- Orientation
- Copies
- Auto print
- Print preview
- Invoice printing
- Receipt printing
- Barcode printing
- Label printing

Support at least:

- Thermal printers
- A4 printers
- Barcode printers

Allow different printers for different document types.

Example:

```text
Sales Invoice → Thermal Printer
Purchase Invoice → A4 Printer
Barcode Label → Barcode Printer
```

---

# 12. Barcode Scanner Settings

Include:

- Scanner enabled
- Scan mode
- Auto focus
- Auto submit
- Enter after scan
- Clear search after scan
- Beep
- Duplicate scan prevention
- Scan timeout

The system must support both:

```text
Manual product entry
```

and:

```text
Barcode scanner input
```

without duplicating or bypassing the core business logic.

---

# 13. Flexible Dynamic Settings Architecture

Do NOT create dozens of columns in the Users table such as:

```text
sales_auto_print
sales_allow_discount
purchase_allow_edit
inventory_negative_stock
...
```

This is not scalable.

Instead, create a centralized settings system.

A recommended conceptual structure is:

### SettingDefinition

Fields may include:

- id
- key
- module
- name
- description
- type
- defaultValue
- validationRules
- isUserConfigurable
- isRoleConfigurable
- isBranchConfigurable
- isOrganizationConfigurable
- isSystemConfigurable
- createdAt
- updatedAt

### SettingValue

Fields may include:

- id
- settingDefinitionId
- scopeType
- scopeId
- value
- createdAt
- updatedAt
- updatedBy

Supported scope types:

```text
SYSTEM
ORGANIZATION
BRANCH
ROLE
USER
```

Supported setting types should include at least:

```text
BOOLEAN
STRING
INTEGER
DECIMAL
SELECT
MULTI_SELECT
JSON
COLOR
DATE
TIME
```

Choose the exact database structure according to the existing project's architecture and ORM.

---

# 14. Central Settings Resolver

Create a centralized service responsible for resolving the effective value of a setting.

Conceptually:

```text
getSetting(key, context)
```

The resolver must search in this order:

```text
USER
↓
ROLE
↓
BRANCH
↓
ORGANIZATION
↓
SYSTEM DEFAULT
```

Example:

```text
getSetting("sales.autoPrint", currentUser)
```

If the user has a custom value, return it.

Otherwise check the user's role.

If no role value exists, check the branch.

Then organization.

Finally use the system default.

Do not duplicate this resolution logic across multiple modules.

There must be one authoritative resolver.

---

# 15. Settings Scope and Overrides

Every setting definition must declare which scopes are allowed.

For example:

```text
sales.maxDiscount
```

may be:

- System configurable
- Organization configurable
- Branch configurable
- Role configurable
- User configurable

While a security-critical system setting may only be:

- System configurable
- Organization configurable

The UI must automatically respect these capabilities.

---

# 16. Inheritance UI

When a setting is inherited, clearly show its source.

Example:

```text
Auto Print
ON

Source:
Inherited from Role
```

Or:

```text
Source:
Inherited from Branch
```

Or:

```text
Source:
Custom User Setting
```

Authorized administrators should be able to click:

```text
Override
```

to create a custom value.

---

# 17. Reset to Inherited

If a user has a custom override, provide:

```text
Reset to Inherited
```

This must delete the user-specific override and restore the normal inheritance chain:

```text
ROLE
→ BRANCH
→ ORGANIZATION
→ SYSTEM DEFAULT
```

Do not store unnecessary duplicate values.

---

# 18. Role Settings

Inside:

```text
Roles → Role → Settings
```

administrators should be able to define default settings for all users assigned to that role.

Example:

```text
Role: Cashier

sales.allowDiscount = true
sales.maxDiscount = 5
sales.allowCredit = false
printing.autoPrint = true
```

All users assigned to the role inherit these values unless they have explicit User Overrides.

---

# 19. Branch Settings

Inside:

```text
Branches → Branch → Settings
```

support settings such as:

- Default printer
- Currency
- Taxes
- Invoice configuration
- Inventory behavior
- Sales configuration
- Purchase configuration
- Invoice numbering
- POS settings
- Payment settings

---

# 20. Permissions

Do not allow ordinary users to modify system-wide settings.

Create granular permissions such as:

```text
settings.view
settings.edit
settings.system.edit
settings.organization.edit
settings.branch.edit
settings.role.edit
settings.user.edit
settings.sales.edit
settings.purchases.edit
settings.inventory.edit
settings.products.edit
settings.printing.edit
settings.barcode.edit
settings.security.edit
```

Backend authorization must be enforced independently from the frontend.

Hiding a button is NOT sufficient security.

---

# 21. Settings Search

Add a global Settings search:

```text
Search settings...
```

It must search:

- Setting name
- Description
- Module
- Key
- Tags

Examples:

Searching:

```text
print
```

should return all printing-related settings.

Searching:

```text
discount
```

should return all discount-related settings.

---

# 22. Settings Categories UI

Each settings category should have:

- Title
- Description
- Search
- Relevant settings
- Save
- Reset
- Restore Defaults
- Inheritance indicator
- Validation feedback

The interface must be professional, consistent with the existing application design system, responsive, accessible, and easy to navigate.

Desktop:

Use a settings sidebar/navigation.

Mobile:

Use an appropriate tabs, accordion, drawer, or responsive navigation pattern.

Do not introduce an unrelated visual design language.

---

# 23. Notification Preferences

Create per-user notification preferences.

Support notifications such as:

- Low stock
- New sale
- Purchase
- Purchase return
- Sales return
- Payment
- Expense
- System alert
- Security alert

Possible delivery channels:

```text
In-App
Email
Sound
```

Only expose channels actually supported by the application.

---

# 24. Audit Log

Every settings change must be audited.

Record:

- User who made the change
- Setting key
- Setting name
- Old value
- New value
- Scope
- Scope ID
- Timestamp
- IP address if supported by the existing system
- Reason/comment if the application supports mandatory change reasons

Example:

```text
Admin changed:

sales.maxDiscount

From:
10

To:
15

Scope:
USER

Employee:
Ahmed
```

Do not expose sensitive values unnecessarily in audit logs.

---

# 25. Validation

Every SettingDefinition must support validation.

Examples:

```text
maxDiscount
```

must be:

```text
>= 0
<= 100
```

Copies must be:

```text
>= 1
```

Percentages must remain within valid ranges.

Select values must exist in the allowed option set.

Dates and times must use valid formats.

Do not trust frontend validation alone.

All critical validation must also happen on the backend.

---

# 26. Business Logic Integration

This is one of the most important requirements.

Settings must NOT be UI-only.

Every setting that changes application behavior must be enforced by the relevant backend/domain/business logic.

Example:

If:

```text
sales.allowDiscount = false
```

then:

- Hide/disable the discount UI when appropriate.
- More importantly, the backend must reject discount operations.

If:

```text
sales.maxDiscount = 10
```

then a 20% discount must be rejected by the backend.

If:

```text
inventory.allowNegativeStock = false
```

then the inventory service must reject operations that would produce negative stock.

The architecture must be:

```text
Settings
   ↓
Settings Resolver
   ↓
Business Rules / Domain Services
   ↓
API
   ↓
Frontend
```

not:

```text
Settings → Frontend UI only
```

---

# 27. Frontend Settings API/Service

Do not scatter hardcoded setting access throughout the frontend.

Create a centralized client-side settings service/hook.

For example:

```text
useSetting()
```

or:

```text
settings.get()
```

according to the existing architecture.

Example usage:

```text
const autoPrint = useSetting("sales.autoPrint");
const maxDiscount = useSetting("sales.maxDiscount");
```

Avoid duplicated settings logic.

---

# 28. Backend API

Create clean APIs or services according to the existing backend architecture.

Conceptually, support operations equivalent to:

```text
GET    /settings
GET    /settings/:key
PUT    /settings/:key

GET    /users/:id/settings
PUT    /users/:id/settings

GET    /roles/:id/settings
PUT    /roles/:id/settings

GET    /branches/:id/settings
PUT    /branches/:id/settings

POST   /settings/reset
GET    /settings/definitions
```

Use the project's existing API conventions instead of blindly creating these exact routes if a different convention is already established.

Every operation must enforce backend permissions.

---

# 29. Caching

Settings may be read frequently.

Implement appropriate caching where it improves performance.

Redis may be used if Redis already exists in the project or if introducing it is justified.

Support:

- Cache by relevant scope/user
- Cache invalidation
- Immediate invalidation after changes
- Safe fallback when cache is unavailable

Never allow stale settings to remain active indefinitely after a configuration change.

Do not introduce unnecessary infrastructure if the current project does not need it.

---

# 30. Default Settings Registry

Create a centralized registry/definition source for all supported settings.

Examples:

```text
sales.autoPrint
sales.allowNegativeStock
sales.allowDiscount
sales.maxDiscount
sales.defaultPaymentMethod

purchases.autoUpdateCost
purchases.allowEdit
purchases.allowReturn

inventory.allowNegativeStock
inventory.lowStockAlert
inventory.reorderPoint

printing.defaultPrinter
printing.autoPrint
printing.invoiceCopies

barcode.autoSubmit
barcode.beep
```

Keep setting keys centralized and consistent.

Do not scatter string literals throughout the project.

Use constants/enums/types where appropriate.

---

# 31. Database Design

Before changing the database:

1. Inspect the current schema.
2. Reuse existing tables where appropriate.
3. Create clean migrations.
4. Preserve existing data.
5. Avoid destructive changes.
6. Add indexes for common lookup paths.
7. Enforce uniqueness where required.
8. Add appropriate foreign keys.
9. Ensure safe deletion behavior.
10. Add default values where necessary.

Consider indexes around:

```text
settingDefinitionId
scopeType
scopeId
key
```

and any composite lookup required by the resolver.

The final schema must support efficient resolution.

---

# 32. Concurrency and Data Integrity

Handle concurrent settings updates safely.

Consider:

- Optimistic locking/versioning where appropriate
- Transaction boundaries
- Race conditions
- Cache invalidation
- Audit consistency
- Duplicate overrides

Do not allow two conflicting updates to silently corrupt the final configuration.

---

# 33. Security

Settings must follow the same security model as the rest of the application.

Never trust:

- Frontend-hidden fields
- Client-side permission checks
- Client-provided scope IDs
- Client-provided user IDs

Validate ownership/access server-side.

Prevent a user from modifying settings belonging to:

- Another organization
- Unauthorized branch
- Unauthorized role
- Unauthorized employee

Respect tenant/organization boundaries if the system is multi-tenant.

---

# 34. Performance

The settings system must be lightweight.

Avoid unnecessary database queries.

Do not load every setting in the database whenever a normal page opens.

Use:

- Efficient queries
- Appropriate indexes
- Caching where justified
- Lazy loading for large settings sections
- Scoped retrieval
- Server-side resolution

Frequently used settings should be fast to resolve.

---

# 35. Testing

Write and execute tests for:

1. User override
2. Role inheritance
3. Branch inheritance
4. Organization inheritance
5. System default fallback
6. Override precedence
7. Reset to inherited
8. Permission enforcement
9. Backend validation
10. Frontend validation
11. Cache invalidation
12. Audit logging
13. Concurrent updates
14. Invalid values
15. Unauthorized scope access
16. Multi-tenant isolation if applicable
17. Business logic enforcement

Explicitly test:

```text
USER
→ ROLE
→ BRANCH
→ ORGANIZATION
→ SYSTEM DEFAULT
```

under all important combinations.

---

# 36. Migration Safety

The migration must be backward compatible.

After deployment:

- Existing users must continue working.
- Existing modules must continue working.
- Existing data must remain intact.
- New settings must have safe defaults.
- Existing workflows must not unexpectedly change.

If an old hardcoded behavior is being replaced with a setting, initialize the default value to preserve the previous behavior.

---

# 37. UX Requirements

The Settings system should feel like a mature ERP product.

Avoid:

- Random toggles
- Huge unstructured forms
- Excessive nested dialogs
- Confusing technical terminology
- Settings with no explanation
- Settings that have no visible effect

Use:

- Clear categories
- Helpful descriptions
- Tooltips where necessary
- Search
- Inheritance indicators
- Override controls
- Reset controls
- Validation messages
- Save states
- Unsaved-change protection
- Consistent spacing
- Responsive layouts
- Accessible controls

Do not overuse animations.

Use subtle, modern micro-interactions only where they improve usability.

---

# 38. Unsaved Changes

If a settings page has unsaved changes:

- Clearly indicate the unsaved state.
- Prevent accidental navigation when appropriate.
- Provide Save.
- Provide Cancel/Discard.
- Show success/error feedback after saving.

Do not silently lose changes.

---

# 39. Restore Defaults

Support restoring settings to their default/inherited state.

Distinguish between:

```text
Reset User Override
```

and:

```text
Restore System Default
```

Do not accidentally delete higher-level configurations when resetting a lower-level override.

---

# 40. Settings Documentation

Each setting should have:

- Human-readable name
- Description
- Module
- Type
- Default value
- Allowed scopes
- Validation rules
- Optional help text

The implementation should make it possible to document new settings without redesigning the system.

---

# 41. Extensibility

The architecture must make it easy to add future settings such as:

- Loyalty
- Promotions
- Accounting
- HR
- Attendance
- Payroll
- Delivery
- E-commerce
- Online payments
- Customer displays
- Multi-warehouse
- Multi-currency
- Advanced tax rules
- Automated workflows

Do not hardcode assumptions that prevent future modules from registering settings.

---

# 42. No Fake Features

Do not create settings that only appear in the UI but do nothing.

Every implemented setting must either:

1. Have real business logic integration, or
2. Be clearly marked as a preference that only affects presentation/UX.

Do not create fake configuration controls for functionality that does not exist.

---

# 43. Existing Project Compatibility

Before implementing each setting, verify whether the corresponding functionality already exists.

For example:

If the project already has a sales discount service, integrate:

```text
sales.maxDiscount
```

into that existing service.

Do not create a second discount engine.

If the project already has a printer service, integrate printer preferences into it.

If the project already has a barcode service, integrate scanner settings into it.

Reuse existing domain services wherever possible.

---

# 44. Code Quality

Follow production-grade engineering standards.

Requirements:

- Strong typing where supported
- Clear separation of concerns
- Reusable components
- Reusable services
- Centralized constants
- Clean naming
- No duplicated business logic
- No dead code
- No unnecessary dependencies
- No insecure shortcuts
- No hardcoded secrets
- No TODO placeholders for core functionality

Do not rewrite unrelated parts of the project.

---

# 45. Dependency Management

Use the project's existing dependencies whenever possible.

Only install new packages when there is a real technical need.

Before adding a dependency:

1. Check whether the functionality already exists in the project.
2. Prefer established, maintained libraries.
3. Verify compatibility with the current stack.
4. Avoid unnecessary package bloat.

If internet access and package installation are available to the agent, it may install necessary dependencies automatically when justified.

---

# 46. Final Integration

After implementation, verify the entire flow:

```text
Admin
 ↓
Settings
 ↓
Setting Definition
 ↓
Setting Value
 ↓
Scope
 ↓
Inheritance
 ↓
Settings Resolver
 ↓
Business Logic
 ↓
API
 ↓
Frontend
```

Test real workflows, not just isolated UI components.

Examples:

### Sales

Change:

```text
sales.allowDiscount = false
```

Then attempt to create a sale with a discount.

The backend must reject it.

### Maximum Discount

Set:

```text
sales.maxDiscount = 10
```

Attempt:

```text
20%
```

The operation must fail.

### User Override

Set:

```text
Role maxDiscount = 5
User maxDiscount = 10
```

The user must receive:

```text
10
```

### Reset

Reset the user override.

The user must return to:

```text
5
```

### Branch

Set a branch-specific printer.

Users in that branch should inherit it unless they have a user-specific printer.

---

# 47. Final QA

After implementation:

1. Inspect all modified files.
2. Run linting.
3. Run type checking.
4. Run unit tests.
5. Run integration tests.
6. Run end-to-end tests where available.
7. Check database migrations.
8. Check API authorization.
9. Check business logic enforcement.
10. Check cache invalidation.
11. Check audit logging.
12. Check responsive UI.
13. Check accessibility.
14. Check loading/error/empty states.
15. Check all major existing modules.
16. Fix all errors you encounter.
17. Do not leave TODOs or placeholders for core functionality.
18. Do not claim completion if a required component is only mocked.

---

# 48. Final Deliverable

The task is NOT complete merely because a Settings page exists.

Consider the feature complete only when the following are implemented and integrated:

```text
Settings UI
+
Setting Definitions
+
Setting Values
+
System Scope
+
Organization Scope
+
Branch Scope
+
Role Scope
+
User Scope
+
Inheritance
+
Overrides
+
Central Resolver
+
Permissions
+
Backend Validation
+
Frontend Integration
+
Business Logic Integration
+
Caching
+
Audit Log
+
Reset / Inheritance
+
Testing
+
Documentation
```

At the end, provide a concise implementation report containing:

- What was implemented
- Files modified
- Database changes
- New migrations
- New APIs/services
- New settings
- New permissions
- New UI components
- Business logic integrations
- Tests executed
- Validation results
- Any remaining issues
- Any future settings recommended

Most importantly:

**Do not merely tell me what should be done. Implement the complete feature inside the existing project.**
