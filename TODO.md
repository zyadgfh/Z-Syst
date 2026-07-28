# 📋 خطة تطوير المبيعات والـ POS

## 📝 ملخص التغييرات

### ملفات جديدة:
| الملف | الوصف |
|-------|-------|
| `app/Models/Inventory.php` | تم تحديثه ليتوافق مع جدول inventory (UUID) و FEFO |
| `app/Http/Controllers/Admin/WalletSettingController.php` | إعدادات المحفظة الإلكترونية (Admin only) |
| `app/Services/ReceiptService.php` | خدمة الإيصالات والطباعة |
| `app/Services/SaleStatsService.php` | إحصائيات وتقارير المبيعات |
| `app/Modules/Sales/Domain/Events/SaleVoided.php` | حدث إلغاء الفاتورة |
| `app/Modules/Sales/Domain/Events/SaleReturned.php` | حدث مرتجع المبيعات |

### ملفات معدلة:
| الملف | التغيير |
|-------|---------|
| `app/Modules/Sales/Application/Services/SaleService.php` | إضافة voidSale, addPayment مع SalePayment, CashRegister logging |
| `app/Modules/Sales/Domain/DTOs/CreateSaleDTO.php` | إضافة payments, cash_register_id, insurance_claim_id |
| `app/Modules/Sales/Infrastructure/Controllers/SaleController.php` | إضافة void, return, payments, receipt, stats |
| `app/Modules/Sales/Infrastructure/Requests/StoreSaleRequest.php` | إضافة wallet payment, split payments validation |
| `app/Modules/Sales/Infrastructure/Resources/SaleResource.php` | إضافة payments, cash_register, receipt_url |
| `app/Modules/Sales/Routes/api.php` | إضافة void, return, payments routes |
| `routes/api.php` | إضافة POS module routes, wallet settings routes |

## ✅ المرحلة 1: تحسين وحدة المبيعات الأساسية ✅ (مكتمل)

### 1.1 إلغاء الفاتورة (Void Sale)
- [x] إضافة `voidSale()` في `SaleService` مع FEFO restore
- [x] إضافة `POST /sales/{sale}/void` endpoint
- [x] إضافة حدث `SaleVoided` 

### 1.2 مرتجعات المبيعات (Sale Return)
- [x] إنشاء حدث `SaleReturned`
- [x] دعم المرتجع مع FEFO restore (جاهز للتوسع)
- [x] ربط مع CashRegister عند الفاتورة

### 1.3 ربط المدفوعات (Split Payments + Wallet)
- [x] تحديث `CreateSaleDTO` لدعم `payments` array
- [x] تحديث `StoreSaleRequest` لدعم `wallet` و `vodafone_cash`
- [x] تسجيل `SalePayment` تلقائيًا عند إنشاء الفاتورة

### 1.4 ربط الخزنة (Cash Register)
- [x] إضافة `cash_register_id` في CreateSaleDTO
- [x] تحديث CashRegister عند كل فاتورة (increment total_sales)

### 1.5 إعدادات المحفظة الإلكترونية ✅
- [x] إنشاء `WalletSettingController` مع `manage_wallet_settings` permission
- [x] إضافة routes `GET/PUT /admin/wallet-settings` و `GET /wallet-phone`
- [x] دعم: wallet_provider, wallet_phone, wallet_name, wallet_qr_code, wallet_enabled

## ✅ المرحلة 2: نقاط النهاية الجديدة ✅ (مكتمل)

### 2.1 إلغاء الفاتورة
- [x] إضافة `POST /sales/{sale}/void`
- [x] إضافة حدث `SaleVoided` مع سبب الإلغاء

### 2.2 المدفوعات والإيصالات
- [x] إضافة `POST /sales/{sale}/payments` مع دعم `vodafone_cash` و `instapay`
- [x] إضافة `GET /sales/{sale}/receipt` (عبر ReceiptService مع بيانات المحفظة)
- [x] إضافة `GET /sales/{sale}/receipt/print` (HTML جاهز للطباعة)

## ✅ المرحلة 3: تقارير وإحصائيات المبيعات ✅ (مكتمل)

### 3.1 SaleStatsService
- [x] إنشاء `app/Services/SaleStatsService.php`
- [x] إحصائيات يومية/أسبوعية/شهرية/سنوية
- [x] أفضل المنتجات مبيعًا (كمية + إيراد)
- [x] أفضل العملاء (عدد المشتريات + إجمالي الإنفاق)
- [x] توزيع طرق الدفع
- [x] إيراد يومي (30 يوم chart data)

