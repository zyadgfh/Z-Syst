# Main Task: Development of the Purchasing, Inventory, Suppliers, and Purchase Returns System

I want you to fully develop the purchasing system within the current project, so that it becomes a professional, integrated system that is genuinely usable in an ERP/POS — not just CRUD screens.

## ⚠️ A Very Fundamental Rule

Before writing any code:

1. Audit the entire project.
2. Understand the current Frontend, Backend, database, and APIs.
3. Understand the existing system for products, categories, inventory, suppliers, invoices, users, and permissions.
4. Inspect the current database schema and relationships.
5. Inspect how invoices are currently created, if this exists.
6. Inspect the current Barcode Scanner system.
7. Inspect the current printing system.
8. Inspect the branches, users, and permissions system.
9. Do not create a parallel system if there are existing entities or services that can be reused.
10. Maintain compatibility with the current system and do not break any existing feature.

After that, design and implement the full purchasing system.

---

## 1. Purchasing System Concept

I want a professional screen for creating a new purchase invoice.

The invoice must contain:

- An automatically generated sequential invoice number.
- Supplier.
- Branch.
- Invoice date.
- Creation time.
- The user who created the invoice.
- Items.
- Quantities.
- Purchase price.
- Discounts.
- Taxes, if the system supports them.
- Total.
- Amount paid.
- Remaining amount.
- The supplier balance resulting from the invoice.
- Invoice status.
- Notes.

---

## 2. Do Not Rely on Batch / Lot Number

Very important:

Do not make the Batch Number or Lot Number a requirement for adding an item to the purchase invoice.

The item should only be selected via:

**Method One:**

Manual item search.

Example:

The user starts typing:

`Panadol`

Matching items appear and the user selects one.

**Method Two:**

Barcode Scanner.

When a barcode is scanned:

1. Search for the item with this barcode.
2. Add it directly to the invoice.
3. If the item already exists in the invoice:
   - Do not automatically create a new line.
   - Handle it according to the current system's logic.
   - Preferably increase the quantity if appropriate.

**Method Three:**

Selecting the item from the items list.

---

## 3. Purchase Invoice Items Table Design

Each line in the purchase invoice must contain at least:

- Product ID
- Barcode
- Item name
- Unit
- Quantity
- Purchase price
- Discount
- Tax, if applicable
- Line total

Example:

| Item | Barcode | Quantity | Purchase Price | Discount | Total |
|---|---|---|---|---|---|
| Panadol Extra | 123456 | 10 | 100 | 0 | 1000 |

Do not make Batch a requirement in this table.

If the current system supports Expiry Date or Batch as optional data, do not remove them — keep them optional and do not block saving the invoice due to their absence.

---

## 4. Real-Time Totals Update

This is a fundamental point.

Whenever the user modifies any value in the invoice:

- Quantity
- Purchase price
- Discount
- Tax
- Adding an item
- Removing an item

Totals must be recalculated immediately.

At the bottom of the page, there must be a fixed, clear section containing:

**Invoice Totals**, including:

- Subtotal
- Discount
- Tax
- Grand Total
- Paid
- Remaining
- Total debt/balance due to the supplier

Example:

```
Total: 10,000 EGP
Paid: 4,000 EGP
Remaining: 6,000 EGP
```

Displayed as:

```
Total Debt: 6,000 EGP
```

---

## 5. Save Invoice Button

At the bottom of the page, there must be a clear button:

**Save Invoice**

When clicked:

Do not save the invoice directly.

Show a Confirmation Dialog.

Suggested text:

> "Do you agree to save and add invoice number [INVOICE_NUMBER] to the balance of supplier [SUPPLIER_NAME]?"

Buttons:

- Confirm
- Cancel

---

## 6. When "Cancel" Is Clicked

None of the following should occur:

- Creating the invoice.
- Updating inventory.
- Updating the supplier balance.
- Creating a financial transaction.
- Creating a final serial number.

The user returns to the invoice screen.

---

## 7. When "Confirm" Is Clicked

Execute the save operation as a single **Atomic Transaction**.

The following operations must be executed safely:

