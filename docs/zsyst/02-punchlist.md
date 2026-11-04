# قائمة الإصلاح المتبقية — بعد إصلاح البنية التحتية

**الحالة الآن:** 17 ملفًا أخضر من 30 (كان 11) + 101/101 اختبار وحدة + صفر "بلا نتيجة".
**المتبقي:** 47 مشكلة (24 إخفاقًا + 23 خطأً) + ملفان ينفد منهما الذاكرة.

---

## ✅ ما أُصلح (جاهز في `fix-01-infrastructure.patch`)

| # | الإصلاح | الأثر |
|---|---|---|
| 1 | حذف `CheckToken` من `app/Http/Kernel.php` | أزال القفل المدمّر الذي كان يمحو `vendor/laravel` |
| 2 | إضافة `APP_KEY` إلى `phpunit.xml` | 3 ملفات خضراء فورًا |
| 3 | `.env.example`: redis → file/sync/log | `composer install && artisan migrate` يعمل من الصفر |

---

## 🔴 المتبقي، مرتّبًا بحسب العائد على الجهد

### المجموعة أ: انفصال الـfactories عن المخطط (أسهل، وأعلى عددًا)
| المشكلة | الملف | الإصلاح |
|---|---|---|
| `table products has no column named name` | `tests/Feature/IntegrationTest.php` س 394، 398، 424، 433، 457 | `'name'` → `'productName'` في استدعاءات `Product::factory()` فقط (لا تلمس س 146/284/296/339/344 فهي User/Loyalty/Warehouse) |
| `UNIQUE constraint failed: supplier_invoices.invoice_number` | `app/Models/SupplierInvoice.php` س 401 | المولّد يُنتج نفس الرقم داخل الاختبار الواحد؛ أضف معرّفًا فريدًا |
| `no such table: supplier_performances` | `database/migrations` | **migration ناقصة كليًا** — المودل `SupplierPerformance` موجود بلا جدول |
| `FOREIGN KEY constraint failed` | `InsuranceModuleTest`, `SupplierInvoiceTest` | الاختبار ينشئ سجلًا بـ`business_id` دون إنشاء `Business` أولًا |

### المجموعة ب: أسماء مسارات ومحاكاة
| المشكلة | الملف | الإصلاح |
|---|---|---|
| `Route [dashboard-reports.overall] not defined` | `IntegrationTest` س ~470 | المسار غُيّر اسمه أو حُذف؛ صحّح الاسم أو احذف الاختبار |
| `SessionGuard::shouldReceive does not exist` | `TenantContextTest` | استخدم `Auth::shouldReceive()` على المدير لا على الحارس |

### المجموعة ج: أخطاء في كود التطبيق (حقيقية)
| المشكلة | الملف | الإصلاح |
|---|---|---|
| `Undefined property: $role` | `app/Services/TenantResolver.php:16` | الكود يقرأ `$user->role` على كائن مجهول؛ أضف تحقّقًا |
| `PermissionDoesNotExist` | `IntegrationTest` | الصلاحيات غير مُزروعة في بيئة الاختبار |
| `401 متوقع لكن 500` | `ProtectedApiRoutesTest`, `ProductAnalyticsTest` | معالج الاستثناءات يحوّل عدم المصادقة إلى 500 |

### المجموعة د: نفاد الذاكرة
| الملف | الحالة |
|---|---|
| `PurchaseOrderTest` | تسرّب ذاكرة يتجاوز 1GB أثناء التشغيل |
| `PurchaseOrderNotificationTest` | نفس السبب على الأرجح |

---

## كيف تطبّق

```bash
cd Z-Syst
git apply /path/to/fix-01-infrastructure.patch
bash /path/to/cleanup.sh
composer install
php artisan key:generate
php artisan migrate
vendor/bin/phpunit tests/Unit          # يجب: 101/101 أخضر
vendor/bin/phpunit                      # يجب: 17 ملف Feature أخضر
```

**أو:** امنحني صلاحية الدفع على GitHub فأطبّقها وأكمل المجموعة (أ).
