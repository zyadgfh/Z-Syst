# تقرير تشخيص Z-Syst — 27 أكتوبر 2026

> أُعدّ بتشغيل المشروع فعليًا في بيئة معزولة (PHP 8.4 + Composer + SQLite)، لا بقراءة الكود فقط.

---

## 🔴 الاكتشاف الأول: مِسح destructive داخل حزمة خارجية

**الملف:** `vendor/safiull/laravel-installer/src/Middleware/CheckToken.php`
**مُسجّل في:** `app/Http/Kernel.php` داخل مجموعة `web` (يعمل على كل طلب ويب)

```php
$excludedPaths = ['', '/', 'login', 'update', 'envato/purchase-code/verify/process'];
$currentPath = trim($request->path(), '/');

if (!file_exists(base_path('vendor/autoload1.php'))
    && !str_contains($currentPath, 'install')
    && !in_array($currentPath, $excludedPaths)) {
    File::cleanDirectory(base_path('vendor/laravel'));   // ← يمحو إطار العمل كاملًا
}
```

**ماذا يفعل:** إن لم يجد ملف الترخيص `vendor/autoload1.php`، يمحو مجلد `vendor/laravel` بأكمله (**1,943 ملفًا**) — أي Laravel نفسه. التطبيق ينهار فورًا.

**كيف اكتشفته:** اختبار `IntegrationTest::test_user_registration_and_login` كان يمحو الاعتماديات. شطّبتُ الطلب حتى وصلتُ إلى الوسيط. `/login` مستثنى فنجا، `/register` غير مستثنى فمُحي.

**لماذا يخصّك هذا شخصيًا:**
1. هذا هو السبب الحقيقي وراء "لا نتيجة" في **16 ملف اختبار من 30** — أول طلب ويب يدمر الإطار، وكل ما بعده يموت بخطأ قاتل. **ليس خطأ في كودك.**
2. في الإنتاج: أي زائر يفتح أي صفحة غير مستثناة على نسخة غير مُفعّلة سيُسقط الموقع بالكامل.
3. الحزمة `safiull/laravel-installer` هي مثبّت ترخيص من **Envato/CodeCanyon** — أي أن المشروع مبني على **قالب تجاري مرخّص**. وهذا يؤثّر مباشرة على كيف يحق لك عرضه في بورتفوليو.

---

## 🟠 الاكتشاف الثاني: سطر إعداد ناقص

`phpunit.xml` يعرّف `APP_ENV`, `CACHE_DRIVER`, `DB_*`, `SESSION_DRIVER`, `QUEUE_CONNECTION` … **ولا يعرّف `APP_KEY`**.

النتيجة: كل اختبار يلمس التشفير أو الجلسة يسقط بـ `MissingAppKeyException`.

**الأثر المقاس:** إضافة المفتاح وحدها حوّلت 3 ملفات إلى خضراء فورًا
(`ExampleTest`, `LandingHomeTest`, `SecurityHeadersTest`).

---

## 📊 الحالة الحقيقية لطقم الاختبارات

| الحالة | قبل | بعد إصلاح APP_KEY | بعد تعطيل CheckToken |
|---|---|---|---|
| ملفات خضراء | 11 | 14 | **22 / 30** |
| ملفات حمراء | 3 | 3 | **6** |
| ملفات بلا نتيجة (إطار مدمَّر) | **16** | 13 | **0** |
| ملفات تنهار بالذاكرة | 2 | 2 | **2** |

### ما تبقّى أحمر (أخطاء حقيقية في الكود)
| الملف | الأخطاء | الإخفاقات |
|---|---|---|
| SupplierInvoiceTest | 2 | 7 |
| SupplierTest | 2 | 5 |
| InsuranceModuleTest | 6 | 2 |
| IntegrationTest | 10 | 1 |
| TenantContextTest | 2 | 2 |
| TenantAndBackupTest | 2 | 0 |
| ProtectedApiRoutesTest | 0 | 3 |
| AuditLoggingTest | 0 | 1 |
| PartyApiTest | 0 | 1 |
| ProductAnalyticsTest | 0 | 1 |
| SecurityTest | 0 | 1 |
| PurchaseOrderTest | **نفاد ذاكرة** | — |
| PurchaseOrderNotificationTest | **نفاد ذاكرة** | — |

### الأسباب الجذرية المعروفة حتى الآن
- `IntegrationTest`: المسار `dashboard-reports.overall` غير معرّف (اسم مسار تغيّر أو حُذف).
- `TenantContextTest`: `Auth::guard()->shouldReceive()` — محاكاة خاطئة لكائن الحارس.
- `TenantAndBackupTest`: `app/Services/TenantResolver.php:16` يقرأ `$user->role` على كائن لا يملكها.
- `ProtectedApiRoutesTest` و`ProductAnalyticsTest`: الطلب غير المُصادَق يُرجع `500` بدل `401` (وسيط أو معالج استثناء).
- نفاد الذاكرة في `PurchaseOrder*`: تسرّب ذاكرة يتجاوز 1GB.

### يعمل بالفعل ✅
- الصفحة الرئيسية و`/login` يستجيبان `200` بمحتوى حقيقي.
- **كل الـ100+ migration تمر على قاعدة نظيفة** بلا خطأ.
- **اختبارات الوحدة: 101/101 خضراء** (245 تأكيدًا).
- 22 ملف Feature أخضر، منها `Barcode` (28 اختبارًا) و`GRN` (29 اختبارًا).

---

## ⚠️ ما لم أفعله

**لم أطبّق أي إصلاح على مستودعك.** لا أملك صلاحية الكتابة على GitHub، وكل ما فعلته كان في نسخة معزولة. لديك ثلاث طرق:
1. أوّلد لك ملف **patch** تطبّقه بأمر واحد.
2. تمنحني صلاحية الدفع على GitHub.
3. أعطيك الأوامر خطوة بخطوة وتنفّذها أنت.

---

## 🧭 ما يحتاج قرارك الآن

وجود مثبّت ترخيص Envato يغيّر طريقة عرض المشروع. سيرك الذاتية تقول بصدق «لم أكتب قاعدة المشروع كاملة بصورة مستقلة» — وهذه هي الرواية الصحيحة. الخيارات في الرسالة التالية.
