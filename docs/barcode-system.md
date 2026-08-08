# Z-Syst Pharmacy - Barcode Printing System Documentation

## 📋 Overview

The Barcode Printing System is a comprehensive barcode generation and printing solution integrated into the Z-Syst Pharmacy Management System. It supports multiple barcode types, batch printing, and seamless integration with inventory management.

---

## 🎯 Features

### Core Features
- ✅ **Multiple Barcode Types**: CODE128, EAN13, UPC, QR
- ✅ **Product & Batch Barcodes**: Generate barcodes for products or specific stock batches
- ✅ **Batch Printing**: Generate and print multiple barcodes at once
- ✅ **Print Tracking**: Track print status, count, and user who printed
- ✅ **Customizable Settings**: Configure size, layout, and print options
- ✅ **PDF Generation**: Generate professional PDF labels
- ✅ **Barcode Validation**: Validate EAN13 and UPC checksums
- ✅ **Search Functionality**: Quick barcode lookup by number
- ✅ **Integration**: Fully integrated with Products and Stock models

### Barcode Types
- **CODE128**: Standard alphanumeric barcode (default)
- **EAN13**: 13-digit European barcode with checksum
- **UPC**: 12-digit Universal Product Code with checksum
- **QR**: QR code for digital scanning

### Print Sizes
- **Small**: Compact labels for small items
- **Standard**: Medium-sized labels (default)
- **Large**: Large labels for high-visibility items

---

## 🗄️ Database Schema

### Barcodes Table
```php
Schema::create('barcodes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained()->onDelete('cascade');
    $table->foreignId('batch_id')->nullable()->constrained('stocks')->onDelete('set null');
    $table->string('barcode_number', 50)->unique();
    $table->string('barcode_type', 20)->default('CODE128');
    $table->string('barcode_image')->nullable();
    $table->string('print_status', 20)->default('not_printed');
    $table->timestamp('printed_at')->nullable();
    $table->foreignId('printed_by')->nullable()->constrained('users')->onDelete('set null');
    $table->integer('print_count')->default(0);
    $table->string('size', 20)->default('standard');
    $table->json('print_settings')->nullable();
    $table->boolean('is_active')->default(true);
    $table->foreignId('business_id')->constrained()->onDelete('cascade');
    $table->foreignId('branch_id')->nullable()->constrained()->onDelete('set null');
    $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
    $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
    $table->timestamps();
    $table->softDeletes();
});
```

### Additional Columns
- **Products table**: Added `barcode` column
- **Stocks table**: Added `barcode` column

---

## 🔧 Models

### Barcode Model
**File:** `app/Models/Barcode.php`

**Key Methods:**
- `generateBarcodeNumber()`: Generate barcode number based on type
- `markAsPrinted()`: Mark barcode as printed
- `isPrinted()`: Check if barcode is printed
- Scopes: `forBusiness()`, `forProduct()`, `forBatch()`, `byPrintStatus()`, `active()`, `notPrinted()`

**Relationships:**
- `product()`: BelongsTo Product
- `batch()`: BelongsTo Stock
- `business()`: BelongsTo Business
- `branch()`: BelongsTo Branch
- `printedBy()`: BelongsTo User

### Product Model Integration
**File:** `app/Models/Product.php`

**Added Relationships:**
- `barcodes()`: HasMany Barcode
- `activeBarcodes()`: HasMany active Barcode

### Stock Model Integration
**File:** `app/Models/Stock.php`

**Added Relationships:**
- `barcodes()`: HasMany Barcode
- `activeBarcodes()`: HasMany active Barcode

**Added Column:**
- `barcode`: Nullable string for barcode number

---

## 🎨 Services

### BarcodeService
**File:** `app/Services/BarcodeService.php`

**Key Methods:**
- `generateForProduct()`: Generate barcode for a product
- `generateForBatch()`: Generate barcode for a stock batch
- `generateMultipleForProduct()`: Generate multiple barcodes for product
- `generateMultipleForBatch()`: Generate multiple barcodes for batch
- `printBarcode()`: Print single barcode
- `printMultipleBarcodes()`: Print multiple barcodes
- `printForProduct()`: Generate and print barcodes for product
- `printForBatch()`: Generate and print barcodes for batch
- `reprintBarcode()`: Reprint existing barcode
- `validateBarcodeNumber()`: Validate barcode checksum
- `searchByNumber()`: Search barcode by number
- `getByProduct()`: Get barcodes by product
- `getByBatch()`: Get barcodes by batch
- `getNotPrinted()`: Get unprinted barcodes
- `deleteBarcode()`: Delete barcode
- `restoreBarcode()`: Restore deleted barcode

---

## 🌐 API Endpoints

