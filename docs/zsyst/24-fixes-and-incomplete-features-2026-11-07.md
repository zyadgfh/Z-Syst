# 24 — إصلاحات تقرير 23 الحية + جرد الميزات غير المكتملة — 2026-11-07

## الجزء الأول: الإصلاحات المنفَّذة (متحقق حيًا)

| ID | الإصلاح | التحقق الحي |
|---|---|---|
| **C-01** حرج | `TenantScoped` (العزل العالمي) على **11 موديلًا**: Unit, BoxSize, Category, Manufacturer, MedicineType, Tax, User, Supplier, DueCollect, Income, Coupon | staff-b (biz2): DELETE تصنيف biz1 → **404** ✓، وقائمة التصنيفات عنه **فارغة** ✓ |
| **H-01** عالٍ | تسعير خادمي: البيع يطابق `sales_price` من القاعدة (تسامح 2%)، التجاوز بصلاحية `sale.price-override` فقط | بيع بـ0.01 → **مرفوض** ✓، بالسعر الصحيح → **200** ✓ |
| **M-01** | FEFO داخل الترانزكشن بـ`lockForUpdate` + خصم محروس (`WHERE productStock >= ?` + فحص الصفوف) | كود مُراجَع (يتطلب تزامنًا لاختباره) |
| **M-02** | الشراء: `only()` whitelist (المبالغ/الحالات/الترقيم من الخادم) + Path-B user_id null | شجرة نظيفة من `request->all()` |
| **M-03** | **صفر `request->all()`** في كل الكنترولرات (25 موقعًا حولت لـfillable-whitelists آليةً) | grep = 0 ✓ |
| **M-04** | `HtmlSanitizer` (allowlist) على sinkي السياسات/الشروط | sinks معقمة ✓ |
| **L-01** | استثناءات CSRF الميتة حُذفت + حذف `app/Library/SslCommerz.php` اليتيم | no dead refs ✓ |
| إصلاح عطل إقلاع | `Handler::report` لم يعد ينهار عند سجل نصف-مُقلَع (crash-guard) + `Logger` CLI-guard + `session.php` بلا حل حاوية أثناء parse (خطأ مني في PR#17: `isLocal()` داخل config) | migrate:fresh --seed يعود يعمل ✓ |

## الجزء الثاني: جرد «غير المكتمل» — فحص دقيق

### أ) واجهات أدمن مفقودة خلف باك-إند مكتمل — **35 view** عبر 10 ميزات
| الميزة | الكنترولر (موجود+API) | المفقود |
|---|---|---|
| GRN (استلام بضاعة) | Admin/GRNController | create/show/edit |
| التأمين (شركات/وثائق/مطالبات) | 3 كنترولرات | 12 view |
| تحويلات المخزون | Admin/StockTransferController | index/create/show |
| الاشتراكات (إدارة) | Admin/SubscriptionController | 3 views |
| مدفوعات الموردين | Admin/SupplierPaymentController | 4 views |
| إعدادات دفع المستأجر | Admin/TenantPaymentSettingController | 2 views |
| المخازن (warehouses) | Admin/WarehouseController | index |
| سجلات التدقيق | Admin/AuditLogController | index/show |
| تقرير الداشبورد | Admin/DashboardReportController | dashboard |
| إعدادات موقع/لغات | Admin/ZSystLanguageController | 4 views |

→ هذه ميزات تعمل عبر API فقط؛ أي دخول لمسارها الويبي سيرمي 500 (view not found). **الأثر**: ميزات «خفية» كاملة بلا واجهة — إما بناء الواجهات أو إخفاء المسارات حتى جهوزها.

### ب) سقالات تنتظر مفاتيح (كود مدموج، off افتراضيًا)
Paymob · WhatsApp · ETA (ومعها TODO خريطة الوحدات الضريبية LTR/KGM) · النسخ السحابي · الرفع السحابي.

### ج) بنود تشغيلية (بيد المالك)
H-01 القديم: تدوير Stripe + filter-repo + gitleaks CI.

### د) نقاء
لا stubs، لا كلاسات مسارات مفقودة، Landing مكتمل، 4 أوامر مجدولة (expiry 08:00 / backup 02:00 / doctor-attention 00:00 / subscriptions 01:00) ✓، TODO = 2 (السقالة فقط).
