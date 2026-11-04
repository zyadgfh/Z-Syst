# حالة العمل — النشر + المجموعة (أ)

آخر تحديث: 27 أكتوبر 2026

## 1. الموقع: منشور

| ما | القيمة |
|---|---|
| المشروع | `ziad-portfolio` (`prj_LWndePPxsLhR3RINwVHJgdU4agOZ`) |
| الرابط الدائم | https://ziad-portfolio-zyadgams-projects.vercel.app |
| آخر نشر إنتاجي | `dpl_CXGzcKgJF59ZmeJCX4ejadxCfEtY` — بُني بنجاح |
| المصدر | `/home/user/portfolio/index.html` |

### عقبة واحدة متبقية: حماية النشر

الحساب كله عليه **Vercel Authentication**، فكل رابط يفتح صفحة دخول Vercel بدل الموقع
(تأكدتُ: `jobscope` محمي بنفس الطريقة). الموقع غير مرئي لأي مسؤول توظيف حتى تُطفئها:

> Vercel → المشروع `ziad-portfolio` → Settings → Deployment Protection →
> Vercel Authentication → **Disable** → Save

بعدها يعمل الرابط علنًا دون إعادة نشر.

### لماذا لا توجد ملفات PDF في النشر

أداة النشر تقبل نصًا فقط. أثبتُ بالتجربة أن الملف الثنائي يُخزَّن كما هو كنص base64
(طابقتُ `sha1` للمحتوى مقابل معرّف الملف عند Vercel). لذلك استُبدلت أزرار السير
بأزرار واتساب برسالة جاهزة — وهي أفضل تحويلًا على أي حال: كل طلب سيرة يصل كرقم تواصل.

## 2. الاختبارات: المجموعة (أ) — factories/schema drift

التصحيح: `/home/user/zsyst-fix/fix-02-group-a.patch` (193 سطرًا، 8 ملفات، يشمل fix-01).

### ما أُصلح (مُتحقَّق بالتشغيل، لا بالقراءة)

| العطل | قبل | بعد |
|---|---|---|
| `no column named name` في المنتجات | يظهر في IntegrationTest | **0** |
| `no such table: supplier_performances` | يظهر في SupplierInvoiceTest | **0** |
| `UNIQUE constraint failed: supplier_invoices` | يظهر في SupplierInvoiceTest | **0** |
| `FOREIGN KEY constraint failed` | متعدد | **2** (واحد فقط متبقٍ) |

التفاصيل:
- **الأعمدة**: `tests/Feature/IntegrationTest.php` كان يمرّر `'name'` إلى المنتجات والعمود الحقيقي `productName`. عُدّلت 6 مواضع فقط؛ سطور User/Loyalty/Warehouse لم تُمَس.
- **الجدول**: الترحيل `2026_08_07_000009` ينشئ `supplier_performance` (مفرد) بينما الموديل يبحث عن الجمع. الحل أنسب من كتابة ترحيل جديد: `protected $table` في الموديل.
- **رقم الفاتورة**: المولّد كان `count()+1`، فيتصادم بعد أي حذف. أُعيد كتابته ليقرأ أعلى رقم فعلي ويتقدّم بحلقة تحقّق — تصاعدي وفريد.
- **المفاتيح الأجنبية**: الاختبارات كانت تفترض `business_id = 1` و`supplier_id = 1` دون إنشاء السجلات. أُنشئت في `setUp` للاختبارين.

### القياس الحالي (26 ملف Feature على المستوى الأعلى، 1G لكل ملف)

**13 أخضر · 11 أحمر · 2 OOM**

> تصحيح مهم: رقم «17/30» السابق كان مبنيًا على عدّ ملفات غير دقيق (العدد الحقيقي للملفات 26 لا 30). لا تقارن الرقمين — المقارنة الصحيحة هي بالأعطال المُحدّدة أعلاه، وهي قاطعة.

### العقبة المتبقية داخل المجموعة (أ)

خطأ FK واحد في `test_can_create_supplier_invoice`: تُدرَج الفاتورة بـ `business_id = 3` (مأخوذ من المستخدم) بينما السجل المُنشأ في `setUp` رقمه 1. يحتاج تتبّع أي مفتاح أجنبي تحديدًا يفشل — لم يُنجَز بعد.

## 3. ما تبقّى خارج المجموعة (أ)

- **المجموعة (ب)** — الطرق غير المعرَّفة: `dashboard-reports.overall`، `admin.products.index/store`، `admin.business.upgrade-plan`، والمحاكاة الخاطئة في `TenantContextTest`.
- **المجموعة (ج)** — أعطال تطبيق حقيقية: `TenantResolver.php:16`، وردّ 500 بدل 401.
- **المجموعة (د)** — `PurchaseOrderTest` و`PurchaseOrderNotificationTest` يستهلكان أكثر من 1GB.
- **إعادة بناء الترخيص** — لم تبدأ.