1. Create the purchase invoice.
2. Generate the Invoice Number / Serial Number.
3. Create the invoice line items.
4. Update inventory.
5. Record the stock movement.
6. Update the supplier balance.
7. Record the financial transaction, if the system supports it.
8. Record an Audit Log entry.
9. Fix/lock the user, branch, date, and time.
10. Save all required relationships.

If any part of the operation fails:

The entire operation must be rolled back.

The following must never happen:

- Saving the invoice without updating inventory.
- Updating inventory without saving the invoice.
- Increasing the supplier balance without creating the invoice.

---

## 8. Updating Inventory

When a purchase invoice is saved:

Example:

Current stock:

```
100 pcs
```

Purchase invoice:

```
20 pcs
```

After saving:

```
120 pcs
```

A Stock Movement must be recorded:

**Type:** `PURCHASE`

With:

- Product ID
- Quantity
- Previous Quantity
- New Quantity
- Reference Type
- Reference ID
- Invoice Number
- Branch ID
- User ID
- Date/Time

---

## 9. Stock by Branch

If the system supports branches:

Do not use a single global stock pool.

Stock must be tied to the branch.

Example:

```
Sohag branch: 100
Cairo branch: 50
```

If a purchase invoice for the Sohag branch adds 20:

```
Sohag = 120
Cairo = 50
```

No change occurs for the other branch.

---

## 10. Suppliers

Develop the supplier system so it is genuinely linked to purchase invoices.

A supplier must contain at least:

- Name
- Phone number
- Additional phone number
- Address
- Email
- Tax number, if applicable
- Current balance
- Notes
- Supplier status
- Creation date

---

## 11. Supplier Balance

A clear Ledger must be created for the supplier.

Do not rely solely on:

```
supplier.balance += amount
```

There must be a record of transactions.

Example — **Supplier Ledger:**

| Date | Operation | Reference | Debit | Credit | Balance |
|---|---|---|---|---|---|
| 21/08 | Purchase Invoice | PUR-000001 | 10,000 | 0 | 10,000 |
| 22/08 | Payment to Supplier | PAY-000001 | 0 | 4,000 | 6,000 |

This way, the reason for the current balance can be traced.

---

## 12. Payments to Suppliers

If the system includes financial accounts:

Add the ability to record a payment to the supplier.

Example:

```
Invoice = 10,000
Paid = 4,000
Balance = 6,000
```

A **Supplier Payment** must be recorded, and a corresponding entry created in the Supplier Ledger.

---

## 13. Purchase Returns

Create a complete module:

**Purchase Returns**

The user must be able to:

1. Select a previous purchase invoice.
2. View the invoice's items.
3. Select the item to be returned.
4. Enter the return quantity.
5. Not be allowed to return a quantity greater than the purchased/returnable quantity.
6. Calculate the return value.
7. Update inventory.
8. Update the supplier balance.
9. Record a reverse stock movement.
10. Record a Supplier Ledger transaction.

---

## 14. Return Example

Purchase invoice:

```
100 pcs
```

Returned:

```
20 pcs
```

Stock before the return:

```
150
```

After the return:

```
130
```

The supplier balance must decrease by the value of the return.

If the return value is:

```
2,000 EGP
```

And the supplier balance was:

```
10,000
```

It becomes:

```
8,000
```

---

## 15. Preventing Quantity Overrun

If the invoice contains:

```
100 pcs
```

And 30 have already been returned:

The maximum quantity that can be returned again is:

```
70
```

The user must not be allowed to enter:

```
71
```

or:

```
100
```

---

## 16. Invoice Number / Serial Number

Every purchase invoice must receive a unique sequential number.

Example:

```
PUR-000001
PUR-000002
PUR-000003
...
```

It must be:

- Unique
- Non-repeatable
- Generated by the Backend
- Safe in case two invoices are created simultaneously
- Not dependent on the Frontend
- Immutable once the invoice is confirmed

The same principle must be applied to purchase returns:

```
PR-000001
PR-000002
...
```

---

## 17. Do Not Use the Frontend to Generate the Final Number

The final invoice number must be generated by the Backend/Database.

Race conditions must be prevented.

Example:

If two users create an invoice at the exact same moment:

