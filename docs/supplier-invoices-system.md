# Z-Syst Pharmacy - Supplier Invoices System Documentation

## 📋 Overview

The Supplier Invoices System is a comprehensive invoice management solution for tracking supplier invoices, payments, and aging reports. It provides complete control over accounts payable with approval workflows, payment tracking, and financial reporting.

---

## 🎯 Features

### Core Features
- ✅ **Invoice Management**: Create, update, approve, reject, cancel invoices
- ✅ **Payment Tracking**: Record and track payments for each invoice
- ✅ **Auto-Generation**: Create invoices from purchases automatically
- ✅ **Approval Workflow**: Multi-step approval process
- ✅ **Aging Reports**: Track overdue invoices (30/60/90/90+ days)
- ✅ **File Upload**: Attach invoice PDFs and payment receipts
- ✅ **Payment Methods**: Cash, Bank Transfer, Check, Credit Card, Debit Card, Online
- ✅ **Status Tracking**: Pending, Approved, Partially Paid, Paid, Overdue, Cancelled, Rejected
- ✅ **Due Date Alerts**: Track invoices due soon and critically overdue
- ✅ **Statistics**: Comprehensive invoice and payment statistics

### Invoice Status Flow
```
Pending → Approved → Partially Paid → Paid
           ↓
         Overdue
           ↓
        Cancelled/Rejected
```

### Payment Status Flow
```
Pending → Approved → Completed
           ↓
        Cancelled
```

---

## 🗄️ Database Schema

### Supplier Invoices Table
```php
Schema::create('supplier_invoices', function (Blueprint $table) {
    $table->id();
    $table->foreignId('supplier_id')->nullable()->constrained('parties')->nullOnDelete();
    $table->foreignId('business_id')->constrained()->cascadeOnDelete();
    $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('purchase_id')->nullable()->constrained('purchases')->nullOnDelete();
    $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
    
    $table->string('invoice_number')->unique();
    $table->date('invoice_date');
    $table->date('due_date');
    
    $table->decimal('subtotal', 10, 2)->default(0);
    $table->decimal('tax_amount', 10, 2)->default(0);
    $table->decimal('discount_amount', 10, 2)->default(0);
    $table->decimal('total_amount', 10, 2)->default(0);
    
    $table->string('status')->default('pending');
    $table->decimal('paid_amount', 10, 2)->default(0);
    $table->decimal('balance', 10, 2)->default(0);
    
    $table->string('currency')->default('SAR');
    $table->string('payment_terms')->default('net_30');
    
    $table->text('notes')->nullable();
    $table->text('internal_notes')->nullable();
    
    $table->string('file_path')->nullable();
    $table->string('file_name')->nullable();
    $table->string('file_mime_type')->nullable();
    
    $table->timestamp('approved_at')->nullable();
    $table->timestamp('sent_at')->nullable();
    $table->timestamp('cancelled_at')->nullable();
    $table->text('cancellation_reason')->nullable();
    
    $table->boolean('is_active')->default(true);
    $table->timestamps();
    $table->softDeletes();
});
```

### Supplier Invoice Items Table
```php
Schema::create('supplier_invoice_items', function (Blueprint $table) {
    $table->id();
    $table->foreignId('supplier_invoice_id')->constrained('supplier_invoices')->cascadeOnDelete();
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    $table->foreignId('purchase_detail_id')->nullable()->constrained('purchase_details')->nullOnDelete();
    
    $table->string('description')->nullable();
    $table->integer('quantity')->default(0);
    $table->decimal('unit_price', 10, 2)->default(0);
    $table->decimal('discount', 10, 2)->default(0);
    $table->decimal('tax', 10, 2)->default(0);
    $table->decimal('total', 10, 2)->default(0);
    
    $table->string('batch_number')->nullable();
    $table->date('expiry_date')->nullable();
    
    $table->text('notes')->nullable();
    $table->timestamps();
});
```

### Supplier Invoice Payments Table
```php
Schema::create('supplier_invoice_payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('supplier_invoice_id')->constrained('supplier_invoices')->cascadeOnDelete();
    $table->foreignId('business_id')->constrained()->cascadeOnDelete();
    $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
    $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
    $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
    
    $table->string('payment_number')->unique();
    $table->date('payment_date');
    $table->string('payment_method');
    $table->string('payment_reference')->nullable();
    $table->string('bank_reference')->nullable();
    
    $table->decimal('amount', 10, 2);
    $table->string('status')->default('pending');
    $table->text('notes')->nullable();
    
    $table->timestamp('approved_at')->nullable();
    $table->string('file_path')->nullable();
    $table->string('file_name')->nullable();
    $table->string('file_mime_type')->nullable();
    
    $table->timestamps();
    $table->softDeletes();
});
```

