# تحليل مشروع Z-Syst الكامل

> تاريخ التحليل: 2026-10-01 — الفرع: `arena/01a0f4bb-z-syst`
> يغطي هذا المستند البنية الكاملة للمنصة، الوحدات، قاعدة البيانات، التكاملات، وحالة كل جزء.

---

## 1. نظرة عامة

**Z-Syst** منصة SaaS متعددة المستأجرين (Multi-Tenant) لإدارة الصيدليات، مبنية بـ **Laravel 10.50.2 / PHP 8.1+**. تغطي: المخزون (مع FEFO)، المبيعات ونقاط البيع، المشتريات، المستودعات، التأمين، الولاء وCRM، الوصفات الطبية، تتبع الأدوية وسحبها، التقارير، والتدقيق المالي والمخزني.

| العنصر | التقنية |
|---|---|
| الخلفية | Laravel 10 (PHP 8.1+) |
| قاعدة بيانات الإنتاج | **Supabase (PostgreSQL 17.6)** حسب تدقيق 2026-09-22 (مع دعم تاريخي لـ MySQL محليًا وSQLite للاختبارات) |
| المصادقة | Sanctum (API) + جلسات ويب للإدارة + spatie/laravel-permission للأدوار |
| الوحدات | nwidart/laravel-modules (وحدة Landing) |
| PDF/Excel | barryvdh/laravel-dompdf + maatwebsite/excel |
| طوابير/كاش | Redis (إنتاج)، array/sqlite (اختبار) |
| عميل الجوال | Flutter (`pharmacy-store-app-codecanyon-main` — أساس CodeCanyon معدّل) |
| إضافي | Firebase (استضافة وثائق + Firestore rules + Functions تجريبية) |

---

## 2. المعمارية متعددة المستأجرين

- **الكيان الجذر:** `Business` (الصيدلية/المستأجر) ← ينتمي إليه المستخدمون والمنتجات والمخزون وكل الحركات.
- **السياق:** `TenantContextMiddleware` و`EnsureBusinessContext` و`TenantResolver` و`TenantService` تحدد المستأجر من المستخدم المصادق وتحصر الاستعلامات عبر نطاقات `forBusiness($id)` الموجودة في معظم النماذج.
- **الاشتراكات:** `Plan` / `PlanSubscribe` / `SubscriptionService` + middleware `CheckSubscriptionLimits` لفرض حدود الخطة.
- **الصلاحيات:** أدوار وصلاحيات عبر spatie/permission مع مجموعات صلاحيات لكل وحدة (تُزرع عبر Seeders مخصصة: `WarehousePermissionsSeeder`, `InsurancePermissionsSeeder`, …).

---

## 3. بنية المجلدات

```
Z-Syst/
├── app/                     # التطبيق الرئيسي
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── API/         # 57 متحكم REST (كل وحدات الأعمال)
│   │   │   ├── Admin/       # لوحة تحكم المنصة (إدارة الأعمال/الخطط/المستخدمين…)
│   │   │   └── Auth/        # مصادقة ويب
│   │   └── Middleware/      # 21 middleware (أمان، تينانت، اشتراكات، عرض…)
│   ├── Models/              # 103 نموذج Eloquent
│   ├── Services/            # 42 خدمة أعمال (طبقة المنطق الأساسية)
│   ├── Mail/ + Notifications/ + Policies/ + Traits/ + Library/ (بوابات الدفع المصرية)
├── Modules/Landing/         # وحدة الموقع العام (nwidart modules): مدونة، مميزات، خطط، شهادات
├── database/                # 80 هجرة + 27 Seeder + مصانع الاختبارات
├── routes/                  # api.php (346 سطر), admin.php, web.php, auth.php
├── tests/Feature            # ~30 ملف اختبار ميزة
├── Flutter/ + pharmacy-store-app-codecanyon-main/  # تطبيقات الجوال
├── functions/ + z-syst/ + dataconnect/ + firestore.* # Firebase (تجريبي/وثائق)
├── docs/                    # ~27 وثيقة تشغيل وتطوير
└── ملفات إرشادية للمساعدات البرمجية (.claude, .agents, .windsurf, …)
```

**ملاحظات على التكرار/الإرث:**
- `pharmacy-store-app-codecanyon-main` هو تطبيق Flutter الأساسي (اسم الحزمة `mobile_pos`) — مأخوذ من CodeCanyon ومعدّل ليتكامل مع الـ API.
- `functions/` و`z-syst/` كلاهما مشاريع Firebase Functions (تكرار)؛ `firebase.json` يستضيف مجلد `documentation/pharmacy-doc` فقط.
- `dataconnect/` + `src/dataconnect-generated`: تجارب Google Data Connect.
- `refactor_acnoo.php/.py`, `update_branding*.php`, `inspect_options.php`, `routes.txt`, ملف `d` الفارغ، `temp_plans.html`: مخلفات جلسات تطوير — مرشحة للتنظيف.

---

## 4. وحدات الأعمال (النطاقات الوظيفية)

