# IMPLEMENTATION ROADMAP — Phase 1 (Foundation Fixes)

هذا الملف يحدد المهام التفصيلية للمرحلة الأولى مع أوامر تنفيذية للبيئة المحلية.

الأهداف الرئيسية:
- إصلاح الهجرات الأساسية وإضافة أي مفقودات
- إعداد Tenant middleware وTenantManager
- تنفيذ نظام المصادقة الكامل (AuthController + AuthService)
- إعداد Global Exception Handler بصيغة JSON
- تحضير seeders لبيئة الـ demo

خطوات التنفيذ المُقترحة (ترتيب عملي):

1) فحص المتطلبات المحلية
   - تأكد أن `php`, `composer`, `node`, `npm`/`pnpm` مثبتين

2) تثبيت الاعتمادات
   - `composer install --no-dev --optimize-autoloader`
   - `npm install` (أو `pnpm install`)

3) ضبط ملف البيئة
   - انسخ `.env.example` إلى `.env` واملأ إعدادات قاعدة البيانات
   - `php artisan key:generate`

4) تشغيل الهجرات والـ seeders
   - `php artisan migrate --force`
   - `php artisan db:seed --class=DemoSeeder`

5) تحقق سريع
   - افتح: `GET /api/v1/health`
   - سجّل الدخول باستخدام `demo@local` / `secret`

مخرجات متوقعة بعد الانتهاء:
- قاعدة بيانات مهيأة بهجرة كاملة
- حساب تجريبي جاهز (Demo Admin)
- نقاط نهاية المصادقة جاهزة للاختبار

المخاطر والملاحظات:
- بيئة Windows قد تتطلب تثبيت إضافات (PHP extensions). راجع سجل الأخطاء عند الفشل.
- إذا كانت بيئة الـ PHP غير متاحة على جهاز العمل، يمكنك تشغيل الهجرات على خادم محلي/حاوية Docker.

---
Next: شغّل سكربت الإعداد `scripts/setup-dev.ps1` أو اتبع الأوامر أعلاه محليًا.
# 🗺️ خطة التنفيذ المفصلة - Z-Syst Pharmacy Management SaaS (PharmaSync)

## 📅 الجدول الزمني الإجمالي

| المرحلة | المدة | التركيز | الحالة |
|---------|-------|---------|--------|
| 1 | 1-2 weeks | Foundation fixes, Auth, API versioning | ⏳ Pending |
| 2 | 3-4 weeks | Core pharmacy domain | ⏳ Pending |
| 3 | 2-3 weeks | Inventory & Purchasing | ⏳ Pending |
| 4 | 2-3 weeks | Sales & POS | ⏳ Pending |
| 5 | 2 weeks | Pharmacy Compliance | ⏳ Pending |
| 6 | 2-3 weeks | Advanced Financial & Accounting | ⏳ Pending |
| 7 | 2-3 weeks | CRM & Customer Management | ⏳ Pending |
| 8 | 2-3 weeks | Advanced Reporting & Analytics | ⏳ Pending |
| 9 | 2-3 weeks | Advanced Features | ⏳ Pending |
| 10 | 2-3 weeks | AI & Automation | ⏳ Pending |
| 11 | 2 weeks | Communication & Collaboration | ⏳ Pending |
| 12 | 3-4 weeks | Desktop Apps | ⏳ Pending |
| 13 | 2-3 weeks | Mobile Apps | ⏳ Pending |
| 14 | 2-3 weeks | Advanced Infrastructure | ⏳ Pending |
| 15 | 2 weeks | Testing, Documentation, DevOps | ⏳ Pending |

**المدة الإجمالية**: 42-43 أسبوع (~10-11 months)

---

## 🔴 المرحلة 1: إصلاح الأساس (Foundation Fixes)

### المدة: 1-2 weeks

### المهام:

#### 1.1 إصلاح الهجرات الأساسية المفقودة
- [ ] مراجعة جميع migrations الموجودة
- [ ] إصلاح migration conflicts (companies, branches, departments, users)
- [ ] توحيد naming conventions
- [ ] إضافة foreign keys المفقودة
- [ ] إضافة indexes المطلوبة
- [ ] إنشاء fresh migration set
- [ ] اختبار migrations على قاعدة بيانات نظيفة

**الملفات المطلوبة:**
- `database/migrations/2025_01_01_000001_fix_core_tables.php`
- `database/migrations/2025_01_01_000002_add_missing_indexes.php`
- `database/migrations/2025_01_01_000003_unify_foreign_keys.php`

#### 1.2 AuthController كامل
- [ ] Login (email/password + 2FA)
- [ ] Register (with email verification)
- [ ] Logout (single device + all devices)
- [ ] Forgot Password (send reset link)
- [ ] Reset Password (validate token + update)
- [ ] Verify Email (validate token)
- [ ] Resend Verification Email
- [ ] 2FA Setup (TOTP secret generation)
- [ ] 2FA Verify (enable/disable)
- [ ] 2FA Backup Codes (generate/regenerate)
- [ ] Refresh Token
- [ ] Barcode Login (for employees)

**الملفات المطلوبة:**
- `app/Http/Controllers/API/AuthController.php` (update)
- `app/Http/Requests/Auth/LoginRequest.php`
- `app/Http/Requests/Auth/RegisterRequest.php`
- `app/Http/Requests/Auth/ForgotPasswordRequest.php`
- `app/Http/Requests/Auth/ResetPasswordRequest.php`
- `app/Http/Requests/Auth/EmailVerificationRequest.php`
- `app/Http/Requests/Auth/ChangePasswordRequest.php`
- `app/Http/Requests/Auth/UpdateProfileRequest.php`
- `app/Http/Requests/Auth/TwoFactorSetupRequest.php`
- `app/Http/Requests/Auth/TwoFactorVerifyRequest.php`
- `app/Http/Requests/Auth/BarcodeLoginRequest.php`
- `app/Services/AuthService.php`
- `app/Services/TwoFactorService.php`