---

## 📊 Models

### SupplierInvoice Model
**File:** `app/Models/SupplierInvoice.php`

**Key Methods:**
- `approve()`: Approve invoice
- `reject()`: Reject invoice
- `cancel()`: Cancel invoice
- `markAsPaid()`: Mark as fully paid
- `markAsPartiallyPaid()`: Mark as partially paid
- `addPayment()`: Add payment to invoice
- `calculateTotal()`: Calculate total amount
- `getPaymentPercentage()`: Get payment completion percentage
- `getDaysUntilDue()`: Get days until due date
- `isDueSoon()`: Check if due within 7 days
- `isCriticallyOverdue()`: Check if overdue by 30+ days

**Relationships:**
- `supplier()`: BelongsTo Party
- `purchase()`: BelongsTo Purchase
- `purchaseOrder()`: BelongsTo PurchaseOrder
- `items()`: HasMany SupplierInvoiceItem
- `payments()`: HasMany SupplierInvoicePayment

**Scopes:**
- `forBusiness()`: Filter by business
- `forSupplier()`: Filter by supplier
- `byStatus()`: Filter by status
- `pending()`: Pending invoices
- `overdue()`: Overdue invoices
- `unpaid()`: Unpaid invoices

### SupplierInvoiceItem Model
**File:** `app/Models/SupplierInvoiceItem.php`

**Key Methods:**
- `calculateTotal()`: Calculate item total

**Relationships:**
- `invoice()`: BelongsTo SupplierInvoice
- `product()`: BelongsTo Product
- `purchaseDetail()`: BelongsTo PurchaseDetails

### SupplierInvoicePayment Model
**File:** `app/Models/SupplierInvoicePayment.php`

**Key Methods:**
- `approve()`: Approve payment
- `complete()`: Mark as completed
- `cancel()`: Cancel payment

**Relationships:**
- `invoice()`: BelongsTo SupplierInvoice
- `createdBy()`: BelongsTo User
- `approvedBy()`: BelongsTo User

**Scopes:**
- `forBusiness()`: Filter by business
- `byStatus()`: Filter by status
- `byMethod()`: Filter by payment method
- `pending()`: Pending payments
- `approved()`: Approved payments
- `completed()`: Completed payments

---

## 🎨 Services

### SupplierInvoiceService
**File:** `app/Services/SupplierInvoiceService.php`

**Key Methods:**
- `create()`: Create new invoice
- `createFromPurchase()`: Create invoice from purchase
- `update()`: Update invoice
- `approve()`: Approve invoice
- `reject()`: Reject invoice
- `cancel()`: Cancel invoice
- `addPayment()`: Add payment to invoice
- `approvePayment()`: Approve payment
- `getByBusiness()`: Get invoices by business
- `getBySupplier()`: Get invoices by supplier
- `getPending()`: Get pending invoices
- `getOverdue()`: Get overdue invoices
- `getUnpaid()`: Get unpaid invoices
- `getStatistics()`: Get invoice statistics
- `getAgingReport()`: Get aging report
- `delete()`: Delete invoice

---

## 🌐 API Endpoints