كل وحدة تتبع النمط: **مهاجرات → نماذج → خدمة → متحكم API → مسارات في `routes/api.php` → اختبارات Feature**.

| الوحدة | الحالة | الملفات الأساسية |
|---|---|---|
| المنتجات والأصناف والوحدات والمصنّعون | ✅ مكتملة | `ZSystProductController`, `Product` |
| المشتريات + المرتجعات | ✅ مكتملة | `PurchaseController`, `PurchaseReturnController` |
| المبيعات + المرتجعات + المدفوعات | ✅ مكتملة | `ZSystSaleController`, `SaleReturnController` |
| أوامر الشراء (PO) | ✅ مكتملة + إشعارات المورد (أُضيفت 2026-10-01) | `PurchaseOrderService` |
| فواتير الموردين + المدفوعات | ✅ مكتملة + عكس المدفوعات عند الإلغاء (2026-10-01) | `SupplierInvoiceService` |
| إدارة الموردين (تقييم/أداء/عقود) | ✅ مكتملة | `SupplierService` |
| استلام البضاعة GRN + فحص الجودة | ✅ مكتملة | `GRNService`, `QualityService` |
| المستودعات المتعددة + التحويلات | ✅ مكتملة | `WarehouseService`, `WarehouseStockService` |
| تتبع الأدوية + السحب (Recall) | ✅ مكتملة | `TraceabilityService`, `BatchLot`, `RecallEvent` |
| التأمين (شركات/بوليصات/مطالبات/تغطيات) | ✅ مكتملة | `InsuranceService` |
| الولاء + CRM | ✅ مكتملة | `LoyaltyService` |
| الإيصالات والطباعة (PDF) | ✅ مكتملة (أُصلح عرض أسماء المنتجات 2026-10-01) | `ReceiptService` + dompdf |
| FEFO (الأول انتهاءً الأول خروجًا) | ✅ مكتملة | `FefoService` |
| تدقيق المخزون والتدقيق المالي | ✅ مكتملة | `StockAuditService`, `FinancialAuditService` |
| تنبؤ المبيعات + الطلب التلقائي | ✅ مكتملة | `PredictionService`, `AutoOrderService` |
| تنبيهات اهتمام الأطباء | ✅ مكتملة + إشعارات فعلية (2026-10-01) | `DoctorAttentionService` |
| إدارة الأطباء/العملاء (Parties) | ✅ مكتملة | `Party` (type: doctor/customer/supplier) |
| الاشتراكات والخطط | ✅ مكتملة | `SubscriptionService` |
| الباركود | ✅ مكتملة | `BarcodeService` |
| الموقع العام (Landing) | ✅ أُصلحت المسارات والمزوّد (2026-10-01) | `Modules/Landing` |
| النسخ الاحتياطي + إدارة المفاتيح | ✅ مكتملة | `BackupService`, `RotateSettingsKey` |
| تحليلات المنتج | ✅ أُصلح خطأ صياغة قاتل (2026-10-01) | `ProductAnalyticsService` |

**تدفق الـ API:** كل المسارات تحت `/api/v1` بمصادقة `auth:sanctum`، مع حدود معدل (throttle) متفاوتة، ووسطاء سياق الأعمال للوحدات الحساسة. توجد أيضًا مسارات عامة محدودة جدًا (تسجيل/دخول/إعادة تعيين + واجهة `FeatureStatus`).

---

## 5. قاعدة البيانات

- **80 هجرة** مرتبة زمنيًا (2014 → 2026-08) تغطي: المنصة (خطط/أعمال/مستخدمون)، الكتالوج (منتجات/أصناف)، الحركات (مبيعات/مشتريات/مرتجعات)، المخزون (مخزون/حركات/تحويلات/مستودعات)، التأمين، التتبع، الولاء، الإيصالات، التدقيق، الاشتراكات، المدفوعات، سير الموافقات، الميزانيات، الجودة، وإدارة الموردين.
- **فخ التسمية:** جداول المنصة القديمة تستخدم **camelCase** في الأعمدة (`productName`, `productCode`, `totalAmount`, `dueAmount`, `paidAmount`, `companyName`, `will_expire`…) بينما الوحدات الأحدث (2026) تستخدم **snake_case** (`total_amount`, `paid_amount`…). قاعدة Supabase الإنتاجية تعمل بـ **snake_case** (حسب تدقيق 2026-09-22: `sales.sale_date`, `sales.total_amount`, `products.product_name`, `products.product_code`)، لذلك توجد **فجوة مواءمة** بين بعض نماذج/هجرات Laravel ومخطط Supabase — مهمة معلنة في التدقيق.
- أُضيف في 2026-10-01 عمود `status` لجدول `businesses` (كان مفهرسًا دون وجوده → فشل `migrate`) وعمود `status` لجدول `testimonials`.
- فهرسة أداء + قيود مفاتيح أجنبية أُضيفت في هجرات `2026_08_07_*`.

---

## 6. الأمان والامتثال