```
User A → PUR-000010
User B → PUR-000011
```

And NOT:

```
User A → PUR-000010
User B → PUR-000010
```

---

## 18. Printing Barcodes After Saving the Invoice

After the save completes successfully, show a dialog:

> "Invoice saved successfully."

Then:

> "Would you like to print item barcodes?"

With options:

**Option One:**

```
☑ Select All
```

Enabled by default.

**Option Two:**

Select specific items.

Such as:

```
☐ Panadol Extra
☐ Augmentin
☐ Brufen
```

The user can select:

- All items
- One item
- Two items
- Any number of items

---

## 19. Barcode Label Quantity

When items are selected, the system should know how many labels are needed.

Example:

```
Panadol
Quantity in invoice = 10
```

The default barcode label quantity can be = 10.

But allow the user to adjust the number of labels before printing.

Example:

```
Panadol:
Number of labels: 10
or: 5
```

---

## 20. Printing the Barcode

When clicking:

**Print Barcode**

Create a Print Job containing only the selected items.

It must be compatible with the current printing system in the project.

Do not create a new printing system if the project already has a Printing Service.

---

## 21. Invoice States

Create a clear State Machine for the invoice.

Such as:

```
DRAFT
CONFIRMED
CANCELLED
RETURNED_PARTIALLY
RETURNED_FULLY
```

Do not allow editing a `CONFIRMED` invoice in a way that breaks inventory or the supplier balance.

If the user needs to modify a confirmed invoice:

There must be a safe workflow:

- Authorized edit
- Or cancellation
- Or creating a return
- Depending on the nature of the system.

---

## 22. Cancelling a Purchase Invoice

Add the ability to cancel an invoice according to permissions.

Upon cancellation, the following must be reversed:

- Inventory
- Supplier Ledger
- Financial Ledger, if applicable

The cancellation operation must be recorded in the Audit Log.

---

## 23. Transaction Log

Create an Audit Log for every important operation.

Example:

```
USER: Ahmed
ACTION: CREATE_PURCHASE
REFERENCE: PUR-000123
TIME: 2026-08-21 05:30
```

Also:

- `UPDATE_PURCHASE`
- `CANCEL_PURCHASE`
- `CREATE_PURCHASE_RETURN`
- `UPDATE_STOCK`
- `UPDATE_SUPPLIER_BALANCE`
- `SUPPLIER_PAYMENT`
- `PRINT_BARCODE`

---

## 24. Permissions

Link all operations to the current permissions system.

Separate permissions must be supported, such as:

- View Purchases
- Create Purchase
- Edit Purchase
- Delete/Cancel Purchase
- View Suppliers
- Create Supplier
- Edit Supplier
- View Supplier Balance
- Create Supplier Payment
- Create Purchase Return
- Print Purchase Invoice
- Print Barcode
- Adjust Stock

Do not assume Admin is the only user.

---

## 25. Purchases List Screen

Create a page:

**Purchases**

Containing:

- Invoice number
- Supplier
- Branch
- Date
- User
- Total
- Paid
- Remaining
- Status
- Actions

**Actions:**

- View
- Print
- Edit (if allowed)
- Cancel (if allowed)
- Return
- Print Barcode
- View Supplier Ledger

Add:

- Search
- Filter by supplier
- Filter by branch
- Filter by date
- Filter by status
- Filter by invoice number

---

## 26. Invoice Details Page

When opening the invoice, display:

**Invoice Information**

- Serial Number
- Supplier
- Branch
- User
- Date
- Status

**Items**

- Product
- Barcode
- Quantity
- Purchase Price
- Discount
- Tax
- Total

**Financial Summary**

- Subtotal
- Discount
- Tax
- Grand Total
- Paid
- Remaining

**Inventory Information**

Show the stock movement resulting from the invoice.

**Supplier Information**

Show the invoice's impact on the supplier balance.

---

## 27. Barcode Search

The Barcode Scanner must be very fast.

When a barcode is scanned:

```
Barcode → Backend/API → Product → Add to Purchase
```

The following must be handled:

- Barcode not found
- Duplicate barcode
- Barcode linked to more than one product
- Inactive product
- Product not eligible for sale/purchase per its settings