#### 1.3 Tenant Isolation Middleware
- [ ] تعزيز TenantMiddleware الحالي
- [ ] إضافة automatic company_id injection
- [ ] إضافة tenant validation
- [ ] إضافة tenant switching (for super-admins)
- [ ] إضافة tenant context logging
- [ ] اختبار isolation لكل models

**الملفات المطلوبة:**
- `app/Http/Middleware/TenantMiddleware.php` (update)
- `app/Services/TenantManager.php` (update)
- `app/Scopes/TenantScope.php` (update)
- `app/Traits/HasCompany.php` (update)

#### 1.4 API Versioning
- [ ] هيكلة routes لـ versioning (`/api/v1/`, `/api/v2/`)
- [ ] إنشاء BaseController موحد
- [ ] توحيد response format
- [ ] توحيد error handling
- [ ] إضافة version deprecation warnings
- [ ] توثيق كل version

**الملفات المطلوبة:**
- `routes/api.php` (restructure)
- `app/Http/Controllers/API/BaseController.php`
- `app/Http/Controllers/API/V1/` (move existing)
- `app/Exceptions/ApiExceptionHandler.php`

#### 1.5 Global Exception Handler
- [ ] إنشاء custom exception handler
- [ ] توحيد error responses
- [ ] إضافة detailed error logging
- [ ] إضافة stack traces (dev only)
- [ ] إضافة validation error formatting
- [ ] إضافة authentication error handling
- [ ] إضافة authorization error handling
- [ ] إضافة tenant error handling

**الملفات المطلوبة:**
- `app/Exceptions/Handler.php` (update)
- `app/Exceptions/ApiException.php`
- `app/Exceptions/ValidationException.php`
- `app/Exceptions/AuthenticationException.php`
- `app/Exceptions/AuthorizationException.php`
- `app/Exceptions/TenantException.php`

#### 1.6 Rate Limiting حسب خطة الاشتراك
- [ ] إنشاء rate limiting middleware
- [ ] ربط rate limits بـ subscription plans
- [ ] إضافة rate limit headers في responses
- [ ] إضافة rate limit exceeded responses
- [ ] إضافة rate limit monitoring
- [ ] اختبار rate limits لكل plan

**الملفات المطلوبة:**
- `app/Http/Middleware/SubscriptionRateLimit.php` (update)
- `app/Services/RateLimitService.php`
- `config/rate_limits.php`

### Acceptance Criteria:
- ✅ جميع migrations تعمل بدون errors
- ✅ Auth flow كامل يعمل (login, register, password reset, 2FA)
- ✅ Tenant isolation 100% functional
- ✅ API versioning implemented
- ✅ Consistent error responses
- ✅ Rate limiting per subscription plan

---

## 🔴 المرحلة 2: مجال الصيدلية الأساسي (Core Pharmacy Domain)

### المدة: 3-4 weeks

### المهام:

#### 2.1 Products/Medications Module
- [ ] إنشاء جداول products (مع كل الحقول المطلوبة)
- [ ] إنشاء جداول categories (هرمي)
- [ ] إنشاء جداول manufacturers
- [ ] إنشاء جداول product_variants
- [ ] إنشاء Product model مع relationships
- [ ] إنشاء Category model (nested)
- [ ] إنشاء Manufacturer model
- [ ] إنشاء ProductVariant model
- [ ] Products CRUD API
- [ ] Categories CRUD API
- [ ] Manufacturers CRUD API
- [ ] ProductVariants CRUD API
- [ ] Advanced search (name, barcode, generic, fuzzy)
- [ ] Import/Export CSV/Excel
- [ ] Barcode scanner integration
- [ ] Price history tracking
- [ ] Egyptian drug database import
- [ ] Drug interaction checker
- [ ] Price change notifications

**الملفات المطلوبة:**
- `database/migrations/2025_01_15_000001_create_products_table.php`
- `database/migrations/2025_01_15_000002_create_categories_table.php`
- `database/migrations/2025_01_15_000003_create_manufacturers_table.php`
- `database/migrations/2025_01_15_000004_create_product_variants_table.php`
- `database/migrations/2025_01_15_000005_create_product_price_history_table.php`
- `database/migrations/2025_01_15_000006_create_drug_interactions_table.php`
- `app/Models/Product.php`
- `app/Models/Category.php`
- `app/Models/Manufacturer.php`
- `app/Models/ProductVariant.php`
- `app/Models/DrugInteraction.php`
- `app/Http/Controllers/API/V1/ProductController.php`
- `app/Http/Controllers/API/V1/CategoryController.php`
- `app/Http/Controllers/API/V1/ManufacturerController.php`
- `app/Http/Requests/ProductRequest.php`
- `app/Http/Requests/CategoryRequest.php`
- `app/Http/Requests/ManufacturerRequest.php`
- `app/Services/ProductService.php`
- `app/Services/DrugInteractionService.php`
- `app/Jobs/ImportEgyptianDrugDatabase.php`

#### 2.2 Suppliers Module
- [ ] إنشاء جدول suppliers
- [ ] إنشاء Supplier model
- [ ] Suppliers CRUD API
- [ ] Supplier balance tracking
- [ ] Supplier performance metrics
- [ ] Supplier rating system

**الملفات المطلوبة:**
- `database/migrations/2025_01_20_000001_create_suppliers_table.php`
- `app/Models/Supplier.php`
- `app/Http/Controllers/API/V1/SupplierController.php`
- `app/Http/Requests/SupplierRequest.php`
- `app/Services/SupplierService.php`

#### 2.3 Customers/Patients Module
- [ ] إنشاء جدول customers/patients
- [ ] إنشاء Customer model
- [ ] Customers CRUD API
- [ ] Patient medical history (JSON)
- [ ] Allergies tracking (JSON)
- [ ] Loyalty points system
- [ ] Insurance information
- [ ] Credit limit management
- [ ] Customer segmentation