## تحديث (جلسة الإصلاح الثالثة)

| الملف | قبل | بعد |
|---|---|---|
| `IntegrationTest.php` | 11 اختبار / 8 نجاح / 5 أخطاء | **OK — 11 اختبار، 33 تحقّق** |
| `SupplierInvoiceTest.php` | 20 اختبار / 5 فشل / 2 OOM | **OK — 20 اختبار، 82 تحقّق** |

### أعطال حقيقية في التطبيق (لا في الاختبارات)
1. **`User` لا يربط عمود `role` بأدوار Spatie** → أي مستخدم «admin» بلا صلاحية واحدة. أُضيف `ROLE_MAP` + مزامنة في `booted()`.
2. **`AuthenticatedSessionController`** كان يستخدم `$user->role` كاسم دور Spatie (`admin` ≠ `Admin`) فيسقط على `null->permissions`، ويشتقّ التوجيه من أول صلاحية. صار يستخدم الخريطة + بديل آمن (`/admin`).
3. **`PermissionSeeder`** كان يزرع 10 من 73 صلاحية مستخدمة فعليًا → 403 بعد تسجيل الدخول في 13 وحدة. أُضيفت الـ63 الناقصة، وAdmin يأخذ كل شيء عدا وحدات مزوّد الخدمة.
4. **`SupplierInvoiceItem::calculateTotal()`** كانت تنادي `save()` داخل حدث `creating` → تكرار لا نهائي واستنفاد الذاكرة (سبب OOM).
5. **`SupplierInvoiceService`**: الدفعة تُسجَّل ولا تُحدِّث الفاتورة أبدًا، و`status` كان يعتمد على قيمة افتراضية في قاعدة البيانات فيبقى `null` في الذاكرة.
6. **`getDaysUntilDue()`** كان يطرح الكسور فيعرض 29 يومًا لفاتورة مستحقّة بعد 30.
7. **`supplier_invoices.supplier_id`** يشير إلى `parties` لا `suppliers` (تصحيح في الـsetUp).

### التالي
- `InsuranceModuleTest` + `SupplierPerformanceTest` (قيود FK: سجلّات تُنشأ قبل `Business`).
- إعادة تشغيل المسح الكامل لـ26 ملفًا لقياس رقم أخضر حقيقي.
- ملفان ما زالا OOM: `PurchaseOrderTest`، `PurchaseOrderNotificationTest`.


## النتيجة النهائية (جولة كاملة)

```
OK (365 tests, 1039 assertions)   — 12 ثانية
26 / 26 ملف اختبار أخضر · 0 أحمر · 0 نفاد ذاكرة
```

### أعطال جذرية إضافية عُثر عليها وأُصلحت بعد الجولة السابقة

| # | العطل | الأثر في الإنتاج |
|---|---|---|
| 14 | `PurchaseOrderItem::calculateTotal()` تنادي `save()` داخل `creating` | تكرار لا نهائي → نفاد ذاكرة (سبب تعليق الملفين) |
| 15 | خصم البند يُحسب **مبلغًا ثابتًا** بينما الواجهة ترسله **نسبة مئوية** (`max=100`, `subtotal * d/100`) | كل بند مخصوم يُسعَّر خطأً — خسارة مالية مباشرة |
| 16 | `generatePONumber` يستخدم `count()+1` | تصادم على الفهرس الفريد بعد أي حذف → 500 عند إنشاء أمر شراء |
| 17 | إجمالي أمر الشراء لا يُحدَّث عند إضافة/حذف بند | المبالغ المحفوظة أقدم من بنودها |
| 18 | `purchase-orders/{id}` مسجّل **قبل** `pending|overdue|statistics` | 404 على ثلاثة مسارات API |
| 19 | `PurchaseOrderRequest` يفرض `items` و`priority` على PUT/PATCH | تعديل أمر شراء يفشل بـ422 دائمًا |
| 20 | `getStatistics()` تُرجع 5 مفاتيح ثابتة فقط | `Undefined array key "sent"` → 500 في الإحصاءات |
| 21 | `completion_percentage` غير مُصدَّر في الـJSON | الواجهة لا تعرف نسبة الاستلام |
| 22 | اختبارات PO تنادي `/api/...` بينما المسارات تحت `/api/v1/...` | 8 اختبارات 404 كاذبة |

### الملفات المخصّصة
- `fix-03-cumulative.patch` — **40 ملفًا، +767/−329** (يُطبَّق على `5f30445`).
- `fix-01-infrastructure.patch` — البنية التحتية فقط.