### Admin Routes
**Prefix:** `/admin/supplier-invoices`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/supplier-invoices` | List invoices |
| POST | `/admin/supplier-invoices` | Create invoice |
| GET | `/admin/supplier-invoices/{supplierInvoice}` | Show invoice |
| PUT | `/admin/supplier-invoices/{supplierInvoice}` | Update invoice |
| DELETE | `/admin/supplier-invoices/{supplierInvoice}` | Delete invoice |
| POST | `/admin/supplier-invoices/{supplierInvoice}/approve` | Approve invoice |
| POST | `/admin/supplier-invoices/{supplierInvoice}/reject` | Reject invoice |
| POST | `/admin/supplier-invoices/{supplierInvoice}/cancel` | Cancel invoice |
| POST | `/admin/supplier-invoices/{supplierInvoice}/add-payment` | Add payment |
| POST | `/admin/supplier-invoices/payments/{paymentId}/approve` | Approve payment |
| GET | `/admin/supplier-invoices/pending` | Get pending invoices |
| GET | `/admin/supplier-invoices/overdue` | Get overdue invoices |
| GET | `/admin/supplier-invoices/unpaid` | Get unpaid invoices |
| GET | `/admin/supplier-invoices/statistics` | Get statistics |
| GET | `/admin/supplier-invoices/aging-report` | Get aging report |
| POST | `/admin/supplier-invoices/create-from-purchase` | Create from purchase |

### API Routes
**Prefix:** `/api/v1/supplier-invoices`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/supplier-invoices` | List invoices |
| POST | `/api/v1/supplier-invoices` | Create invoice |
| GET | `/api/v1/supplier-invoices/{supplierInvoice}` | Show invoice |
| PUT | `/api/v1/supplier-invoices/{supplierInvoice}` | Update invoice |
| DELETE | `/api/v1/supplier-invoices/{supplierInvoice}` | Delete invoice |
| POST | `/api/v1/supplier-invoices/{supplierInvoice}/approve` | Approve invoice |
| POST | `/api/v1/supplier-invoices/{supplierInvoice}/reject` | Reject invoice |
| POST | `/api/v1/supplier-invoices/{supplierInvoice}/cancel` | Cancel invoice |
| POST | `/api/v1/supplier-invoices/{supplierInvoice}/add-payment` | Add payment |
| POST | `/api/v1/supplier-invoices/payments/{paymentId}/approve` | Approve payment |
| GET | `/api/v1/supplier-invoices/pending` | Get pending invoices |
| GET | `/api/v1/supplier-invoices/overdue` | Get overdue invoices |
| GET | `/api/v1/supplier-invoices/unpaid` | Get unpaid invoices |
| GET | `/api/v1/supplier-invoices/statistics` | Get statistics |
| GET | `/api/v1/supplier-invoices/aging-report` | Get aging report |
| POST | `/api/v1/supplier-invoices/create-from-purchase` | Create from purchase |

---

## 📝 Request/Response Examples

### Create Invoice
```json
POST /api/v1/supplier-invoices
{
    "supplier_id": 1,
    "invoice_date": "2026-08-07",
    "due_date": "2026-09-06",
    "tax_amount": 100,
    "discount_amount": 50,
    "payment_terms": "net_30",
    "notes": "Monthly purchase invoice",
    "items": [
        {
            "product_id": 1,
            "description": "Paracetamol 500mg",
            "quantity": 100,
            "unit_price": 10.50,
            "discount": 0,
            "tax": 105,
            "batch_number": "BATCH001",
            "expiry_date": "2027-12-31"
        }
    ]
}

Response:
{
    "success": true,
    "message": "Supplier invoice created successfully",
    "data": {
        "id": 1,
        "invoice_number": "INV-2026-00001",
        "status": "pending",
        "total_amount": 1055.00,
        "paid_amount": 0,
        "balance": 1055.00,
        "payment_percentage": 0,
        "days_until_due": 30,
        "is_due_soon": false,
        "is_critically_overdue": false
    }
}
```

### Create Invoice from Purchase
```json
POST /api/v1/supplier-invoices/create-from-purchase
{
    "purchase_id": 1
}

Response:
{
    "success": true,
    "message": "Invoice created from purchase successfully",
    "data": {
        "id": 1,
        "invoice_number": "INV-2026-00001",
        "purchase_id": 1,
        "supplier_id": 1,
        "total_amount": 5000.00,
        "status": "pending"
    }
}
```

### Approve Invoice
```json
POST /api/v1/supplier-invoices/1/approve

Response:
{
    "success": true,
    "message": "Supplier invoice approved successfully",
    "data": {
        "id": 1,
        "status": "approved",
        "approved_by": 1,
        "approved_at": "2026-08-07T10:00:00Z"
    }
}
```

### Add Payment
```json
POST /api/v1/supplier-invoices/1/add-payment
{
    "payment_date": "2026-08-07",
    "payment_method": "bank_transfer",
    "payment_reference": "REF123456",
    "bank_reference": "BANK789",
    "amount": 500.00,
    "notes": "Partial payment"
}

Response:
{
    "success": true,
    "message": "Payment added successfully",
    "data": {
        "id": 1,
        "payment_number": "PAY-2026-00001",
        "amount": 500.00,
        "status": "pending",
        "payment_method": "bank_transfer"
    }
}
```

### Get Statistics
```json
GET /api/v1/supplier-invoices/statistics

Response:
{
    "success": true,
    "data": {
        "total": 25,
        "pending": 5,
        "overdue": 3,
        "unpaid": 10,
        "total_amount": 125000.00,
        "paid_amount": 75000.00,
        "balance": 50000.00
    }
}
```