- **طبقات الحماية:** `SecurityCheck`, `SecurityHeaders`, `XSSProtectionService`, `CSRFProtectionService`, `AdminMiddleware`, `TenantAccessCheck`, فحوصات CSRF/XSS، وGitleaks في CI.
- **تدقيق:** `AuditLogger`/`AuditService` + جداول `audit_logs` و`financial_audit_logs` وسجلات الحركات.
- **تشفير الإعدادات الحساسة** عبر `Setting` مع أمر تدوير مفتاح `RotateSettingsKey`.
- **حدود معدل** على مجموعات API.
- حوادث سابقة موثقة: كلمة مرور Gmail تسربت في `.env.example` وأُزيلت (التدقيق يوصي بتدويرها خارجيًا).
- مسارات التطوير الخطرة (`/publish`, `/reset`, `/cache-clear`) أصبحت محمية ببيئة التشغيل فقط (2026-10-01).

---

## 7. الاختبارات وCI/CD

- **~30 ملف Feature tests** تغطي: التأمين، المستودعات، التتبع، الولاء، الإيصالات، أوامر الشراء، فواتير الموردين، الباركود، التدقيق، سياق المستأجر، الأمان، حدود المعدل، ورأس الصفحة العامة.
- البيئة: SQLite في الذاكرة + بريد array (معزولة بالكامل).
- أُضيف في 2026-10-01: **13 مصنع نماذج كانت مفقودة** (كانت تمنع تشغيل معظم الاختبارات) + اختبارات الإشعارات الجديدة.
- **الـ CI:** `.github/workflows/` — `ci.yml`, `ci-cd.yml`, `phpunit.yml` (SQLite), `secret-scan.yml` (Gitleaks). لا توجد أسرار Supabase داخل المستودع (تُحقن في بيئة النشر).

---

## 8. التكاملات الخارجية

| التكامل | الوضع |
|---|---|
| **Supabase PostgreSQL** | قاعدة الإنتاج الفعلية (تدقيق 2026-09-22: مشروع نشط، RLS مفعّل، بيانات حقيقية). **لا توجد أي بيانات اتصال في المستودع** — تُدار خارجيًا. |
| بوابات دفع مصرية | `app/Library/`: Vodafone Cash, Orange Cash, InstaPay, Fawry, بطاقة بنكية, دفع نقدي (مسارات ويب جاهزة) |
| البريد | `Mail` (Gmail SMTP افتراضيًا) — الآن تستخدمه خدمة الإشعارات الجديدة |
| SMS | سائق `log` افتراضيًا عبر `NotificationService`؛ قابل للتبديل بإضافة بوابة |
| Firebase | استضافة الوثائق + Firestore rules (نموذج مستخدم/أعمال) + Functions تجريبية |
| Stripe | مذكور في أدلة الإعداد كخيار اشتراكات |

---

## 9. ما أُصلح/أُكمل في جلسة 2026-10-01

1. ملف مسارات `Modules/Landing/routes/api.php` التالف → أُعيد بناؤه.
2. تعطّل تحميل مسارات وحدة Landing → فُعّل `RouteServiceProvider` مع حارس ذكي.
3. خطأ صياغة قاتل في `ProductAnalyticsService` (قوس مفقود).
4. منظومة الإشعارات الكاملة (`NotificationService` + `BusinessMail` + قالب + إعداد SMS) وتنفيذ كل الـ TODO في الخدمات الثلاث.
5. عمود `businesses.status` المفقود (كان يكسر التهجير والفهرس) + نموذج + متحكم.
6. عمود `testimonials.status` + متحكم + فلترة الـ API.
7. دوال `status` المفقودة في متحكمي المستخدمين والأعمال والشهادات.
8. تصحيح اسم `ZystPlanController` في المسارات الإدارية.
9. 13 مصنعًا مفقودًا للاختبارات + اختباران جديدان.
10. `Product::name` accessor (يصلح أسماء المنتجات في الإيصالات/التقارير/التتبع).
11. حماية مسارات التطوير الخطرة حسب البيئة.

---

## 10. المخاطر والمهام المتبقية

1. **مواءمة مخطط Laravel ↔ Supabase** (camelCase مقابل snake_case) — يجب حسمها قبل أي نشر، ويفضل عبر طبقة خرائط أعمدة أو تحديث النماذج.
2. **تطبيق تغييرات الجلسة على Supabase**: إضافة `businesses.status` و`testimonials.status` هناك (إن لم يكونا موجودين) مع سياسات RLS.
3. تدوير كلمة مرور Gmail التي تسربت سابقًا.
4. اختبارات عزل المستأجر بين الأعمال (موصى بها في التدقيق).
5. تنظيف مخلفات الجلسات من جذر المستودع (`d`, `routes.txt`, `temp_plans.html`, سكربتات `update_*`).
6. مولد صور الباركود وخدمة ضغط الصور ما زالا placeholder (يحتاجان حزمًا خارجية).
7. لا يمكن تشغيل الاختبارات في بيئة التحليل الحالية (لا يوجد PHP/Composer) — التحقق الرسمي عبر GitHub Actions.
