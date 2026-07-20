
### Authentication
- كل endpoints محمية بـ `auth:sanctum`
- Header: `Authorization: Bearer {token}`
- Header: `Accept: application/json`
- Header: `X-Company-Id: {id}` (اختياري - يُستنتج من المستخدم)

### Response Format (موحد)

#### ✅ Success Response
```json
{
  "success": true,
  "message": "Product created successfully",
  "data": {
    "id": 1,
    "name": "Paracetamol 500mg",
    ...
  },
  "meta": {
    "current_page": 1,
    "last_page": 10,
    "per_page": 15,
    "total": 150
  }
}

❌ Error Response

{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "name": ["The name field is required."],
    "barcode": ["The barcode has already been taken."]
  },
  "error_code": "VALIDATION_ERROR"
}

HTTP Status Codes

Code
الاستخدام
200
GET ناجح
201
POST ناجح (إنشاء)
204
DELETE ناجح
400
Bad Request
401
Unauthorized
403
Forbidden (لا صلاحية)
404
Not Found
409
Conflict (duplicate)
422
Validation Error
429
Too Many Requests
500
Server Error

🔐 Authentication

POST   /api/v1/auth/register
POST   /api/v1/auth/login
POST   /api/v1/auth/logout
POST   /api/v1/auth/forgot-password
POST   /api/v1/auth/reset-password
POST   /api/v1/auth/email/verification
GET    /api/v1/auth/user

💊 Products

GET    /api/v1/products                    # قائمة مع فلترة وبحث
POST   /api/v1/products                    # إنشاء
GET    /api/v1/products/{id}               # عرض
PUT    /api/v1/products/{id}               # تحديث
DELETE /api/v1/products/{id}               # حذف
POST   /api/v1/products/import             # استيراد CSV
GET    /api/v1/products/export             # تصدير CSV
GET    /api/v1/products/low-stock          # منخفض المخزون
GET    /api/v1/products/expiring-soon      # قارب على الانتهاء
GET    /api/v1/products/search             # بحث سريع (POS)

📦 Categories

GET    /api/v1/categories
POST   /api/v1/categories
GET    /api/v1/categories/{id}
PUT    /api/v1/categories/{id}
DELETE /api/v1/categories/{id}

🏭 Manufacturers

GET    /api/v1/manufacturers
POST   /api/v1/manufacturers
GET    /api/v1/manufacturers/{id}
PUT    /api/v1/manufacturers/{id}
DELETE /api/v1/manufacturers/{id}

📊 Inventory

GET    /api/v1/inventory                   # مستوى المخزون
GET    /api/v1/inventory/movements         # حركات المخزون
POST   /api/v1/inventory/adjustments       # تعديل
GET    /api/v1/inventory/expiry-alerts     # تنبيهات الانتهاء
POST   /api/v1/inventory/stock-take        # جرد

🔄 Stock Transfers

GET    /api/v1/stock-transfers
POST   /api/v1/stock-transfers
GET    /api/v1/stock-transfers/{id}
POST   /api/v1/stock-transfers/{id}/approve
POST   /api/v1/stock-transfers/{id}/ship
POST   /api/v1/stock-transfers/{id}/receive
POST   /api/v1/stock-transfers/{id}/cancel

🛒 Sales / POS

POST   /api/v1/sales                       # إنشاء عملية بيع
GET    /api/v1/sales                       # قائمة المبيعات
GET    /api/v1/sales/{id}                  # تفاصيل
POST   /api/v1/sales/{id}/void             # إلغاء
GET    /api/v1/sales/daily-summary         # ملخص يومي
GET    /api/v1/sales/invoice/{id}/pdf      # تحميل الفاتورة

↩️ Sale Returns

POST   /api/v1/sale-returns
GET    /api/v1/sale-returns
GET    /api/v1/sale-returns/{id}
POST   /api/v1/sale-returns/{id}/approve

🧾 Purchases

GET    /api/v1/purchase-orders
POST   /api/v1/purchase-orders
GET    /api/v1/purchase-orders/{id}
PUT    /api/v1/purchase-orders/{id}
POST   /api/v1/purchase-orders/{id}/approve
POST   /api/v1/purchase-orders/{id}/receive

🏪 Suppliers

GET    /api/v1/suppliers
POST   /api/v1/suppliers
GET    /api/v1/suppliers/{id}
PUT    /api/v1/suppliers/{id}
DELETE /api/v1/suppliers/{id}

📋 Prescriptions

GET    /api/v1/prescriptions
POST   /api/v1/prescriptions
GET    /api/v1/prescriptions/{id}
POST   /api/v1/prescriptions/{id}/dispense
POST   /api/v1/prescriptions/{id}/cancel

👥 Customers (Patients)

GET    /api/v1/customers
POST   /api/v1/customers
GET    /api/v1/customers/{id}
PUT    /api/v1/customers/{id}
DELETE /api/v1/customers/{id}
GET    /api/v1/customers/{id}/prescriptions
GET    /api/v1/customers/{id}/sales

👨‍⚕️ Doctors

GET    /api/v1/doctors
POST   /api/v1/doctors
GET    /api/v1/doctors/{id}
PUT    /api/v1/doctors/{id}
DELETE /api/v1/doctors/{id}

🏥 Insurance

GET    /api/v1/insurance-companies
POST   /api/v1/insurance-companies
GET    /api/v1/insurance-claims
POST   /api/v1/insurance-claims
POST   /api/v1/insurance-claims/{id}/approve
POST   /api/v1/insurance-claims/{id}/reject

💰 Financial

GET    /api/v1/expenses
POST   /api/v1/expenses
GET    /api/v1/expense-categories
GET    /api/v1/cash-registers
POST   /api/v1/cash-registers/open
POST   /api/v1/cash-registers/{id}/close
GET    /api/v1/cash-registers/{id}/summary

📊 Reports

GET    /api/v1/reports/sales
GET    /api/v1/reports/inventory
GET    /api/v1/reports/purchases
GET    /api/v1/reports/profit-loss
GET    /api/v1/reports/top-products
GET    /api/v1/reports/top-customers
GET    /api/v1/reports/expiry

⚙️ Settings

GET    /api/v1/settings
PUT    /api/v1/settings
GET    /api/v1/settings/company
GET    /api/v1/settings/branch/{branchId}

🎯 أمثلة على Requests/Responses
POST /api/v1/products
Request:
{
  "name": "Paracetamol 500mg",
  "generic_name": "Paracetamol",
  "brand_name": "Panadol",
  "barcode": "6221031234567",
  "category_id": 5,
  "manufacturer_id": 3,
  "dosage_form": "tablet",
  "strength": "500mg",
  "prescription_required": false,
  "cost_price": 15.50,
  "selling_price": 22.00,
  "tax_rate": 14.0,
  "min_stock_level": 100,
  "reorder_point": 50,
  "max_stock_level": 1000
}

Response (201):

{
  "success": true,
  "message": "Product created successfully",
  "data": {
    "id": 123,
    "name": "Paracetamol 500mg",
    "generic_name": "Paracetamol",
    "brand_name": "Panadol",
    "barcode": "6221031234567",
    "category": {
      "id": 5,
      "name": "Pain Relief"
    },
    "manufacturer": {
      "id": 3,
      "name": "GSK"
    },
    "selling_price": 22.00,
    "cost_price": 15.50,
    "current_stock": 0,
    "created_at": "2026-07-20T10:30:00Z"
  }
}

POST /api/v1/sales (POS Sale)
Request:
{
  "customer_id": 45,
  "items": [
    {
      "product_id": 123,
      "quantity": 2,
      "batch_number": "B20260101"
    },
    {
      "product_id": 456,
      "quantity": 1
    }
  ],
  "discount_type": "percentage",
  "discount_amount": 5,
  "payment_method": "cash",
  "amount_paid": 100.00,
  "prescription_id": null,
  "notes": "Regular customer"
}

🔐 الصلاحيات (Permissions)

// مثال في Controller
public function store(StoreProductRequest $request)
{
    Gate::authorize('create', Product::class);
    // ...
}

📊 Rate Limiting

// في RouteServiceProvider
RateLimiter::for('api', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});

RateLimiter::for('pos', function (Request $request) {
    return Limit::perMinute(30)->by($request->user()?->id);
});