**الملفات المطلوبة:**
- `database/migrations/2025_01_25_000001_create_customers_table.php`
- `app/Models/Customer.php`
- `app/Http/Controllers/API/V1/CustomerController.php`
- `app/Http/Requests/CustomerRequest.php`
- `app/Services/CustomerService.php`
- `app/Services/LoyaltyService.php`

#### 2.4 Doctors Module
- [ ] إنشاء جدول doctors
- [ ] إنشاء Doctor model
- [ ] Doctors CRUD API
- [ ] License number validation
- [ ] Specialization tracking
- [ ] Clinic information
- [ ] Referral tracking
- [ ] Doctor performance metrics

**الملفات المطلوبة:**
- `database/migrations/2025_01_30_000001_create_doctors_table.php`
- `app/Models/Doctor.php`
- `app/Http/Controllers/API/V1/DoctorController.php`
- `app/Http/Requests/DoctorRequest.php`
- `app/Services/DoctorService.php`

### Acceptance Criteria:
- ✅ Products module كامل (CRUD, search, import/export)
- ✅ Egyptian drug database imported
- ✅ Suppliers module functional
- ✅ Customers/Patients module with medical history
- ✅ Doctors module with referral tracking
- ✅ Drug interaction checker working

---

## 🔴 المرحلة 3: المخزون والمشتريات (Inventory & Purchasing)

### المدة: 2-3 weeks

### المهام:

#### 3.1 Inventory/Stock Management
- [ ] إنشاء جدول inventory (مع batch/expiry)
- [ ] إنشاء جدول stock_movements
- [ ] إنشاء جدول stock_adjustments
- [ ] إنشاء جدول stock_transfers
- [ ] إنشاء جدول stock_takes
- [ ] Inventory model مع relationships
- [ ] StockMovement model
- [ ] StockAdjustment model
- [ ] StockTransfer model
- [ ] StockTake model
- [ ] FEFO logic implementation
- [ ] Low stock alerts
- [ ] Expiry alerts (30/60/90 days)
- [ ] Stock take workflow
- [ ] Dead stock identification
- [ ] Auto stock limits calculation
- [ ] Opening balances import
- [ ] Stock adjustment approval workflow

**الملفات المطلوبة:**
- `database/migrations/2025_02_05_000001_create_inventory_table.php`
- `database/migrations/2025_02_05_000002_create_stock_movements_table.php`
- `database/migrations/2025_02_05_000003_create_stock_adjustments_table.php`
- `database/migrations/2025_02_05_000004_create_stock_transfers_table.php`
- `database/migrations/2025_02_05_000005_create_stock_takes_table.php`
- `app/Models/Inventory.php`
- `app/Models/StockMovement.php`
- `app/Models/StockAdjustment.php`
- `app/Models/StockTransfer.php`
- `app/Models/StockTake.php`
- `app/Http/Controllers/API/V1/InventoryController.php`
- `app/Http/Controllers/API/V1/StockMovementController.php`
- `app/Http/Controllers/API/V1/StockAdjustmentController.php`
- `app/Http/Controllers/API/V1/StockTransferController.php`
- `app/Http/Controllers/API/V1/StockTakeController.php`
- `app/Services/InventoryService.php`
- `app/Services/StockMovementService.php`
- `app/Services/FEFOService.php`
- `app/Jobs/CheckLowStock.php`
- `app/Jobs/CheckExpiringProducts.php`

#### 3.2 Purchase Management
- [ ] إنشاء جدول purchase_orders
- [ ] إنشاء جدول purchase_order_items
- [ ] إنشاء جدول goods_received_notes
- [ ] إنشاء جدول grn_items
- [ ] إنشاء جدول purchase_returns
- [ ] إنشاء جدول purchase_return_items
- [ ] PurchaseOrder model
- [ ] PurchaseOrderItem model
- [ ] GoodsReceivedNote model
- [ ] GRNItem model
- [ ] PurchaseReturn model
- [ ] PurchaseReturnItem model
- [ ] PO workflow (draft → approve → send → receive)
- [ ] GRN workflow
- [ ] Purchase return workflow
- [ ] Supplier payment tracking
- [ ] AI-powered purchase suggestions
- [ ] Recurring purchase orders
- [ ] Invoice OCR integration

**الملفات المطلوبة:**
- `database/migrations/2025_02_10_000001_create_purchase_orders_table.php`
- `database/migrations/2025_02_10_000002_create_purchase_order_items_table.php`
- `database/migrations/2025_02_10_000003_create_goods_received_notes_table.php`
- `database/migrations/2025_02_10_000004_create_grn_items_table.php`
- `database/migrations/2025_02_10_000005_create_purchase_returns_table.php`
- `database/migrations/2025_02_10_000006_create_purchase_return_items_table.php`
- `app/Models/PurchaseOrder.php`
- `app/Models/PurchaseOrderItem.php`
- `app/Models/GoodsReceivedNote.php`
- `app/Models/GRNItem.php`
- `app/Models/PurchaseReturn.php`
- `app/Models/PurchaseReturnItem.php`
- `app/Http/Controllers/API/V1/PurchaseOrderController.php`
- `app/Http/Controllers/API/V1/GoodsReceivedNoteController.php`
- `app/Http/Controllers/API/V1/PurchaseReturnController.php`
- `app/Services/PurchaseOrderService.php`
- `app/Services/GRNService.php`
- `app/Services/AIPurchaseService.php`
- `app/Services/InvoiceOCRService.php`

### Acceptance Criteria:
- ✅ Inventory management كامل (FEFO, alerts, adjustments)
- ✅ Stock transfers بين الفروع
- [ ] Stock take workflow
- ✅ Purchase order workflow كامل
- ✅ GRN workflow
- ✅ Purchase returns
- ✅ AI purchase suggestions
- ✅ Invoice OCR integration

---

## 🔴 المرحلة 4: المبيعات ونقطة البيع (Sales & POS)

### المدة: 2-3 weeks

### المهام:

#### 4.1 POS System
- [ ] إنشاء جدول sales
- [ ] إنشاء جدول sale_items
- [ ] إنشاء جدول sale_payments
- [ ] إنشاء جدول sale_returns
- [ ] إنشاء جدول sale_return_items
- [ ] إنشاء جدول cash_registers
- [ ] إنشاء جدول cash_register_transactions
- [ ] Sale model
- [ ] SaleItem model
- [ ] SalePayment model
- [ ] SaleReturn model
- [ ] SaleReturnItem model
- [ ] CashRegister model
- [ ] CashRegisterTransaction model
- [ ] POS interface API (keyboard-first)
- [ ] Barcode scanning integration
- [ ] Invoice auto-generation
- [ ] Real-time stock deduction
- [ ] Multiple payment methods
- [ ] Sale returns workflow
- [ ] General sale returns (without original invoice)
- [ ] Cash register management (open/close shift)
- [ ] Hold/Park sale feature
- [ ] Receipt printing (ESC/POS)
- [ ] Offline POS support
- [ ] Dosage sheet printing
- [ ] Customer data during sale

**الملفات المطلوبة:**
- `database/migrations/2025_02_20_000001_create_sales_table.php`
- `database/migrations/2025_02_20_000002_create_sale_items_table.php`
- `database/migrations/2025_02_20_000003_create_sale_payments_table.php`
- `database/migrations/2025_02_20_000004_create_sale_returns_table.php`
- `database/migrations/2025_02_20_000005_create_sale_return_items_table.php`
- `database/migrations/2025_02_20_000006_create_cash_registers_table.php`
- `database/migrations/2025_02_20_000007_create_cash_register_transactions_table.php`
- `app/Models/Sale.php`
- `app/Models/SaleItem.php`
- `app/Models/SalePayment.php`
- `app/Models/SaleReturn.php`
- `app/Models/SaleReturnItem.php`
- `app/Models/CashRegister.php`
- `app/Models/CashRegisterTransaction.php`
- `app/Http/Controllers/API/V1/POSController.php`
- `app/Http/Controllers/API/V1/SaleController.php`
- `app/Http/Controllers/API/V1/SaleReturnController.php`
- `app/Http/Controllers/API/V1/CashRegisterController.php`
- `app/Services/POSService.php`
- `app/Services/SaleService.php`
- `app/Services/CashRegisterService.php`
- `app/Services/ReceiptPrintingService.php`

#### 4.2 Order Management
- [ ] إنشاء جدول orders
- [ ] إنشاء جدول order_items
- [ ] Order model
- [ ] OrderItem model
- [ ] Order states (pending, confirmed, preparing, out_for_delivery, delivered, cancelled)
- [ ] Delivery tracking
- [ ] Delivery management (drivers, areas)
- [ ] Order workflow API

**الملفات المطلوبة:**
- `database/migrations/2025_02_25_000001_create_orders_table.php`
- `database/migrations/2025_02_25_000002_create_order_items_table.php`
- `app/Models/Order.php`
- `app/Models/OrderItem.php`
- `app/Http/Controllers/API/V1/OrderController.php`
- `app/Services/OrderService.php`
- `app/Services/DeliveryService.php`

### Acceptance Criteria:
- ✅ POS system كامل (keyboard-first, barcode, payments)
- ✅ Real-time stock deduction
- ✅ Multiple payment methods
- ✅ Sale returns workflow
- ✅ Cash register management
- ✅ Receipt printing
- ✅ Offline POS support
- ✅ Order management with delivery tracking

---

## 🔴 المرحلة 5: امتثال الصيدلية (Pharmacy Compliance)

### المدة: 2 weeks

### المهام:

#### 5.1 Prescription Management
- [ ] إنشاء جدول prescriptions
- [ ] إنشاء جدول prescription_items
- [ ] إنشاء جدول prescription_refills
- [ ] إنشاء جدول controlled_substances_log
- [ ] Prescription model
- [ ] PrescriptionItem model
- [ ] PrescriptionRefill model
- [ ] ControlledSubstanceLog model
- [ ] Prescription entry API
- [ ] Prescription image upload
- [ ] Dispensing workflow (pending → partially_dispensed → dispensed)
- [ ] Prescription validity check
- [ ] Refill tracking with limits
- [ ] Controlled substance log (compliance)
- [ ] Patient allergy cross-reference
- [ ] Drug-drug interaction alerts

**الملفات المطلوبة:**
- `database/migrations/2025_03_05_000001_create_prescriptions_table.php`
- `database/migrations/2025_03_05_000002_create_prescription_items_table.php`
- `database/migrations/2025_03_05_000003_create_prescription_refills_table.php`
- `database/migrations/2025_03_05_000004_create_controlled_substances_log_table.php`
- `app/Models/Prescription.php`
- `app/Models/PrescriptionItem.php`
- `app/Models/PrescriptionRefill.php`
- `app/Models/ControlledSubstanceLog.php`
- `app/Http/Controllers/API/V1/PrescriptionController.php`
- `app/Services/PrescriptionService.php`
- `app/Services/ControlledSubstanceService.php`
- `app/Services/DrugInteractionChecker.php`

### Acceptance Criteria:
- ✅ Prescription management كامل
- ✅ Dispensing workflow
- ✅ Prescription validity checks
- ✅ Refill tracking
- ✅ Controlled substance log (compliance)
- ✅ Patient allergy cross-reference
- ✅ Drug-drug interaction alerts

---

## 🔴 المرحلة 6: المالية والمحاسبة المتقدمة (Advanced Financial & Accounting)

### المدة: 2-3 weeks

### المهام:

#### 6.1 Financial Module
- [ ] إنشاء جدول expense_categories
- [ ] إنشاء جدول expenses
- [ ] إنشاء جدول payment_records
- [ ] إنشاء جدول accounts
- [ ] إنشاء جدول journal_entries
- [ ] إنشاء جدول journal_entry_lines
- [ ] إنشاء جدول tax_rates
- [ ] ExpenseCategory model
- [ ] Expense model
- [ ] PaymentRecord model
- [ ] Account model
- [ ] JournalEntry model
- [ ] JournalEntryLine model
- [ ] TaxRate model
- [ ] Expenses tracking API
- [ ] Income tracking API
- [ ] Cash register management
- [ ] Wallet management
- [ ] Accounts receivable
- [ ] Accounts payable
- [ ] Tax calculations
- [ ] Daily/monthly financial summary
- [ ] Profit/loss calculations
- [ ] Full financial audit log