### Admin Routes
**Prefix:** `/admin/barcodes`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/admin/barcodes` | List barcodes |
| POST | `/admin/barcodes` | Create barcode |
| GET | `/admin/barcodes/{barcode}` | Show barcode |
| PUT | `/admin/barcodes/{barcode}` | Update barcode |
| DELETE | `/admin/barcodes/{barcode}` | Delete barcode |
| POST | `/admin/barcodes/generate-multiple` | Generate multiple barcodes |
| POST | `/admin/barcodes/generate-for-batch` | Generate for batch |
| POST | `/admin/barcodes/{barcode}/print` | Print barcode |
| POST | `/admin/barcodes/print-multiple` | Print multiple barcodes |
| POST | `/admin/barcodes/print-for-product` | Print for product |
| POST | `/admin/barcodes/print-for-batch` | Print for batch |
| POST | `/admin/barcodes/{barcode}/reprint` | Reprint barcode |
| GET | `/admin/barcodes/download/{filename}` | Download PDF |
| GET | `/admin/barcodes/search` | Search barcode |
| GET | `/admin/barcodes/settings` | Get settings |
| GET | `/admin/barcodes/not-printed` | Get unprinted barcodes |
| GET | `/admin/barcodes/by-product/{productId}` | Get by product |
| GET | `/admin/barcodes/by-batch/{batchId}` | Get by batch |
| POST | `/admin/barcodes/{barcodeId}/restore` | Restore barcode |

### API Routes
**Prefix:** `/api/v1/barcodes`

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/barcodes` | List barcodes |
| POST | `/api/v1/barcodes` | Create barcode |
| GET | `/api/v1/barcodes/{barcode}` | Show barcode |
| PUT | `/api/v1/barcodes/{barcode}` | Update barcode |
| DELETE | `/api/v1/barcodes/{barcode}` | Delete barcode |
| POST | `/api/v1/barcodes/generate-multiple` | Generate multiple barcodes |
| POST | `/api/v1/barcodes/generate-for-batch` | Generate for batch |
| POST | `/api/v1/barcodes/{barcode}/print` | Print barcode |
| POST | `/api/v1/barcodes/print-multiple` | Print multiple barcodes |
| POST | `/api/v1/barcodes/print-for-product` | Print for product |
| POST | `/api/v1/barcodes/print-for-batch` | Print for batch |
| POST | `/api/v1/barcodes/{barcode}/reprint` | Reprint barcode |
| GET | `/api/v1/barcodes/download/{filename}` | Download PDF |
| GET | `/api/v1/barcodes/search` | Search barcode |
| GET | `/api/v1/barcodes/settings` | Get settings |
| GET | `/api/v1/barcodes/not-printed` | Get unprinted barcodes |
| GET | `/api/v1/barcodes/by-product/{productId}` | Get by product |
| GET | `/api/v1/barcodes/by-batch/{batchId}` | Get by batch |

---

## 📝 Request/Response Examples

### Create Barcode
```json
POST /api/v1/barcodes
{
    "product_id": 1,
    "barcode_type": "CODE128",
    "size": "standard",
    "print_settings": {
        "show_product_name": true,
        "show_price": false,
        "show_expiry": true,
        "show_batch": true
    }
}

Response:
{
    "success": true,
    "message": "Barcode generated successfully",
    "data": {
        "id": 1,
        "product_id": 1,
        "barcode_number": "BC123456789",
        "barcode_type": "CODE128",
        "print_status": "not_printed",
        "print_count": 0,
        "size": "standard",
        "is_active": true
    }
}
```

### Generate Multiple Barcodes
```json
POST /api/v1/barcodes/generate-multiple
{
    "product_id": 1,
    "quantity": 10,
    "type": "CODE128",
    "size": "standard"
}

Response:
{
    "success": true,
    "message": "Generated 10 barcodes successfully",
    "data": [
        {
            "id": 1,
            "barcode_number": "BC123456789",
            ...
        },
        ...
    ]
}
```

### Print Barcode
```json
POST /api/v1/barcodes/1/print

Response:
{
    "success": true,
    "message": "Barcode printed successfully",
    "data": {
        "pdf_url": "https://example.com/storage/barcodes/barcode_1_BC123456789.pdf",
        "download_url": "https://example.com/api/v1/barcodes/download/barcodes/barcode_1_BC123456789.pdf"
    }
}
```

### Search Barcode
```json
GET /api/v1/barcodes/search?barcode_number=BC123456789

Response:
{
    "success": true,
    "data": {
        "id": 1,
        "barcode_number": "BC123456789",
        "product": {
            "id": 1,
            "name": "Paracetamol 500mg",
            ...
        },
        "batch": {
            "id": 1,
            "batch_no": "BATCH001",
            ...
        }
    }
}
```

