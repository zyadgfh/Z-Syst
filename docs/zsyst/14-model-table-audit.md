# 14 · جرد النماذج مقابل مخطط Supabase

**التاريخ:** 31 أكتوبر 2026 · **المصدر:** فحص آلي لكل ملف في `app/Models` مقابل `information_schema` الحقيقي.

- إجمالي النماذج: **126**
- تعمل مقابل جدول موجود: **95**
- تشير إلى جداول **غير موجودة** في Supabase: **31**

## لماذا هذا مهم

كل نموذج يشير إلى جدول مفقود سيُنتج خطأ `relation does not exist` عند أول استعلام — أي صفحة 500.
هذه ليست أخطاء برمجية جديدة، بل **بقايا MySQL**: 31 جدولًا من أصل 70 لم تنتقل إلى Supabase لأن النظام الجديد لم يكن يحتاجها.

## التصنيف

### Subscription/billing

| النموذج | الجدول المتوقع |
|---|---|
| `Gateway` | `gateways` |
| `Plan` | `plans` |
| `PlanSubscribe` | `plan_subscribes` |
| `TenantPaymentSetting` | `tenant_payment_settings` |

### Settings/options

| النموذج | الجدول المتوقع |
|---|---|
| `Option` | `options` |
| `PredictionSetting` | `prediction_settings` |
| `Receipt` | `receipts` |
| `ReceiptSetting` | `receipt_settings` |
| `Setting` | `settings` |

### Reference data

| النموذج | الجدول المتوقع |
|---|---|
| `BusinessCategory` | `business_categories` |
| `Currency` | `currencies` |
| `InsuranceCoverage` | `insurance_coverages` |
| `Language` | `languages` |

### Suppliers (parties in Supabase)

| النموذج | الجدول المتوقع |
|---|---|
| `CreditDebitItem` | `credit_debit_items` |
| `DueCollect` | `due_collects` |
| `Supplier` | `suppliers` |
| `SupplierContract` | `supplier_contracts` |
| `SupplierCredit` | `supplier_credits` |
| `SupplierDebit` | `supplier_debits` |
| `SupplierPerformance` | `supplier_performances` |
| `SupplierRating` | `supplier_ratings` |

### Approvals

| النموذج | الجدول المتوقع |
|---|---|
| `ApprovalStep` | `approval_steps` |
| `ApprovalTemplate` | `approval_templates` |
| `ApprovalWorkflow` | `approval_workflows` |

### Analytics/reports

| النموذج | الجدول المتوقع |
|---|---|
| `InventoryTurnoverReport` | `inventory_turnover_reports` |
| `ProductInventoryAnalysis` | `product_inventory_analysis` |
| `SalesForecast` | `sales_forecasts` |

### Ops

| النموذج | الجدول المتوقع |
|---|---|
| `Banner` | `banners` |
| `Notification` | `notifications` |
| `StockAuditDetail` | `stock_audit_details` |
| `StockReconciliation` | `stock_reconciliations` |

## ما تم إصلاحه في هذه الجلسة

| البند | الحل |
|-------|------|
| جداول Laravel المفقودة كليًا | أُنشئت في Supabase: `personal_access_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs` — RLS مفعّل بلا سياسات، ممنوعة على `anon`/`authenticated` |
| `Plan` (كان يشير إلى `plans` المفقود) | **view توافقية** `public.plans` فوق `subscription_plans` بالأسماء القديمة، فتعمل كل مواضع الاستخدام دون تعديل، وتعرض الأسعار الجديدة 499/899/1,499 فعليًا |
| بقية الجداول الـ30 | **لم تُنقل بعد** — انظر التوصية أدناه |

## توصية للجداول المتبقية (30 جدولًا)

هذه ليست أعطالًا في الكود، بل **ميزات MySQL لم تنتقل**. أمامها ثلاثة مسارات، وأوصي بالأول:

1. **إسقاط الميزة + تعطيل المسارات** (موصى به): معظمها بلا بيانات أصلًا (suppliers / businesses / purchases كانت 0 صفوف في MySQL). تُزال الصفحات من القوائم ويُعاد بناء ما نحتاجه فعلًا على مخطط Supabase.
2. **إعادة إنشاء الجداول في Supabase** من `zsyst-backup/mysql-final-2026-10-31.sql.gz` — يحتاج تحويل الأنواع (MySQL → Postgres) وقرارًا لكل جدول.
3. **views توافقية** كما في `plans` — ممكنة فقط حيث يوجد جدول مقابل فعلي، وحاليًا `plans` هو الوحيد بهذا الوضع الواضح.