**الملفات المطلوبة:**
- `database/migrations/2025_03_15_000001_create_expense_categories_table.php`
- `database/migrations/2025_03_15_000002_create_expenses_table.php`
- `database/migrations/2025_03_15_000003_create_payment_records_table.php`
- `database/migrations/2025_03_15_000004_create_accounts_table.php`
- `database/migrations/2025_03_15_000005_create_journal_entries_table.php`
- `database/migrations/2025_03_15_000006_create_journal_entry_lines_table.php`
- `database/migrations/2025_03_15_000007_create_tax_rates_table.php`
- `app/Models/ExpenseCategory.php`
- `app/Models/Expense.php`
- `app/Models/PaymentRecord.php`
- `app/Models/Account.php`
- `app/Models/JournalEntry.php`
- `app/Models/JournalEntryLine.php`
- `app/Models/TaxRate.php`
- `app/Http/Controllers/API/V1/ExpenseController.php`
- `app/Http/Controllers/API/V1/IncomeController.php`
- `app/Http/Controllers/API/V1/AccountController.php`
- `app/Http/Controllers/API/V1/JournalEntryController.php`
- `app/Services/FinancialService.php`
- `app/Services/AccountingService.php`
- `app/Services/TaxService.php`

#### 6.2 Payroll Management
- [ ] إنشاء جدول payrolls
- [ ] إنشاء جدول payroll_items
- [ ] Payroll model
- [ ] PayrollItem model
- [ ] Employee salary management
- [ ] Payroll calculations
- [ ] Payroll slips
- [ ] Payroll history

**الملفات المطلوبة:**
- `database/migrations/2025_03_20_000001_create_payrolls_table.php`
- `database/migrations/2025_03_20_000002_create_payroll_items_table.php`
- `app/Models/Payroll.php`
- `app/Models/PayrollItem.php`
- `app/Http/Controllers/API/V1/PayrollController.php`
- `app/Services/PayrollService.php`

### Acceptance Criteria:
- ✅ Financial module كامل
- ✅ Expense/income tracking
- ✅ Cash register management
- ✅ Wallet management
- ✅ Accounts receivable/payable
- ✅ Tax calculations
- ✅ Financial reports
- ✅ Payroll management
- ✅ Full audit log

---

## 🔴 المرحلة 7: CRM وإدارة العملاء (CRM & Customer Management)

### المدة: 2-3 weeks

### المهام:

#### 7.1 Customer Management
- [ ] Customer 360° view
- [ ] Credit control (limits, tracking)
- [ ] Loyalty points system
- [ ] Customer offers/discounts
- [ ] Group discounts
- [ ] Product-specific discounts
- [ ] Top customers ranking
- [ ] Customers needing follow-up (auto-detection)
- [ ] Follow-up alerts
- [ ] Customer segmentation
- [ ] Customer communication history

**الملفات المطلوبة:**
- `app/Services/Customer360Service.php`
- `app/Services/CreditControlService.php`
- `app/Services/LoyaltyService.php` (update)
- `app/Services/CustomerSegmentationService.php`
- `app/Services/FollowUpService.php`
- `app/Http/Controllers/API/V1/CustomerAnalyticsController.php`

#### 7.2 Doctor Management
- [ ] Referring doctors database
- [ ] Prescription-sales linkage
- [ ] Top referring doctors
- [ ] Doctors needing follow-up (auto-detection)
- [ ] Follow-up alerts
- [ ] Doctor performance analytics

**الملفات المطلوبة:**
- `app/Services/DoctorAnalyticsService.php`
- `app/Services/DoctorFollowUpService.php`
- `app/Http/Controllers/API/V1/DoctorAnalyticsController.php`

### Acceptance Criteria:
- ✅ Customer 360° view
- ✅ Credit control system
- ✅ Loyalty points system
- ✅ Customer discounts/offers
- ✅ Top customers analytics
- ✅ Follow-up system
- ✅ Doctor referral tracking
- ✅ Doctor performance analytics

---

## 🔴 المرحلة 8: التقارير والتحليلات المتقدمة (Advanced Reporting & Analytics)

### المدة: 2-3 weeks

### المهام:

#### 8.1 11 Analytics Dashboards
- [ ] Pharmacy Overview Dashboard
- [ ] Sales Analytics Dashboard
- [ ] Purchase Analytics Dashboard
- [ ] Customer Analytics Dashboard
- [ ] Supplier Analytics Dashboard
- [ ] Inventory Analytics Dashboard
- [ ] Employee Performance Dashboard
- [ ] Doctor Referrals Dashboard
- [ ] Insurance Claims Dashboard
- [ ] Financial Overview Dashboard
- [ ] Delivery Tracking Dashboard

**الملفات المطلوبة:**
- `app/Http/Controllers/API/V1/Dashboard/PharmacyOverviewController.php`
- `app/Http/Controllers/API/V1/Dashboard/SalesAnalyticsController.php`
- `app/Http/Controllers/API/V1/Dashboard/PurchaseAnalyticsController.php`
- `app/Http/Controllers/API/V1/Dashboard/CustomerAnalyticsController.php`
- `app/Http/Controllers/API/V1/Dashboard/SupplierAnalyticsController.php`
- `app/Http/Controllers/API/V1/Dashboard/InventoryAnalyticsController.php`
- `app/Http/Controllers/API/V1/Dashboard/EmployeePerformanceController.php`
- `app/Http/Controllers/API/V1/Dashboard/DoctorReferralsController.php`
- `app/Http/Controllers/API/V1/Dashboard/InsuranceClaimsController.php`
- `app/Http/Controllers/API/V1/Dashboard/FinancialOverviewController.php`
- `app/Http/Controllers/API/V1/Dashboard/DeliveryTrackingController.php`
- `app/Services/DashboardService.php`