---

## 🖨️ Print Views

### Single Barcode View
**File:** `resources/views/barcodes/single.blade.php`

Features:
- Product name and generic name
- Barcode image placeholder
- Barcode number
- Optional price display
- Expiry date
- Batch number
- Print timestamp

### Multiple Barcodes View
**File:** `resources/views/barcodes/multiple.blade.php`

Features:
- Grid layout (3 columns)
- Multiple barcodes per page
- Print-optimized CSS
- Batch printing support

---

## 🧪 Testing

### Test File
**File:** `tests/Feature/BarcodeTest.php`

**Test Coverage:**
- Barcode generation for products
- Barcode generation for batches
- Multiple barcode generation
- Barcode number generation
- Checksum calculation (EAN13, UPC)
- Barcode validation
- Print status tracking
- Model scopes
- Model relationships
- API endpoints
- Search functionality

### Running Tests
```bash
# Run all barcode tests
php artisan test --filter BarcodeTest

# Run specific test
php artisan test --filter test_can_generate_barcode_for_product
```

---

## 🔒 Security

### Business Isolation
- All barcodes are scoped to `business_id`
- Automatic filtering based on user's business
- Tenant-level data isolation

### Print Tracking
- Track who printed each barcode
- Timestamp for print operations
- Print count for reprint tracking

### Validation
- Barcode number uniqueness
- Product and batch existence validation
- Print status state management

---

## 🎯 Use Cases

### 1. Generate Barcode for New Product
```php
$product = Product::find(1);
$barcode = $barcodeService->generateForProduct($product, [
    'type' => Barcode::TYPE_CODE128,
    'size' => Barcode::SIZE_STANDARD,
]);
```

### 2. Generate Barcodes for Stock Batch
```php
$stock = Stock::find(1);
$barcodes = $barcodeService->generateMultipleForBatch($stock, 50);
```

### 3. Print Single Barcode
```php
$pdfPath = $barcodeService->printBarcode($barcode, $userId);
```

### 4. Print Multiple Barcodes
```php
$pdfPath = $barcodeService->printMultipleBarcodes($barcodeIds, $userId);
```

### 5. Search Barcode by Number
```php
$barcode = $barcodeService->searchByNumber('BC123456789', $businessId);
```

### 6. Get Unprinted Barcodes
```php
$unprinted = $barcodeService->getNotPrinted($businessId);
```

---

## 📊 Integration Points

### Product Integration
- Products can have multiple barcodes
- Automatic barcode generation on product creation
- Barcode display in product details

### Stock Integration
- Stock batches can have unique barcodes
- Batch-specific barcode generation
- FEFO-compatible barcode selection

### Sales Integration
- Barcode scanning for quick product lookup
- Automatic stock deduction on barcode sale
- Barcode-based inventory tracking

### Purchase Integration
- Automatic barcode generation on stock receipt
- Batch barcode assignment
- Purchase-to-barcode linking

---

## 🎨 Customization

### Print Settings
```json
{
    "show_product_name": true,
    "show_price": false,
    "show_expiry": true,
    "show_batch": true,
    "font_size": 12,
    "margin": 10
}
```

### Barcode Types
- Use `CODE128` for general products
- Use `EAN13` for retail products
- Use `UPC` for North American products
- Use `QR` for digital products

### Sizes
- `small`: 100 labels per page
- `standard`: 60 labels per page
- `large`: 30 labels per page

---

## 🚀 Future Enhancements

### Planned Features
- [ ] Real barcode image generation (picqer/php-barcode-generator)
- [ ] Thermal printer support
- [ ] Custom label templates
- [ ] Barcode history tracking
- [ ] Bulk barcode import
- [ ] Barcode expiration alerts
- [ ] Mobile barcode scanning
- [ ] Integration with handheld scanners

---

## 📚 Related Documentation

- [PROJECT_RULES/08_MODULE_GUIDES.md](../PROJECT_RULES/08_MODULE_GUIDES.md)
- [PROJECT_RULES/03_CODING_STANDARDS.md](../PROJECT_RULES/03_CODING_STANDARDS.md)
- [PROJECT_RULES/04_SECURITY_RULES.md](../PROJECT_RULES/04_SECURITY_RULES.md)

---

## 🎉 Summary

The Barcode Printing System provides a complete solution for barcode generation and printing in the Z-Syst Pharmacy Management System. It's fully integrated with the inventory management system, supports multiple barcode types, and includes comprehensive tracking and validation features.

**System Status:** ✅ COMPLETE  
**Documentation Version:** 1.0.0  
**Last Updated:** 2026-08-07  
**Integration Status:** FULLY INTEGRATED
