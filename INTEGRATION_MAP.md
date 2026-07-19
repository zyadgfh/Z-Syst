# INTEGRATION_MAP

## 1. Overview

هذا الملف يوثق التكامل بين صفحات الواجهة الحالية وخدمات الـ backend الموجودة في مشروع Z-Syst Pharmacy.
المحتوى يركز على المسارات الحالية، خدمات الـ API، كائنات Domain الأساسية، ونقاط الربط التي يمكن أن تُبنى عليها التكاملات الحقيقية.

## 2. Web Pages ↔ Backend Services

### 2.1 صفحات الصيدلية الحالية

- `/pharmacy-dashboard`
  - Controller: `App\Http\Controllers\DashboardController`
  - View: `resources/views/pharmacy-dashboard.blade.php`
  - Data sources:
    - `Medicine::count()`
    - `Supplier::count()`
    - `Customer::count()`
    - `PurchaseOrder::count()`
  - Integration intent: واجهة KPI Dashboard تربط Sales / Inventory / Purchases / Customers.

- `/pharmacy/medicines`
  - Controller: `App\Http\Controllers\MedicineController`
  - View: `resources/views/pharmacy-medicines.blade.php`
  - Routes:
    - `GET /pharmacy/medicines`
    - `GET /pharmacy/medicines/create`
    - `POST /pharmacy/medicines`
    - `GET /pharmacy/medicines/{id}`
    - `GET /pharmacy/medicines/{id}/edit`
    - `PUT /pharmacy/medicines/{id}`
    - `DELETE /pharmacy/medicines/{id}`
    - `GET /pharmacy/medicines/reports`
    - `GET /pharmacy/medicines/search`
  - Integration intent: CRUD دوائي مع اتصال مباشر لتقارير وإدارة المخزون والأسعار.

- `/pharmacy/sales`
  - View: `resources/views/pharmacy-sales.blade.php`
  - Integration intent: صفحة مبيعات عرضية تعتمد على بيانات فواتير المبيعات.

- `/pharmacy/purchases`
  - View: `resources/views/pharmacy-purchases.blade.php`
  - Integration intent: عرض طلبات المشتريات والموردين.

- `/pharmacy/pos`
  - View: `resources/views/pharmacy-pos.blade.php`
  - Integration intent: نقطة بيع تفاعلية تحتاج ربطاً فوريًا بالمخزون والأسعار والعملاء.

- `/pharmacy/barcode-scanner`
  - View: `resources/views/barcode-scanner.blade.php`
  - Integration intent: قراءة باركود لتحديث سلة البيع أو البحث في المنتجات.

- `/pharmacy/operations`
  - View: `resources/views/pharmacy-operations.blade.php`
  - Integration intent: مركز عمليات الصيدلية يربط بين الوظائف الرئيسية.

## 3. API Services ↔ Page Consumption

### 3.1 Pharmacy API Endpoints

- `GET /api/v1/medicines` → `App\Http\Controllers\MedicineController@index`
- `POST /api/v1/medicines` → `App\Http\Controllers\MedicineController@store`
- `GET /api/v1/suppliers` → `App\Http\Controllers\SupplierController@index`
- `POST /api/v1/suppliers` → `App\Http\Controllers\SupplierController@store`
- `GET /api/v1/customers` → `App\Http\Controllers\CustomerController@index`
- `POST /api/v1/customers` → `App\Http\Controllers\CustomerController@store`
- `GET /api/v1/purchases` → `App\Http\Controllers\PurchaseController@index`
- `POST /api/v1/purchases` → `App\Http\Controllers\PurchaseController@store`
- `GET /api/v1/sales` → `App\Http\Controllers\SaleController@index`
- `POST /api/v1/sales` → `App\Http\Controllers\SaleController@store`
- `GET /api/v1/reports/stock` → `App\Http\Controllers\ReportController@stock`
- `GET /api/v1/reports/sales` → `App\Http\Controllers\ReportController@sales`

### 3.2 Auth + Tenant

- `POST /api/v1/login` → `App\Http\Controllers\API\AuthController@login`
- `POST /api/v1/register` → `App\Http\Controllers\API\AuthController@register`
- Tenant middleware: `tenant`
- Tenant-aware endpoints currently wrap pharmacy APIs in `Route::group(['middleware' => ['tenant']])`

### 3.3 Secondary services in existing backend

- `Api\StatisticsController` → dashboard summary endpoints
- `Api\ReportsController` → purchase/sales/financial reports
- `Api\AcnooProductController` → product inventory / stock operations
- `Api\AcnooSaleController` → POS sale workflows
- `API\V1\PrescriptionController` → prescription, checkout, dispense
- `API\V1\PurchaseOrderController` → orders workflow
- `API\V1\NotificationController` → notifications

## 4. Existing Integration Points

### 4.1 Shared navigation

- `resources/views/components/pharmacy-nav.blade.php`
- Included in:
  - `pharmacy-dashboard.blade.php`
  - `pharmacy-medicines.blade.php`
  - `pharmacy-sales.blade.php`
  - `pharmacy-purchases.blade.php`

### 4.2 Dashboard counts

- `App\Http\Controllers\DashboardController@index`
  - Counts medicines, suppliers, customers, purchase orders
  - Uses tenant-aware queries when possible

### 4.3 Report endpoints

- `App\Http\Controllers\ReportController@stock`
  - Returns stock list and low-stock summary
- `App\Http\Controllers\ReportController@sales`
  - Returns last invoices data

## 5. Domain Entities & Services

### 5.1 Core models

- `App\Models\Medicine`
- `App\Models\Supplier`
- `App\Models\Customer`
- `App\Models\PurchaseOrder`
- `App\Models\Sale`
- `App\Models\Purchase`
- `App\Models\SaleInvoice` (used in some controllers)