#### 8.2 Reports
- [ ] Sales Reports (daily/weekly/monthly/annual)
- [ ] Sales Reports (by branch/product/category/cashier)
- [ ] Inventory Reports (valuation, movement, expiry, slow-moving, dead stock)
- [ ] Purchase Reports
- [ ] Financial Reports (P&L, cash flow, tax)
- [ ] Customer Reports (top customers, purchase history, loyalty)
- [ ] Employee Reports (sales per employee, performance)
- [ ] Pharmacy-Specific Reports (prescription log, controlled substances, expiry)
- [ ] PDF/Excel export for all reports
- [ ] Dashboard KPIs with charts

**الملفات المطلوبة:**
- `app/Http/Controllers/API/V1/Report/SalesReportController.php`
- `app/Http/Controllers/API/V1/Report/InventoryReportController.php`
- `app/Http/Controllers/API/V1/Report/PurchaseReportController.php`
- `app/Http/Controllers/API/V1/Report/FinancialReportController.php`
- `app/Http/Controllers/API/V1/Report/CustomerReportController.php`
- `app/Http/Controllers/API/V1/Report/EmployeeReportController.php`
- `app/Http/Controllers/API/V1/Report/PharmacyReportController.php`
- `app/Services/ReportService.php`
- `app/Services/ExportService.php`

### Acceptance Criteria:
- ✅ 11 analytics dashboards functional
- ✅ All reports working
- ✅ PDF/Excel export working
- ✅ KPIs with charts
- ✅ Real-time data updates

---

## 🔴 المرحلة 9: الميزات المتقدمة (Advanced Features)

### المدة: 2-3 weeks

### المهام:

#### 9.1 Insurance Management
- [ ] Insurance companies & plans
- [ ] Claims submission & tracking
- [ ] Approval/rejection workflow
- [ ] Settlement reports
- [ ] Contract management

**الملفات المطلوبة:**
- `database/migrations/2025_04_15_000001_create_insurance_companies_table.php`
- `database/migrations/2025_04_15_000002_create_insurance_plans_table.php`
- `database/migrations/2025_04_15_000003_create_insurance_claims_table.php`
- `app/Models/InsuranceCompany.php`
- `app/Models/InsurancePlan.php`
- `app/Models/InsuranceClaim.php`
- `app/Http/Controllers/API/V1/InsuranceCompanyController.php`
- `app/Http/Controllers/API/V1/InsurancePlanController.php`
- `app/Http/Controllers/API/V1/InsuranceClaimController.php`
- `app/Services/InsuranceService.php`

#### 9.2 Partner Management
- [ ] Partner database
- [ ] Partnership tracking
- [ ] Partner contracts
- [ ] Partner performance

**الملفات المطلوبة:**
- `database/migrations/2025_04_20_000001_create_partners_table.php`
- `app/Models/Partner.php`
- `app/Http/Controllers/API/V1/PartnerController.php`
- `app/Services/PartnerService.php`

#### 9.3 Notification System (Multi-channel)
- [ ] In-app notifications
- [ ] Email notifications
- [ ] SMS notifications
- [ ] Push notifications
- [ ] Per-user preferences
- [ ] Queued delivery
- [ ] Smart alerts (expiring products, low stock, shortages)

**الملفات المطلوبة:**
- `app/Services/NotificationService.php`
- `app/Services/EmailNotificationService.php`
- `app/Services/SMSNotificationService.php`
- `app/Services/PushNotificationService.php`
- `app/Jobs/SendNotificationJob.php`

#### 9.4 Notes & Reminders
- [ ] Quick notes for pharmacy team
- [ ] Personal/shared reminders
- [ ] Note categories
- [ ] Note search

**الملفات المطلوبة:**
- `database/migrations/2025_04_25_000001_create_notes_table.php`
- `database/migrations/2025_04_25_000002_create_reminders_table.php`
- `app/Models/Note.php`
- `app/Models/Reminder.php`
- `app/Http/Controllers/API/V1/NoteController.php`
- `app/Http/Controllers/API/V1/ReminderController.php`

#### 9.5 Events & Calendar
- [ ] Appointment management
- [ ] Important dates
- [ ] Recurring events
- [ ] Team sharing
- [ ] Calendar integration

**الملفات المطلوبة:**
- `database/migrations/2025_04_30_000001_create_events_table.php`
- `database/migrations/2025_04_30_000002_create_calendar_events_table.php`
- `app/Models/Event.php`
- `app/Models/CalendarEvent.php`
- `app/Http/Controllers/API/V1/EventController.php`
- `app/Services/CalendarService.php`

#### 9.6 Settings & Configuration
- [ ] Company-level settings
- [ ] Branch-level settings
- [ ] System-level settings
- [ ] Tax rates
- [ ] Payment methods
- [ ] Invoice templates
- [ ] Currency
- [ ] Contract pricing

**الملفات المطلوبة:**
- `app/Services/SettingsService.php`
- `app/Http/Controllers/API/V1/SettingsController.php`

### Acceptance Criteria:
- ✅ Insurance management functional
- ✅ Partner management working
- ✅ Multi-channel notifications
- ✅ Notes & reminders system
- ✅ Events & calendar
- ✅ Advanced settings

---

## 🔴 المرحلة 10: الذكاء الاصطناعي والأتمتة (AI & Automation)

### المدة: 2-3 weeks

### المهام:

#### 10.1 AI Assistant
- [ ] Natural language search
- [ ] Voice commands
- [ ] Predictive insights
- [ ] Smart recommendations

**الملفات المطلوبة:**
- `app/Services/AI/AIAssistantService.php`
- `app/Services/AI/NaturalLanguageSearchService.php`
- `app/Services/AI/VoiceCommandService.php`
- `app/Services/AI/PredictiveInsightsService.php`
- `app/Services/AI/SmartRecommendationsService.php`