And clear messages must be shown to the user.

---

## 28. Performance

Do not make every change to quantity or price trigger a heavy request to the Backend.

Use Frontend calculations for immediate UI feedback, but:

Upon saving:

The Backend is the final source of truth and must recalculate:

- Subtotal
- Discount
- Tax
- Grand Total
- Remaining
- Supplier Balance Impact

Never trust calculated values coming from the Frontend.

---

## 29. Golden Rule for Financial Calculations

Do not rely on the Grand Total coming from the Frontend.

The Backend must recalculate everything from the invoice line items.

Example:

```
quantity × purchasePrice
→ discount
→ tax
→ grandTotal
→ paid
→ remaining
```

---

## 30. Database Integrity

Add:

- Foreign Keys
- Unique Constraints
- Indexes
- Transactions
- Check Constraints where appropriate
- Decimal/NUMERIC for money instead of Float
- Proper timestamps
- Soft Delete where appropriate
- Audit fields

Do not use Float for financial values.

---

## 31. Error Prevention

Handle the following cases:

**Case 1**
Supplier does not exist.
→ Prevent saving.

**Case 2**
No items.
→ Prevent saving.

**Case 3**
Quantity = 0.
→ Prevent saving.

**Case 4**
Price less than 0.
→ Prevent saving.

**Case 5**
Paid amount greater than the total.
→ Prevent saving unless the system supports a supplier credit balance, in which case apply a clear business rule.

**Case 6**
Barcode not found.
→ Message:
> "No item was found with this barcode."

**Case 7**
An error occurred while updating inventory.
→ Full rollback.

**Case 8**
An error occurred while updating the supplier balance.
→ Full rollback.

---

## 32. UX

I want a professional and fast interface.

Focus on:

- Keyboard navigation
- Barcode-first workflow
- Search-first workflow
- Fast item addition
- Sticky invoice summary
- Sticky save button
- Responsive design
- Clear error messages
- Loading states
- Empty states
- Confirmation dialogs
- Toast notifications

---

## 33. Compatibility with the Current System

Before creating any:

- Component
- API
- Service
- Database Table
- Hook
- Utility

Check whether it already exists.

If it exists:

Reuse it or extend it.

Do not create:

```
ProductService2
```

or:

```
InventoryServiceNew
```

or:

```
SupplierServiceFinal
```

or any duplicate architecture.

---

## 34. Testing

After implementation, create tests for the critical operations.

Test:

**Purchase Creation**

Invoice:

```
10 × 100
Total: 1000
```

Verify:

- Invoice created
- Stock +10
- Supplier balance +1000
- Ledger created
- Stock movement created

**Partial Payment**

```
Total: 1000
Paid: 400
Remaining: 600
```

**Purchase Return**

```
Purchase: 10
Return: 3
Stock: -3
Supplier balance: - value of 3 units
```

**Full Return**

Return all.

The invoice must become:

```
RETURNED_FULLY
```

**Concurrent Invoices**

Test creating two invoices simultaneously.

Verify no duplicate Serial Number occurs.

---

## 35. Reports

Add basic reports:

**Purchases Report**

- Total purchases
- Number of invoices
- By supplier
- By branch
- By period
- By user

**Purchase Returns Report**

- Total returns
- By supplier
- By branch
- By period

**Supplier Balance Report**

- Opening Balance
- Purchases
- Payments
- Returns
- Adjustments
- Closing Balance

**Stock Movement Report**

Displays:

- Purchase
- Purchase Return
- Sale
- Sale Return
- Adjustment

---

## 36. Supplier Page

When opening a supplier, show a **Dashboard** for the supplier:

**Information**

- Name
- Phone
- Address
- Status

**Current Balance**

Example:

```
Supplier Balance: 12,500 EGP
```

**Transactions**

- Purchases
- Returns
- Payments
- Adjustments

**Timeline**

Show all transactions in chronological order.

---

## 37. Security

Do not allow the user to tamper with:

- Supplier ID
- Branch ID
- User ID
- Invoice Number
- Stock Quantity
- Supplier Balance
- Grand Total

