# Vulnscan — تقرير الفحص الأمني الشامل
# Z-Syst (Laravel SaaS لصيدليات) — 2026-11-26

**Mode:** offline · code-vulnscan skill (`.github/skills/code-vulnscan`)
**Target:** `zyadgfh/Z-Syst` @ `arena/62680a42-z-syst` (base `691caab` = main بعد PR #34/#35)
**Scope:** 505 ملف PHP · 674 مسار (admin 319 / api 302 / web 39 / auth 14) · Blade · Supabase RLS · CI
**Baseline:** docs/zsyst/21, 22, 23 (2026-11-06/07) — أُعيد التحقق من كل نتيجة سابقة بالكود الحالي
**القاعدة الذهبية طُبّقت:** كل نتيجة مؤكَّدة مبنية على source → sink → path مقروءة من الكود الفعلي؛ المرشحات غير المُتحقَّق منها استُبعدت.

---

## Executive Summary

**النتيجة: 1 High · 3 Medium · 3 Low · 2 Informational** — بعد إزالة **9 مرشحات كاذبة** تحققت منها يدوياً، وإثبات أن **6 إصلاحات من الفحص السابق مكتملة**.

أخطر ما وُجد: **هالة البيع في POS ما زالت تتلقى `total_amount`/`loss_profit`/`purchase_price` من العميل** رغم قفل أسعار الأسطر — أي تزوير إيرادات عبر API رغم إصلاح H-01 الجزئي. كذلك **تعديلات `business-categories` بلا بوابة**، واشتراكات API بلا بوابة دور.

| # | Severity | CWE | الموضوع | الحالة |
|---|---|---|---|---|
| F-01 | 🟠 High | CWE-840 | تزوير إجماليات فاتورة البيع من العميل (H-01 متبقٍ) | جديد/متبقٍ |
| F-02 | 🟠 Medium | CWE-862 | تعديلات business-categories بلا صلاحيات | جديد |
| F-03 | 🟠 Medium | CWE-862 | mutations اشتراكات API بلا بوابة دور | مفتوح (متابعة PR #34) |
| F-04 | 🟡 Low | CWE-89/639 | `SupplierInvoiceRequest.product_id` بلا TenantRules | مفتوح (متابعة PR #34) |
| F-05 | 🟡 Low | CWE-200 | قراءات منصّاتية بلا بوابة (planUsage/proration/categories filter) | جديد |
| F-06 | 🟡 Low | CWE-770 | `POST /payments/*` عام بلا throttle | جديد جزئياً |
| F-07 | ℹ️ Info | CWE-89 | `SqlDate::format` يُدرج format في SQL (كل المتاحفات ثابتة اليوم) | تأمين مستقبلي |
| F-08 | ℹ️ Info | CWE-20 | `sale_date` بلا تحقق صيغة | متانة |

---

## Findings Detail

### 🟠 F-01 — تزوير إجماليات فاتورة البيع من العميل (residual H-01)
**CVSS:** 7.5 `AV:N/AC:L/PR:L/UI:N/S:U/C:L/I:H/A:L` · **CWE-840** (Business Logic) / CWE-915
**المسار (مقروء من الكود):**
- السعر/السطر **مُصلَح ✓**: `ZSystSaleController::store` يقارن `products.*.price` بـ `sales_price` من الخادم (سماحية 2%، وإلا استثناء بصلاحية `sale.price-override` + audit) — هذا جزء H-01 الأصلي مغلق.
- **لكن الحقول التجميعية ما زالت من العميل:**
  - `'total_amount' => 'nullable|numeric'` (سطر 99) — لا يُعاد احتسابها من الأسطر أبداً (لا يوجد أي إعادة حساب في الملف).
  - `'products.*.loss_profit' => 'required|numeric'` (106) و`'purchase_price'` تُخزَّن حرفياً في `sale_details` (311، 344) وتُغذّي تقارير الربح (`AccountingService`، `StatisticsController`).
  - **خطأ أولوية `+`**: `Sale::create($request->only([... 'loss_profit' ...]) + ['loss_profit' => array_sum(...)-discount, ...])` (246-263) — في PHP اليسار يفوز؛ إذا أرسل العميل `loss_profit` أعلى المستوى تتجاوز القيمة **المحسوبة** (مفتاح ميت في الكود)، و`loss_profit` غير موجود في قواعد التحقق فلا يرفضه `validate()`.
**الاستغلال:** موظف الكاشير (بصلاحية `sales-create` فقط) يرسل عبر API: `total_amount=0, is_paid=1, paid_amount=0` مع أسطر بأسعار صحيحة → فاتورة مدفوعة بصفر، مخزون يُخصم بالكامل، الإيراد المُسجَّل صفر — سرقة مباشرة. أو يزوّد `loss_profit`/`purchase_price` لتزوير تقارير الربح.
**الإصلاح:** اشتق `total_amount = Σ(lines price×qty) − discount + tax` و`loss_profit` من `purchase_price` **المحمَّل من الدفعة** في الخادم، وضع القيم المحسوبة في جانب `+` الأيمن **بلمسار آخر** (أو استخدم `array_merge($computed, $only)` بترتيب صحيح)، وأضف `min:0` لكل المبالغ، وارفض اختلاف الإجمالي عن مجموع الأسطر.

### 🟠 F-02 — تعديلات business-categories بلا بوابة صلاحيات
**CVSS:** 5.4 `AV:N/AC:L/PR:L/UI:N/S:U/C:N/I:L/A:L` · **CWE-862**
**Evidence:** `ZSystBusinessCategoryController::__construct` يضع `permission:business-categories-read` على `index/show` **فقط** — لا بوابة لـ `store/update/destroy/deleteAll/status/zsystFilter/export`، ومسارات `routes/admin.php` (56-62) بلا middleware إضافي. خريطة القدرات (`config/supabase.php`) لا تمنح `business-categories-*` إلا لـ superadmin، لكن **غياب البوابة يعني أن `AdminMiddleware` وحده يكفي**: أي `shop-owner`/`admin`/`superadmin` يمرّ.
**الاستغلال:** مستخدمو الأدوار المالكة في أي صيدلية يعدّلون/يحذفون تصنيفات المنصّة (`Plan::whereIn`-style `deleteAll` بلا scope — التصنيفات منصّة).
**الإصلاح:** أضف في الـ constructor:
`$this->middleware('permission:business-categories-create')->only('store')` + update/delete للـ edit/update/status/destroy/deleteAll، وبوابة `business-categories-read` على filter/export.

### 🟠 F-03 — مواعات اشتراكات API بلا بوابة دور/صلاحية
**CVSS:** 5.4 · **CWE-862** — *متابعة PR #34 الموثّقة، ما زالت مفتوحة*
**Evidence:** `routes/api.php:151-157` داخل مجموعة `auth:sanctum + tenant.context + tenant.check` فقط — `upgrade/downgrade/cancel/renew/generate-invoice` بلا `role:`/`permission:`. `SubscriptionController` يعتمد على TenantScoped لمنع العبور بين المستأجرين (مُتحقَّق ✓) لكن **أي مستخدم مصادَق من الصيدلية — بما فيهم `staff` — يستطيع إلغاء/تجديد/ترقية اشتراك صيدليته** (رفيق المقارنة: النسخة المماثلة في `routes/admin.php:282-286` عليها `role:superadmin` ✓).
**الإصلاح:** أضف `permission:subscriptions-update` أو `role:shop-owner|admin` على الـ mutations في المسار (مع بقاء عرض `usage/check-limits` للجميع).

### 🟡 F-04 — `items.*.product_id` بلا TenantRules
**CVSS:** 3.9 · **CWE-639** — *متابعة PR #34 الموثّقة*
**Evidence:** `SupplierInvoiceRequest.php:36` → `'items.*.product_id' => 'nullable|exists:products,id'` — يقبل معرّف منتج عملٍ آخر (الموديل `SupplierInvoice` معزول بالـTrait، لكن البنود تُخزَّن بمعرّفات أجنبية → اتساخ مالي/تسريب عند الـ joins). بقية الـ FormRequests طُبّق عليها `TenantRules::exists` في PR #34.
**الإصلاح:** `'exists:products,id,where,business_id,'.auth()->user()->business_id` أو `TenantRules::exists('products','id')`.

### 🟡 F-05 — قراءات منصّاتية بلا بوابة
**CVSS:** 3.3 · **CWE-200**
**Evidence:** `plans/{plan}/usage` و`plans/calculate-proration` خارج قوائم `only()` في `ZSystPlanController` constructor؛ `business-categories/filter` + exports خارج بوابة القراءة؛ `Route::resource('business')` action `show` مسجَّل بلا `function show` (500 بدل 403 — سلوك خطأ). تأثير: إحصاءات تبنّي خطط المنصّة/تصنيفاتها لأي دور مالك.
**الإصلاح:** أضف `plans-read` على planUsage/calculateProration، و`business-categories-read` على filter/export، وأزل `show` من الـ resource.

### 🟡 F-06 — بدء دفع عام بلا throttle
**CVSS:** 4.3 · **CWE-770**
**Evidence:** `routes/web.php:13-14` — `GET /payments-gateways/{plan_id}/{business_id}` و`POST /payments/{plan_id}/{gateway_id}` بلا `throttle:` (المقارنة: `store/checkout` عليه `throttle:20,1` ✓، `payment/success|failed` عليه `throttle:30,1` ✓، الويب هوكات `120,1` ✓). بدونه يمكن مضاعفة معاملات بوابة الدفع/ملء الجداول بضغط عام.
**الإصلاح:** `->middleware('throttle:20,1')` على المسارين.

### ℹ️ F-07 — `SqlDate::format` يُدرج `$format/$column` داخل SQL
**Evidence:** `app/Support/SqlDate.php` → `sprintf("DATE_FORMAT(%s, '%s')", $column, $format)`؛ **كل المتاحفات الحالية ثابتة** (`'%Y-%m-%d'` واسم أعمدة ثابت — grep على 5 مواقع ✓) → لا استغلال اليوم، لكن أي استدعاء مستقبلي بمدخل مستخدم = SQLi مباشر.
**الإصلاح:** تحقّق `preg_match('/^[%YymdHMi_s-]+$/', $format)` وأسماء الأعمدة من allowlist.

### ℹ️ F-08 — `sale_date` بلا تحقق صيغة
**Evidence:** `'sale_date' => 'required|string'` (ZSystSaleController:92) — يُقبل أي نص؛ خطأ إدخال → استثناء قاعدة بيانات (500) بدل 422.
**الإصلاح:** `date_format:Y-m-d H:i:s` (أو `date`).

---

## False Positives المُتحقَّق منها (9) — لا تُبلَّغ كثغرات

| المرشح | السبب بعد التحقق |
|---|---|
| secrets ×5 high + ×3 medium (`Totp.php`, `supabase/config.toml`, `valkey-compat-test.py`) | أبجدية base32 رسمية RFC4648، استبدال `env()` في config.toml، وسلسلة مساعدة في docstring — **لا سر حقيقي** |
| guzzle CVE-2022-31090 (dependency.py) | الأداة قرأت قيد `composer.json` (^7.2) بينما **`composer.lock` = 7.15.5** (≥7.5.1 المُصلَح) + composer audit CI أخضر |
| "مسارات business/plans بلا بوابة" (كادت Critical) | البوابات في **constructor المتحكم** (`permission:business-delete` إلخ) + خريطة القدرات تمنح `business-*`/`plans-*` **لـ superadmin فقط** (`['*']`) — مطابقة لتعليق الملف. `AdminMiddleware` يرفض staff تبعاً للـallowlist المُختبَر في `SecurityHardeningTest` |
| `users/*` (delete-all/status/destroy) | `User extends AppAppUser` → يرث `TenantScoped` global scope ✓ + حماية superadmin في الكود ✓ + `store` يثبت `business_id` من المصادَق ✓ |
| `$request->all()` المتبقٍ (webhook, Normalize middleware) | الويب هوك يتحقق HMAC عبر `PaymentGatewayService` (توقيع مربوط بالنتيجة)؛ Middleware للتطبيع فقط |
| `{!! !!}` في term/policy (M-04 سابق) | أصبح محاطاً بـ `HtmlSanitizer::clean()` ✓ |
| `SetSupabaseContext` SQL | `$role` ثلاثي ثابت، الـclaims parameterized، RLS يُفعَّل داخل transaction ✓ |
| Webhook CSRF except | `L-01` مُصلَح: التبقّي `webhooks/stripe/*` فقط + تعليق موثّق |

---

## حالة إصلاحات الفحص السابق (23-deep-scan) — إعادة تحقق

| البند السابق | الحالة اليوم |
|---|---|
| 🔴 C-01 عزل 10 موديلات (incl. User) | ✅ **مُصلَح** — `TenantScoped` على كل الموديلات ذات business_id (عدا AuthUser/Business by design) |
| 🟠 H-01 ثمن POS من العميل | ⚠️ **جزئي** — أسعار الأسطر مُصلَحة؛ الإجماليات/الأرباح لا (→ F-01) |
| 🟠 M-01 سباق FEFO | ✅ **مُصلَح** — `lockForUpdate()` داخل الترانزكشن + خصم محروس بشرط حارس |
| 🟠 M-02 تعيين جماعي Purchase | ✅ **مُصلَح** — `->only([...])` صريح في كل المتحكمات |
| 🟠 M-03 تعيين جماعي أدمن | ✅ **مُصلَح** — `->only()`/`->validated()` فقط (grep على `create($request` = صفر) |
| 🟠 M-04 XSS في Blade | ✅ **مُصلَح** — `HtmlSanitizer::clean()` |
| 🟡 L-01 CSRF except ميت | ✅ **مُصلَح** — `webhooks/stripe/*` فقط |
| فجوة rate-limit للويب العام | ✅ **جزئي** — auth/checkout/webhooks مُعلَّمة؛ `POST /payments/*` لا (→ F-06) |

## ما تم فحصه ونظيف (بأدلة)
- **SQL injection:** كل `whereRaw/selectRaw/orderByRaw/DB::statement` المُفحوصة parameterized أو ثابتة؛ لا `DB::raw` بمدخل مستخدم.
- **عزل المستأجرين (النواة):** middleware `tenant.context` بعد auth في مجموعات API ×4 + `tenant.check` على /admin؛ `trustedBusinessId()` يستخرج الهوية من المصادَق فقط (403 لمن بلا business)؛ RLS Supabase عبر `set_config` parameterized.
- **Authentication:** throttles على auth (`auth-guest`/`auth-reset`/signed URLs)؛ `SESSION_SECURE_COOKIE` افتراضياً true في production؛ `APP_DEBUG=false`؛ `same_site=lax`.
- **التشفير/السرّات:** لا hash ضعيف، لا `eval/unserialize`، لا مفاتيح مضمّنة (9/9 مرشحات كاذبة).
- **Webhooks:** توقيع مربوط بالنتيجة (`signatureCoversOutcome`) — تصميم مضاد للاحتيال موثّق.
- **الصلاحيات:** `AdminMiddleware` deny-by-default لـ staff مع allowlist مُختبَر (`SecurityHardeningTest`)؛ system-settings/gateways/roles بوابات constructor + فحص superadmin إضافي.
- **الرفع:** allowlist صور `image|max:2048/2048` في المسارات المفحوصة.
- **الاعتمادات:** 185 حزمة مفحوصة — صفر ثغرات حقيقية بعد تصحيح lock.

## التوصيات المرتبة
1. **F-01** (ساعة–ساعتان): اشتقاق الإجماليات في الخادم + إصلاح أولوية `+` + اختبار fraud (total=0 يُرفض).
2. **F-02/F-03** (ساعة): بوابات constructor للتصنيفات + بوابة دور لـ mutations الاشتراكات.
3. **F-04/F-05/F-06** (نصف ساعة لكل): إصلاحات موضعية موثّقة أعلاه.
4. هيكلي (مذكور في baseline): Laravel Policies بدل الفحوص اليدوية المتفرقة؛ تغطية `vulnscan` في CI (gitleaks موجود ✓).

---
*generated-by: code-vulnscan · evidence-based (source+sink+path verified by direct file reads)*