#### 10.2 AI-Powered Purchasing
- [ ] Sales analysis
- [ ] Inventory analysis
- [ ] Item movement analysis
- [ ] Auto quantity suggestions
- [ ] Data-driven purchase decisions

**الملفات المطلوبة:**
- `app/Services/AI/AIPurchaseService.php`
- `app/Services/AI/DemandForecastingService.php`

#### 10.3 Invoice OCR
- [ ] Supplier invoice OCR
- [ ] Automatic item recognition
- [ ] Automatic data entry
- [ ] Manual verification workflow

**الملفات المطلوبة:**
- `app/Services/AI/InvoiceOCRService.php`
- `app/Http/Controllers/API/V1/InvoiceOCRController.php`

#### 10.4 Auto Stock Limits
- [ ] Movement data analysis
- [ ] Auto limit calculation
- [ ] Stop guessing, start planning

**الملفات المطلوبة:**
- `app/Services/AI/AutoStockLimitService.php`
- `app/Jobs/CalculateAutoStockLimits.php`

### Acceptance Criteria:
- ✅ AI assistant functional
- ✅ AI-powered purchasing
- ✅ Invoice OCR working
- ✅ Auto stock limits calculation

---

## 🔴 المرحلة 11: الاتصالات والتعاون (Communication & Collaboration)

### المدة: 2 weeks

### المهام:

#### 11.1 Inter-Branch Chat
- [ ] Real-time messaging between branches
- [ ] Stock transfer coordination
- [ ] Order approval
- [ ] Problem solving without external apps

**الملفات المطلوبة:**
- `database/migrations/2025_05_15_000001_create_messages_table.php`
- `database/migrations/2025_05_15_000002_create_conversations_table.php`
- `app/Models/Message.php`
- `app/Models/Conversation.php`
- `app/Http/Controllers/API/V1/ChatController.php`
- `app/Services/ChatService.php`

#### 11.2 Branch Communication
- [ ] Share updates
- [ ] Transfer coordination
- [ ] Keep team connected

**الملفات المطلوبة:**
- `app/Services/BranchCommunicationService.php`

#### 11.3 Order Transfers
- [ ] Transfer sales/purchase orders
- [ ] Full customer service flexibility

**الملفات المطلوبة:**
- `app/Services/OrderTransferService.php`

### Acceptance Criteria:
- ✅ Inter-branch chat working
- ✅ Branch communication functional
- ✅ Order transfers working

---

## 🔴 المرحلة 12: تطبيقات سطح المكتب (Desktop Apps)

### المدة: 3-4 weeks

### المهام:

#### 12.1 Remote Management Desktop App
- [ ] Pharmacy performance monitoring
- [ ] Purchase order creation
- [ ] Operations supervision
- [ ] Reports & analytics

**الملفات المطلوبة:**
- `desktop/remote-management/` (Electron/Tauri project)
- `package.json`
- `src/` (React/Next.js UI)
- `src/services/` (API integration)
- `src/store/` (State management)

#### 12.2 Offline-First Desktop App
- [ ] Full POS offline
- [ ] Inventory management offline
- [ ] Sales & returns offline
- [ ] Local reports
- [ ] 100% offline capability
- [ ] Auto-sync on connection

**الملفات المطلوبة:**
- `desktop/offline-pos/` (Electron/Tauri project)
- `package.json`
- `src/` (React/Next.js UI)
- `src/database/` (SQLite integration)
- `src/sync/` (Sync queue)
- `src/hardware/` (Hardware integration)

#### 12.3 Hardware Integration
- [ ] Barcode scanners (USB/Serial)
- [ ] Receipt printers (ESC/POS)
- [ ] Cash drawers
- [ ] Weighing scales

**الملفات المطلوبة:**
- `desktop/offline-pos/src/hardware/BarcodeScanner.js`
- `desktop/offline-pos/src/hardware/ReceiptPrinter.js`
- `desktop/offline-pos/src/hardware/CashDrawer.js`
- `desktop/offline-pos/src/hardware/WeighingScale.js`

### Acceptance Criteria:
- ✅ Remote management app working
- ✅ Offline POS app 100% functional
- ✅ Hardware integration working
- ✅ Auto-sync functional

---

## 🔴 المرحلة 13: تطبيقات الهاتف (Mobile Apps)

### المدة: 2-3 weeks

### المهام:

#### 13.1 Mobile Monitoring App
- [ ] Real-time sales tracking
- [ ] Inventory monitoring
- [ ] Employee performance
- [ ] Reports & analytics
- [ ] Notifications & alerts

**الملفات المطلوبة:**
- `mobile/pharmacy-monitor/` (React Native/Flutter project)
- `package.json` / `pubspec.yaml`
- `src/` (React Native) / `lib/` (Flutter)
- `src/services/` (API integration)
- `src/notifications/` (Push notifications)

#### 13.2 Mobile Features
- [ ] Dashboard
- [ ] Sales tracking
- [ ] Inventory monitoring
- [ ] Employee performance
- [ ] Reports
- [ ] Notifications
- [ ] Biometric login
- [ ] Offline mode
- [ ] Push notifications

**الملفات المطلوبة:**
- `mobile/pharmacy-monitor/src/screens/Dashboard.js`
- `mobile/pharmacy-monitor/src/screens/Sales.js`
- `mobile/pharmacy-monitor/src/screens/Inventory.js`
- `mobile/pharmacy-monitor/src/screens/Reports.js`
- `mobile/pharmacy-monitor/src/services/BiometricAuth.js`

### Acceptance Criteria:
- ✅ Mobile monitoring app working
- ✅ All features functional
- ✅ Push notifications working
- ✅ Biometric auth working
- ✅ Offline mode working

---

## 🔴 المرحلة 14: البنية التحتية المتقدمة (Advanced Infrastructure)

### المدة: 2-3 weeks

### المهام:

#### 14.1 Offline-First Architecture
- [ ] Desktop app works offline
- [ ] All sales/inventory/reports work offline
- [ ] Auto-sync on connection
- [ ] Conflict resolution strategy