### 5.2 Service classes with integration potential

- `App\Services\ProductService`
- `App\Services\AnalyticsService`
- `App\Services\Invoice\InvoiceNotificationService`
- `App\Services\Invoice\InvoicePDFGenerator`
- `App\Services\Invoice\InvoiceImageGenerator`
- `App\Services\TenantManager`

## 6. Current Integration Gaps

### 6.1 Data flow gaps

- POS page is largely static in the frontend and not integrated with `/api/v1/sales` or inventory services.
- Purchase and sales pages currently render hard-coded demo rows instead of calling live API.
- Dashboard page count data is partial and not yet aggregated by realtime or event updates.
- Pharmacy medicine UI is separated from inventory movement and stock adjustments.

### 6.2 Service orchestration gaps

- No event-driven listeners are wired from Sales → Inventory → Reporting → Notifications.
- No Saga/orchestration layer currently coordinates sale creation, payment, stock deduction, and notification emission.
- Tenant isolation is present in route middleware, but integration logic still assumes `tenant.company_id` binding in some places.

### 6.3 Real-time gaps

- No broadcast channels currently defined for pharmacy domain events.
- No frontend realtime subscription layer exists in current blade views.
- Notifications and dashboard refresh rely on manual refresh rather than push updates.

## 7. Integration Map — Proposed Core Flows

### 7.1 Sale creation flow

Page:
- `/pharmacy/pos` → user submits sale

APIs:
- `POST /api/v1/sales`
- `GET /api/v1/medicines` for stock lookup
- `GET /api/v1/customers` for customer selection

Services:
- `SaleController@store`
- `SaleService` or `CreateSaleSaga`
- `InventoryService` for stock deduction
- `AnalyticsService` for dashboard KPI updates
- `InvoiceNotificationService` for sending invoice notifications

Events:
- `SaleCreated`
- `StockDeducted`
- `LowStockDetected`
- `InvoiceSent`

Real-time subscribers:
- Dashboard
- Inventory page
- Notification center
- POS summary panel

### 7.2 Purchase order / stock receive flow

Page:
- `/pharmacy/purchases`

APIs:
- `POST /api/v1/purchases`
- `GET /api/v1/suppliers`
- `GET /api/v1/medicines`

Services:
- `PurchaseController@store`
- `PurchaseOrderService`
- `InventoryService::addStock()`
- `AnalyticsService` updates

Events:
- `PurchaseOrderCreated`
- `StockReceived`
- `PurchaseApproved`

Real-time subscribers:
- Dashboard low-stock widget
- Inventory page
- Purchase order status panels

### 7.3 Medicine management flow

Page:
- `/pharmacy/medicines`
- `/pharmacy/medicines/create`
- `/pharmacy/medicines/{id}`

APIs:
- `GET /api/v1/medicines`
- `POST /api/v1/medicines`
- `PUT /api/v1/medicines/{id}`
- `DELETE /api/v1/medicines/{id}`

Services:
- `MedicineController`
- `ProductService`
- `InventoryService` for stock tracking

Events:
- `ProductCreated`
- `ProductUpdated`
- `ProductDeactivated`

Subscribers:
- Dashboard product KPIs
- POS product search
- Inventory reports

### 7.4 Report aggregation flow

Page:
- `resources/views/pharmacy-medicine-reports.blade.php`
- `/api/v1/reports/stock`
- `/api/v1/reports/sales`

APIs:
- ReportController stock/sales
- `Api\ReportsController` purchase/sales/financial

Services:
- `AnalyticsService`
- `ReportService`

Events:
- `SaleCreated`
- `PurchaseOrderReceived`
- `StockAdjusted`

Consumers:
- Dashboard widgets
- Admin reports pages

## 8. Recommended immediate integration layers

1. **Unified API client / hook interface**
   - All pages should call a common API wrapper for auth, tenant, and error normalization.
2. **Shared navigation + route definitions**
   - Centralize pharmacy page routes and avoid hard-coded URLs in Blade views.
3. **Event orchestration layer**
   - Define domain events for Sales, Inventory, Purchases, and Products.
4. **Realtime update scaffolding**
   - Add broadcast channel definitions and real-time invalidation hooks.
5. **Integration test map**
   - Create E2E scenarios for sale creation, purchase receipt, product update, and dashboard refresh.

## 9. Next file to create

- `INTEGRATION_ROADMAP.md` — سيناريوهات تنفيذية لكل طبقة تكامل مع مهام وأولويات.

## 10. Notes for implementation

- `tenant` middleware exists, لكنه يحتاج إلى استخدام ثابت عبر `app()->bound('tenant.company_id')` قبل استدعاء القيمة.
- الصفحة `/pharmacy/pos` هي أهم نقطة تكامل فورية؛ ربطها أولاً سيؤثر على Inventory, Sales, Dashboard, Notifications.
- صفحة `/pharmacy/medicines` تمتلك بالفعل واجهة عمليات CRUD، لذا يجب دمجها مع `ProductService` و `InventoryService`.
- التقارير موجودة في موضعين: `ReportController` و `Api\ReportsController`، لذا يجب توحيدها لاحقًا في واجهة واحدة.
- إن وجود `App\Models\SaleInvoice` بداخل تقارير Sales يشير إلى أن بعض النماذج قد تحتاج تطويعاً أو إعادة تسميتها لتتماشى مع خطوط المنتجات الحالية.

---

> تم إنشاء هذا الملف كمخرَج أولي للتكامل. الخطوة التالية هي بناء `INTEGRATION_ROADMAP.md` وبدء تنفيذ `Unified API Client` و `Event Orchestration`.