### 3.2 نقاط النهاية
- [x] `GET /sales/stats/dashboard` - إحصائيات متقدمة
- [x] `GET /sales/stats/top-products` - أفضل المنتجات
- [x] `GET /sales/stats/top-customers` - أفضل العملاء

## ✅ المرحلة 4: خدمة الإيصالات والطباعة ✅ (مكتمل)

### 4.1 ReceiptService
- [x] إنشاء `app/Services/ReceiptService.php`
- [x] دعم HTML (للعرض والطباعة في المتصفح)
- [x] إظهار رقم المحفظة في الإيصال (إن وجد)
- [x] ترجمة طرق الدفع والحالات إلى العربية
- [x] دعم معلومات الكاشير والعميل

## ✅ المرحلة 5: تحديث النماذج (Models) ✅

### 5.1 Sale Model
- [x] إضافة `fillable` للحقول الجديدة (branch_id, customer_id, customer_name, customer_phone, payment_method, invoice_number, subtotal, discount_amount, total_amount, amount_paid, change_amount, notes, prescription_id, void_reason, voided_at, created_by)
- [x] إضافة `casts` جديدة (subtotal, discount_amount, tax_amount, total_amount, amount_paid, change_amount, voided_at)
- [x] إضافة علاقات: `items()`, `payments()`, `customer()`, `createdBy()`, `cashRegister()`

### 5.2 Inventory Model
- [x] تحديث النموذج ليتوافق مع جدول `inventory` (UUID PK)
- [x] إضافة `HasUuids` trait
- [x] إضافة دوال: reserve(), releaseReserve(), deductQuantity(), addQuantity(), hasAvailableStock()
- [x] إضافة scopes: available(), notExpired(), fefoOrder()
- [x] إضافة accessor: available_quantity

## ✅ المرحلة 6: ربط الخدمات (Service Container) ✅

### 6.1 ModuleServiceProvider
- [x] تحديث `SaleService` binding ليشمل `FefoStockService`
- [x] إضافة singleton لـ `ReceiptService` و `SaleStatsService`
- [x] إضافة `SaleController` binding مع كل التبعيات

## 🔄 المرحلة 7: اختبارات التكامل (قيد التنفيذ)

### 7.1 إصلاح SaleItem Model (إضافة line_total إلى fillable)
- [ ] إضافة `line_total` إلى `$fillable` في SaleItem model

### 7.2 اختبار إنشاء فاتورة
- [ ] إنشاء `tests/Feature/SalesModuleFlowTest.php`
- [ ] اختبار إنشاء فاتورة كاملة مع FEFO
- [ ] اختبار فشل إنشاء فاتورة عند عدم توفر مخزون

### 7.3 اختبار إلغاء فاتورة مع FEFO Restore
- [ ] اختبار إلغاء فاتورة واستعادة المخزون
- [ ] اختبار فشل إلغاء فاتورة ملغاة مسبقًا

### 7.4 اختبار مرتجع المبيعات
- [ ] اختبار إنشاء مرتجع مع استعادة المخزون

### 7.5 اختبار الدفع بالمحفظة والمدفوعات المتعددة
- [ ] اختبار إنشاء فاتورة مع دفعات متعددة (split payments)
- [ ] اختبار الدفع بالمحفظة (wallet)
- [ ] اختبار إضافة دفعة لفاتورة قائمة

## 🔄 المرحلة 8: تحسين واجهة POS الأمامية (قيد التنفيذ)

### 8.1 دعم العربية و RTL
- [ ] تحديث `pos-screen.tsx` لدعم RTL واللغة العربية
- [ ] ترجمة جميع عناصر الواجهة إلى العربية

### 8.2 تحسين واجهة الدفع
- [ ] إضافة دعم المحفظة وفودافون كاش وإنستاباي
- [ ] عرض تفاصيل الدفع المتعددة

### 8.3 دعم مسح الباركود
- [ ] إضافة حقل إدخال باركود للبحث السريع

### 8.4 شاشة تأكيد الفاتورة
- [ ] تحسين شاشة عرض الفاتورة بعد البيع

## 🔄 المرحلة 9: دعم الطباعة الحرارية ESC/POS (قيد التنفيذ)

### 9.1 إنشاء ThermalPrintService
- [ ] إنشاء `app/Services/ThermalPrintService.php`
- [ ] دعم بروتكول ESC/POS
- [ ] دعم تحويل بيانات الإيصال إلى أوامر طباعة

### 9.2 نقطة نهاية الطباعة الحرارية
- [ ] إضافة endpoint `GET /sales/{sale}/receipt/thermal`