**الملفات المطلوبة:**
- `app/Services/Sync/SyncService.php`
- `app/Services/Sync/ConflictResolutionService.php`
- `app/Services/Sync/SyncQueueService.php`

#### 14.2 Multi-Device Network
- [ ] Multi-device network system
- [ ] Local network device connection
- [ ] Real-time sync

**الملفات المطلوبة:**
- `app/Services/Network/MultiDeviceNetworkService.php`
- `app/Services/Network/LocalSyncService.php`

#### 14.3 Backup & Security
- [ ] Enterprise-level backup
- [ ] Secure restore
- [ ] Data encryption

**الملفات المطلوبة:**
- `app/Services/Backup/BackupService.php`
- `app/Services/Backup/RestoreService.php`
- `app/Services/Security/EncryptionService.php`

#### 14.4 Import/Export
- [ ] Import inventory items
- [ ] Import product data
- [ ] Export reports
- [ ] Export invoices
- [ ] Export analytics (PDF, Excel)

**الملفات المطلوبة:**
- `app/Services/Import/ImportService.php`
- `app/Services/Export/ExportService.php`

#### 14.5 Integrations
- [ ] Smart integrations
- [ ] Automatic data transfer
- [ ] No manual entry

**الملفات المطلوبة:**
- `app/Services/Integration/IntegrationService.php`
- `app/Services/Integration/DataTransferService.php`

### Acceptance Criteria:
- ✅ Offline-first architecture working
- ✅ Multi-device network functional
- ✅ Enterprise backup working
- ✅ Import/export working
- ✅ Integrations working

---

## 🔴 المرحلة 15: الاختبارات والتوثيق والـ DevOps (Testing, Documentation, DevOps)

### المدة: 2 weeks

### المهام:

#### 15.1 Testing
- [ ] Unit tests (80%+ coverage)
- [ ] Feature tests (all API endpoints)
- [ ] E2E tests (critical flows)
- [ ] Frontend tests (components + flows)
- [ ] Performance tests (load testing)
- [ ] Security tests (OWASP Top 10)
- [ ] Offline tests (sync & conflict resolution)

**الملفات المطلوبة:**
- `tests/Unit/` (Unit tests)
- `tests/Feature/` (Feature tests)
- `tests/E2E/` (E2E tests)
- `tests/Performance/` (Performance tests)
- `tests/Security/` (Security tests)

#### 15.2 Documentation
- [ ] API documentation (Swagger/OpenAPI)
- [ ] Code documentation (PHPDoc + TSDoc)
- [ ] Architecture docs (ADRs)
- [ ] User manual (Arabic + English)
- [ ] Developer guide (Setup, Contributing, Deployment)
- [ ] CHANGELOG (Semantic versioning)

**الملفات المطلوبة:**
- `docs/api/` (API documentation)
- `docs/architecture/` (Architecture docs)
- `docs/user/` (User manual)
- `docs/developer/` (Developer guide)
- `CHANGELOG.md`

#### 15.3 DevOps
- [ ] Docker setup (dev + prod)
- [ ] CI/CD pipeline
- [ ] Staging environment
- [ ] Production deployment
- [ ] Monitoring setup
- [ ] Alerting setup

**الملفات المطلوبة:**
- `Dockerfile`
- `docker-compose.yml`
- `.github/workflows/ci-cd.yml`
- `deploy/` (Deployment scripts)
- `monitoring/` (Monitoring configs)

#### 15.4 Localization
- [ ] Arabic (RTL) complete
- [ ] English (LTR) complete
- [ ] Currency formatting
- [ ] Date/time formatting

**الملفات المطلوبة:**
- `lang/ar/` (Arabic translations)
- `lang/en/` (English translations)
- `frontend/locales/ar.json`
- `frontend/locales/en.json`

### Acceptance Criteria:
- ✅ 80%+ test coverage
- ✅ All documentation complete
- ✅ CI/CD pipeline working
- ✅ Docker setup working
- ✅ Arabic + English complete
- ✅ Production-ready

---

## 🎯 معايير النجاح النهائية (Final Success Criteria)

### Technical Excellence
- ✅ Lighthouse score 95+
- ✅ 80%+ test coverage
- ✅ < 2s page load time
- ✅ 99.9% uptime
- ✅ Zero critical security vulnerabilities
- ✅ Clean, maintainable code
- ✅ Comprehensive documentation

### Business Value
- ✅ Support 10,000+ pharmacies
- ✅ 100+ concurrent users per pharmacy
- ✅ < 1 second API response time
- ✅ 99.9% data accuracy
- ✅ 24/7 system availability
- ✅ Competitive with global solutions

### User Experience
- ✅ Intuitive UI (Stripe/Linear level)
- ✅ Arabic RTL support
- ✅ Offline capability
- ✅ Mobile-responsive
- ✅ Accessibility (WCAG 2.1 AA)
- ✅ Fast performance
- ✅ Delightful interactions

---

## 📝 ملاحظات هامة

1. **الجودة > السرعة**: كل كود يجب أن يكون production-ready من المرة الأولى
2. **Testing أولاً**: كل feature يجب أن يحتوي على tests
3. **Documentation**: التوثيق جزء من التطوير، ليس مرحلة منفصلة
4. **Security**: Security first في كل قرار
5. **Performance**: Performance optimization مستمر
6. **User Experience**: User experience paramount
7. **Communication**: تواصل مستمر مع stakeholder
8. **Flexibility**: الاستعداد لتعديل الخطة حسب الاحتياجات

---

## 🚀 الخطوات التالية

1. ✅ مراجعة ARCHITECTURE.md
2. ✅ مراجعة IMPLEMENTATION_ROADMAP.md
3. ⏳ البدء بالمرحلة 1 (Foundation Fixes)
4. ⏳ التنفيذ التدريجي لكل مرحلة
5. ⏳ Testing و Documentation مستمر
6. ⏳ Deployment لكل مرحلة

**التاريخ**: 2026-07-18  
**الإصدار**: 1.0  
**الحالة**: جاهز للتنفيذ