### Get Aging Report
```json
GET /api/v1/supplier-invoices/aging-report

Response:
{
    "success": true,
    "data": {
        "period_30": 15000.00,
        "period_60": 20000.00,
        "period_90": 10000.00,
        "period_90_plus": 5000.00,
        "total": 50000.00
    }
}
```

---

## 🧪 Testing

### Test File
**File:** `tests/Feature/SupplierInvoiceTest.php`

**Test Coverage:**
- Invoice creation
- Invoice from purchase
- Invoice approval
- Invoice rejection
- Invoice cancellation
- Payment addition
- Full payment handling
- Payment approval
- Invoice scopes
- Invoice calculations
- Due date checks
- Number generation
- API endpoints
- Statistics
- Aging reports

### Running Tests
```bash
# Run all supplier invoice tests
php artisan test --filter SupplierInvoiceTest

# Run specific test
php artisan test --filter test_can_create_supplier_invoice
```

---

## 🔒 Security

### Business Isolation
- All invoices scoped to `business_id`
- Automatic filtering based on user's business
- Tenant-level data isolation

### Payment Tracking
- Track who approved each payment
- Timestamp for approval
- Payment count and amount tracking

### Validation
- Invoice number uniqueness
- Supplier existence validation
- Payment method validation
- File upload validation (PDF, JPG, PNG, max 10MB)

---

## 🎯 Use Cases

### 1. Create Supplier Invoice
```php
$invoice = $invoiceService->create([
    'business_id' => $businessId,
    'supplier_id' => $supplierId,
    'invoice_date' => now(),
    'due_date' => now()->addDays(30),
    'items' => [
        [
            'product_id' => $productId,
            'description' => 'Product Name',
            'quantity' => 100,
            'unit_price' => 10.50,
        ],
    ],
]);
```

### 2. Create Invoice from Purchase
```php
$purchase = Purchase::find(1);
$invoice = $invoiceService->createFromPurchase($purchase);
```

### 3. Approve Invoice
```php
$invoice = $invoiceService->approve($invoice, $userId);
```

### 4. Add Payment
```php
$payment = $invoiceService->addPayment($invoice, [
    'payment_method' => 'bank_transfer',
    'amount' => 500.00,
]);
```

### 5. Get Overdue Invoices
```php
$overdue = $invoiceService->getOverdue($businessId);
```

### 6. Get Aging Report
```php
$agingReport = $invoiceService->getAgingReport($businessId);
```

---

## 📊 Integration Points

### Purchase Integration
- Invoices can be created from purchases
- Automatic item copying from purchase details
- Supplier linking

### Purchase Order Integration
- Invoices can be linked to purchase orders
- PO tracking through invoice

### Supplier Integration
- Supplier-based invoice filtering
- Supplier performance tracking
- Aging reports per supplier

---

## 🎨 Customization

### Payment Methods
- Cash
- Bank Transfer
- Check
- Credit Card
- Debit Card
- Online

### Payment Terms
- Net 30 (default)
- Net 15
- Net 45
- Net 60
- Custom

### Currency
- SAR (default)
- USD
- EUR
- Custom

---

## 🚀 Future Enhancements

### Planned Features
- [ ] Automatic payment reminders
- [ ] Recurring invoices
- [ ] Multi-currency support
- [ ] Exchange rate integration
- [ ] Credit limit enforcement
- [ ] Payment scheduling
- [ ] Invoice templates
- [ ] Email notifications
- [ ] Bulk payment processing
- [ ] Integration with accounting software

---

## 📚 Related Documentation

- [PROJECT_RULES/08_MODULE_GUIDES.md](../PROJECT_RULES/08_MODULE_GUIDES.md)
- [PROJECT_RULES/03_CODING_STANDARDS.md](../PROJECT_RULES/03_CODING_STANDARDS.md)
- [PROJECT_RULES/04_SECURITY_RULES.md](../PROJECT_RULES/04_SECURITY_RULES.md)
- [docs/purchase-implementation-guide.md](./purchase-implementation-guide.md)

---

## 🎉 Summary

The Supplier Invoices System provides a complete solution for managing supplier invoices and payments in the Z-Syst Pharmacy Management System. It's fully integrated with the purchase system, supports comprehensive tracking, and includes detailed reporting capabilities.

**System Status:** ✅ COMPLETE  
**Documentation Version:** 1.0.0  
**Last Updated:** 2026-08-07  
**Integration Status:** FULLY INTEGRATED  
**Test Coverage:** COMPREHENSIVE