from the Frontend.

All of these sensitive values must be validated on the Backend.

---

## 38. Architecture

Split the system in a clear way.

Logical example:

```
Purchasing
├── Purchase Invoice
├── Purchase Items
├── Purchase Return
├── Supplier
├── Supplier Ledger
├── Supplier Payment
├── Purchase Service
├── Purchase Return Service
└── Purchase Reports

Inventory
├── Stock
├── Stock Movement
├── Stock Service
└── Inventory Reports
```

There is no requirement to follow these names literally if the project uses a different architecture.

The most important thing is to preserve the current architecture.

---

## 39. The Most Important Rule in the System

Treat the Purchase Invoice as a **Business Transaction**, not just a record in the database.

When the invoice is confirmed:

```
Purchase Invoice
↓
Purchase Items
↓
Inventory Update
↓
Stock Movement
↓
Supplier Ledger
↓
Financial Ledger (if applicable)
↓
Audit Log
↓
Invoice Serial Number
↓
Barcode Printing Workflow
```

All of these operations must be interconnected.

---

## 40. What Is Required From You Now

Do not start writing code immediately.

Start first with:

### PHASE 1 — Codebase Audit

Audit the entire project.

Then provide me with a report containing:

1. Current architecture.
2. Current database.
3. Product model.
4. Inventory model.
5. Supplier model.
6. Existing Invoice model.
7. Existing Barcode system.
8. Existing Printing system.
9. Existing permissions.
10. Existing branch system.
11. Existing financial/accounting system.
12. What can be reused.
13. What needs modification.
14. What needs to be created from scratch.
15. Potential risks.
16. Implementation plan.

Do not change any code at this stage.

Once the audit is complete, move automatically to:

**PHASE 2 — Database & Domain Design**

Then:

**PHASE 3 — Backend**

Then:

**PHASE 4 — Frontend**

Then:

**PHASE 5 — Integration**

Then:

**PHASE 6 — Testing**

Then:

**PHASE 7 — Final QA**

---

## 41. Final Acceptance Criteria

I will not consider the task complete just because the purchases page "works."

I must be able to fully execute the following scenario:

1. Open a new purchase invoice.
2. Select the supplier.
3. Select the branch.
4. Scan a barcode.
5. Add the item.
6. Edit the quantity.
7. Edit the purchase price.
8. See the invoice total change immediately.
9. Add several items.
10. See the total debt.
11. Click Save.
12. A Confirmation Dialog appears.
13. Click Confirm.
14. A Serial Number is created.
15. The invoice is saved.
16. Inventory increases.
17. A Stock Movement is created.
18. The supplier balance increases.
19. A Supplier Ledger transaction is created.
20. A success message appears.
21. A Barcode Printing Dialog appears.
22. Select All is checked by default.
23. Ability to deselect some items.
24. Ability to select only one or two items.
25. Print the barcode.
26. Open the invoice later.
27. See all its data.
28. Create a partial return.
29. Inventory decreases.
30. Supplier balance decreases.
31. A return movement is created.
32. A Serial Number is created for the return.
33. All operations appear in the Audit Log.

---

## 42. Very Important

If, during the audit, you find that the current database design prevents implementing this system correctly, do not work around it with temporary fixes.

Identify the problem first.

Then propose a safe migration.

If there is more than one solution:

Present:

- Solution A
- Solution B
- Solution C

With:

- Advantages
- Disadvantages
- Impact on existing data
- Impact on performance
- Impact on future development

Then choose the most professional solution.

---

## Required Outcome

In the end, I want a truly integrated purchasing system linked to:

- Products
- Inventory
- Suppliers
- Supplier Ledger
- Purchases
- Purchase Returns
- Stock Movements
- Financial Transactions
- Branches
- Users
- Permissions
- Barcode
- Printing
- Audit Logs
- Reports

And the system must be:

- Reliable
- Transactional
- Auditable
- Scalable
- Secure
- Fast
- Maintainable

Do not rely on Batch/Lot Number as a core requirement for adding items or creating a purchase invoice.

**Start now with PHASE 1 — Codebase Audit.**
Do not jump directly to writing code before fully understanding the current project.
