# 25 — تدقيق أمني شامل باستخدام حزمة مهارات Anthropic-Cybersecurity-Skills — 2026-11-12

**Target:** `zyadgfh/Z-Syst` @ `arena/42fd9f1b-z-syst` (من `main` = `aba23cd` + حزمة المهارات `5f1d6a9`)
**Assessor:** أمر `/cybersec` → مهارة `z-syst-cybersec` (موجّه) + 62 مهارة مثبّتة من `mukul975/Anthropic-Cybersecurity-Skills@54a7988` (Apache-2.0) + المهارة الحتمية `code-vulnscan` + موصلات `supabase` و`vercel`
**Mode:** قراءة فقط (لا تعديل كود التطبيق، لا قيم أسرار، لا أي تغيير حي) · أدلة بـ`file:line` أو نتيجة استعلام مباشر · فصل **مُلاحَظ / مُستنتَج / غير مُتحقَّق**
**العمق:** فحص حتمي لـ1033 ملفاً و83 مساراً → مراجعة يدوية لكل مرشّح بقاعدة «مَنبع ← مَصب ← مسار» → مراجعة الأدوات الـ62 على المعمار → تدقيق مباشر لوضع Supabase الحي (RLS/Auth/Storage/Edge) ووضع Vercel الحي.

---

## Executive Summary

**النتيجة: 0 Critical · 0 High · 3 Medium · 9 Low · 8 ملاحظات تشغيلية**

النظام **تحسن جذرياً** مقارنة بالتقارير 21/22/23: كل ثغرات عزل المستأجرين الحرجة أُغلقت (تحقّقتُ منها بنفسي في الكود الحي: `TenantScoped` على 15 نموذجاً، و`TenantAccessCheck` يفحص كل parameter مرتبط بالـRoute، و`private.post_sale` يرفض أي `p_business_id` مختلف بـ`raise exception 'tenant mismatch'`)، والتعيين الجماعي `$request->all()` اختفى من الكود، وفحص التوقيعات على webhooks الدفع يعمل بـ`hash_equals` مع تعطيل وضع المحاكاة في الإنتاج.

الفحص الحتمي (26 مرشّحاً + 8 مرشّحات أسرار + 5 مرشّحات CVE) **خرج كلها زائف أو غير قابل لل exploiting** بعد التحقق اليدوي. نقاط القوة الحية: RLS مفعّل على كل جداول المستأجرين، جداول البنية التحتية بلا صلاحيات لـ`anon/authenticated`، GoTrue مُغلق التسجيل مع تدوير refresh-token، وحزمة الرؤوس (CSP) مثبتة بالاختبارات.

الثغرات المتبقية集中在 تطبيق Flutter (حفظ كلمة مرور بصيغة plaintext)، وسياسة كلمات المرور الضعيفة حياً (6 أحرف)، وتعطيل CAPTCHA حياً رغم توفر الأسلاك، و9 فجوات منخفضة في الـhardening، وملاحظات تشغيلية (فشل نشر Vercel، ونقص نجمة `backups`).

---

## النطاق والمетодية

| الخطوة | الأداة | النتيجة |
|---|---|---|
| 1. تثبيت الحزمة | نسخ 62 مهارة verbatim إلى `.agents/skills/` + موجّه `z-syst-cybersec` | 342 ملفاً، فُحصت 62/62 صحة frontmatter |
| 2. فحص حتمي | `code-vulnscan/scripts/scan.py` (run_id `44b06a0f`) | 1033 ملف، 83 مسار، 26 مرشّحاً |
| 3. أسرار + تبعيات | `secrets.py` + `dependency.py` | 8 مرشّحات أسرار، 5 مرشّحات CVE |
| 4. مراجعة يدوية | مهارات OWASP/API/JWT/Mobile/IAM + قراءة الكود فعلياً | 34 فحصاً في 7 فئات |
| 5. وضع حي — Supabase | `get_auth_config` + 10 استعلامات SELECT-only |详见 أدناه |
| 6. وضع حي — Vercel | `list_projects/env_vars/domains/deployments` |详见 أدناه |

**مخرجات الفحص الحتمي (كلها تحققتُ منها يدوياً):**

| المرشّحات | العدد | الحكم بعد التحقق |
|---|---|---|
| `eval()` في `tools/valkey-compat-test.py` و`tools_local/phpw-runner/runner.mjs` | 10 | زائف — سكربتات dev تُشغّل نصوص Lua/PHP ثابتة بلا مدخلات مستخدم |
| `urlopen()` في `tools_local/*.py` | 3 | زائف — عناوين ثابتة في أدوات dev |
| `rand()` في `HasUploader` و`InsuranceClaimFactory` | 2 | منخفض — اسم ملف `timestamp-rand(1..1000)` لمحتوى صور عامة أصلاً؛ والآخر factory اختبارات |
| `md5()` في `QueryOptimizationService` و`ProductAnalyticsService` | 2 | زائف — تجزئة لـcache key/تشخيص لا لأمان |
| `{!! echo !!}` في `ReceiptController:106` و`src/main.js:94` | 2 | زائف — PDF مولَّد داخلياً؛ و`innerHTML` من كائن i18n ثابت |
| `hardcoded_secret` في `supabase/config.toml` | 3 | زائف — قيَم `env(...)` الصحيحة (نمط مُوصى به) |
| أسرار `Totp.php` و`valkey-compat-test.py` | 5 | زائف — أبجدية base32 القياسية وتوثيق usage |
| CVE في `guzzle/lodash/axios` | 5 | **غير قابل للاستغلال** — lockfiles تثبّت: guzzle **7.15.5** ≥7.5.0، lodash **4.18.1** ≥4.17.21، axios **1.21.0** ≥1.6.0 |
| `automation.sh` طباعة env | 2 | زائف — سطر `cp .env.example .env` فقط |

**grep إيجابي على أنماط الأسرار الحقيقية** (AKIA/sk-/ghp_/eyJ… في الكود): صفر نتائج · لا `.env` متتبَّع (فقط `.example`/`.testing` ونظيف) · `service_role` يُقرأ من `config()` فقط.

---

## Findings Detail

### 🟡 M-01 — تطبيق Flutter يحفظ كلمة المرور بصيغة plaintext عند «تذكرني»
**CVSS (تقديري):** 6.5 `AV:L/AC:L/PR:N/UI:R/S:U/C:H/I:N/A:N` (CWE-256)
**Evidence (observed):** `pharmacy-store-app-codecanyon-main/lib/Screens/Authentication/Sign In/sign_in_screen.dart:60-73` — `_saveUserCredentials()` ينفّذ `prefs.setString('password', passwordController.text)` ويعيد قراءتها لاحقاً في `_loadUserCredentials()`.
**Impact:** على جهاز مُجذّر أو عبر استخراج نسخة احتياطية غير مشفّرة، تُستعاد كلمة مرور حساب الصيدلية بصيغة plaintext (بيانات صحية/مالية خلف الحساب).
**Remediation:** احفظ البريد فقط عند «تذكرني»؛ احذف حفظ كلمة المرور نهائياً؛ الاعتماد على `FlutterSecureStorage` للتوكن فقط.
**Label:** observed (كود مُلتزم به في الشجرة).

### 🟡 M-02 — سياسة كلمات مرور ضعيفة حياً في GoTrue (6 أحرف، بلا تعقيد)
**CVSS (تقديري):** 5.3 `AV:N/AC:L/PR:N/UI:N/S:U/C:L/I:N/A:N` (CWE-521)
**Evidence (observed):** `get_auth_config` للمشروع الحي `atvttthrqsybjjwrbiyl`: `password_min_length: 6`، `password_required_characters: null`، `password_hibp_enabled: false`.
**Impact:** كلمات مرور قصيرة/متوقعة لحسابات صيدليات (فرض حروف غير مطلوب) + عدم كشف تسريب HIBP.
**Remediation:** ارفع `password_min_length` إلى ≥8 وفعّل `password_required_characters` (رقم + حالة)، وفعّل HIBP اختيارياً عبر Management API/اللوحة.
**Label:** observed (قراءة حية).

### 🟡 M-03 — حماية CAPTCHA معطّلة حياً رغم وجود الأسلاك الكاملة لها
**CVSS (تقديري):** 5.3 (CWE-307 — Brute Force مُخفَّف جزئياً)
**Evidence (observed):** `get_auth_config`: `security_captcha_enabled: false` و`security_captcha_provider: "hcaptcha"`. الكود يدعمها: `config/supabase.php:44-46`، `LoginRequest.php:38-41` (تفرض `captcha_token` عند التفعيل)، `SupabaseAuthClient.php:40-44`، ووثائق `docs/zsyst/13-hcaptcha-wiring.md`.
**Mitigations (observed):** ثrottling layered فعّال: `routes/api.php:8` (`throttle:10,1` على sign-in)، `LoginRequest.php:108` (RateLimiter 5 محاولات)، وحدود GoTrue (`rate_limit_anonymous_users: 30`, `rate_limit_verify: 30`, `rate_limit_email_sent: 2`).
**Remediation:** فعّل CAPTCHA في لوحة Supabase (يتطابق مع `supabase.captcha.enabled` في الكود) — أو وثّق قرار عدم التفعيل كـaccepted-risk مع مبرّرات.
**Label:** observed (قراءة حية) + observed (mitigations).

### 🟢 L-01 — تكرار التكنون في SharedPreferences بلا تشفير (أثناء هجرة التخزين)
**Evidence (observed):** `lib/Repository/constant_functions.dart:14-19` — `saveUserData` يكتب التكنون في `FlutterSecureStorage` **و**`prefs.setString('token', raw)` (fallback للهجرة).
**Remediation:** بعد تأكيد الهجرة، احذف النسخة plaintext وألغي قراءة `getString('token')`. **Label:** observed.

### 🟢 L-02 — صلاحيات Android مُفرطة: `MANAGE_EXTERNAL_STORAGE` + `WRITE_EXTERNAL_STORAGE`
**Evidence (observed):** `pharmacy-store-app-codecanyon-main/android/app/src/main/AndroidManifest.xml:2-4`.
**Impact:** سطح هجوم أوسع + رفض سياسات Google Play لـ`MANAGE_EXTERNAL_STORAGE` ما لم يكن ضرورياً جداً.
**Remediation:** احذفها إن لم تكن هناك ميزة تستخدمها فعلياً (الوصول للصور يتم عبر `image_picker` بصلاحيات محدودة). **Label:** observed.

### 🟢 L-03 — مرفقات الفواتير/Mدفوعات عبر روابط capability غير مُصادَقة (`asset('storage/...')`)
**Evidence (observed):** `app/Http/Resources/SupplierInvoiceResource.php:54` و`SupplierInvoicePaymentResource.php:39` و`SupplierPaymentResource.php:46` تُعيد `file_url = asset('storage/'.$file_path)`؛ الملفات تُخزَّن عبر `$file->store('supplier-invoices','public')` (اسم hash عشوائي — `SupplierInvoiceService.php:426,439`).
**Impact:** الاسم غير قابل للتخمين (hash) ⇒ خطر منخفض، لكن الرابط يعمل **بلا مصادقة وبلا انتهاء صلاحية** — تسريب الرابط = وصول دائم لفاتورة مالية؛ ولا يوجد binding للملكية.
**Remediation:** قدّم الملفات عبر Route محمي بـ`auth` + فحص `business_id` (نمط `Api\BarcodeController::download`) أو استخدم `SupabaseStorage::signedUrl()` الموجود أصلاً (`app/Services/SupabaseStorage.php:97`).
**Label:** observed (الكود والنمط) · inferred (قابلية الاستغلال تتطلب تسريب الرابط).

### 🟢 L-04 — كود ميت غير آمن: `Admin\BarcodeController::download`
**Evidence (observed):** `app/Http/Controllers/Admin/BarcodeController.php:447-459` — `storage_path('app/public/'.$filename)` **بلا** realpath/prefix/tenant/extension، بينما النسخة المُخدَمة (`Api/BarcodeController.php:50-70`) مُحكَّمة (realpath + tenant prefix + `.pdf` فقط). **غير مُعرَّف في `routes/`** (dead code).
**Impact:** لا هو قابل للاستغلال الآن (لا route)، لكنه قنبلة وقتية لو رُبط لاحقاً (path traversal + عبور عزل المستأجرين).
**Remediation:** احذفه، أو انسخ منطق النسخة API حرفياً قبل أي استخدام. **Label:** observed.

### 🟢 L-05 — أسرار عالية القيمة غير مستخدمة في بيئة Vercel (أثر انفجار أكبر)
**Evidence (observed):** `list_env_vars` لمشروع `z-syst` (`prj_mgAB0jwxskozdMpzacRUaQLBuvx5`) يحوي `SUPABASE_SERVICE_ROLE_KEY` و`SUPABASE_JWT_SECRET` و`SUPABASE_SECRET_KEY` بجانب `NEXT_PUBLIC_SUPABASE_URL` — المشروع Vite ثابت (إطار landing) بلا runtime يستهلكها.
**Impact:** أي تسريب في بيئة Vercel يعطي service_role (تجاوز RLS الكامل) دون داعٍ.
**Remediation:** احذف المتغيرات غير المستخدمة (بعد التأكد عبر بناء محلي أن لاشيء يقرأها) — الأنواع كلها `sensitive/encrypted` و`securityIssues: []` حالياً.
**Label:** observed (الأسماء والأنواع) · **unverified**: هل أي كود يقرأها فعلاً (لم أستطع تتبّع بناء Vercel الفاشل).

### 🟢 L-06 — موقع Vercel (اللاندنج) بلا أمان رؤوس في `vercel.json`
**Evidence (observed):** `vercel.json` يضبط `Cache-Control` فقط — لا CSP/HSTS/X-Content-Type-Options/X-Frame-Options/Referrer-Policy.
**Remediation:** أضف كتلة headers لكل المسارات (بما فيها `cleanUrls`). **Label:** observed.

### 🟢 L-07 — `secret-scan.yml` بلا `permissions:` صريحة
**Evidence (observed):** `ci.yml:9` و`phpunit.yml:9` و`runtime.yml:10` تملك كتلة `permissions:`؛ `secret-scan.yml` لا يملكها (يستخدم صلاحيات التوكن الافتراضية الأوسع).
**Remediation:** أضف `permissions: contents: read` أعلى الملف. **Label:** observed.

### 🟢 L-08 — أرضيات تبعيات أقدم من الإصدارات الآمنة (الـlockfiles آمنة اليوم)
**Evidence (observed):** `composer.json` → `guzzlehttp/guzzle` (الconstraint يسمح ≥7.2 والمثبَّت 7.15.5)؛ `package.json` → `"lodash": "^4.17.19"` (المثبَّت 4.18.1) و`"axios": "^1.1.2"` (المثبَّت 1.21.0).
**Impact:** أي `composer update`/`npm update` غير مُقفل يمكنه التراجع لإصدار مصاب.
**Remediation:** ارفع الأرضيات: `^7.5` / `^4.17.21` / `^1.6.0`. **Label:** observed.

### 🟢 L-09 — `http://localhost:3000/**` داخل قائمة تحويلات GoTrue الحية
**Evidence (observed):** `get_auth_config` → `uri_allow_list: "https://app.z-syst.com/**,https://*.z-syst.com/**,http://localhost:3000/**"`.
**Impact:** سماح redirect لـlocalhost في مشروع إنتاج (منخفض؛ يحدّ من سوء الاستخدام لكنه زائد).
**Remediation:** أزل localhost من بيئة الإنتاج وأبقِه في بيئة التطوير. **Label:** observed.

---

## ملاحظات تشغيلية (Observations)

| ID | الملاحظة | الدليل | التصنيف |
|---|---|---|---|
| O-01 | **نشر Vercel معطَّل:** آخر إنتاجين `ERROR` — الأحدث `git_info_fail` (GitHub fetch 404: تغيّر ملكية/نقل المستودع `zyadgam`→`zyadgfh` كسر تثبيت GitHub App)، والأول `STATIC_BUILD_NO_OUT_DIR`. `z-syst.vercel.app` مُتحقَّق منه لكن لا نشر ناجح. إضافة: `githubCommitVerification: unverified` | `list_deployments` (observed) | تشغيلي — أعد تثبيت GitHub App وفعّل توقيع COMMITS |
| O-02 | جدول `notifications` سياسته `notifications_tenant_select` بـ`qual=true` (**قراءة عابرة للمستأجرين**) لكنه فارغ الآن (0 صف) — خطر كامن عند أول استخدام للـDB notifications | `pg_policies` + `count(*)` (observed) | صحّح السياسة قبل أول استخدام: أضف قيد `notifiable_id = auth.uid()` أو `business_id` |
| O-03 | جدولا `settings` و`options` بسياسة `SELECT qual=true` لكامل الجدول — **فارغان الآن** (0 صفوف). إن حُفظت بهما أسرار لاحقاً ستُقرأ من كل مستخدم مُصادَق | استعلام أسماء المفاتيح = صفر صفوف (observed) | لا تخزّن أسرار فيهما؛ احذف السياسة أو خصّصها |
| O-04 | نجمة `backups` **غير موجودة** في `storage.buckets` (الوحيدة `uploads`) — نقل النسخ الاحتياطية السحابية يفشل ويُسجَّل كـ`backup.cloud_failed` فقط. و`BackupController` نفسه superadmin-gated ✅ | `storage.buckets` (observed) + `BackupController.php:34-52` | أنشئ نجمة `backups` خاصة (public=false) أو عطّل `cloud_enabled` صراحة |
| O-05 | سياسات storage تفترض المجلد الأول = `business_id` لكن `HasUploader` يكتب `uploads/YY/MM/...` ⇒ قراءة PostgREST تفشل **بشكل آمن (fail-closed)**؛ مسار service_role لا يتأثر | `pg_policies storage` + `HasUploader.php:123` (observed) | وحّد البادئات عند تبنّي signed URLs |
| O-06 | CSP يحوي `'unsafe-inline' 'unsafe-eval'` — مطلوب لstack Blade/jQuery/Alpine ومثبَّت حرفياً في `tests/Feature/SecurityHeadersTest.php` | observed (مقبول ومُوثَّق سابقاً) | accepted risk — لا تغيير مقترح |
| O-07 | `public.current_business_id` بـ`search_path=public` فقط (بدون `pg_temp` صريح) — دالة `private` الصحيحة تضمّ `pg_temp` ✅. المخاطرة عملية شبه معدومة عبر PostgREST | `pg_proc.proconfig` (observed) | تحسين بسيط: أعد تعريفها `search_path=public, pg_temp` |
| O-08 | لا PHP/composer في بيئة الفحص ⇒ **لم يُعاد تشغيل PHPUnit محلياً** — الشجرة بلا أي تعديل كود تطبيق (المهارات فقط)؛ السuite أخضر في CI على دمج الأساس (398/398 في PR #26) | observed | CI (`phpunit.yml`) هو المرجع |

---

## عناصر مؤكَّدة سليمة (Verified Controls)

| ID | العنصر | الدليل |
|---|---|---|
| V-01 | **RLS:** 140 جدول مستأجرين `rowsecurity=true`؛ جداول البنية (`cache/jobs/sessions/personal_access_tokens/failed_jobs/job_batches`) بلا RLS **وبلا أي صلاحيات** لـ`anon/authenticated` (استعلام `role_table_grants` أرجع فقط `app_users/businesses/sales` المُقيَّدة بسياسات)؛ `migrations` بـRLS بلا سياسات = deny-by-default | 3 استعلامات حية (observed) |
| V-02 | **دالة سياق فاشلة-بشكل-آمن:** `private.current_business_id` = `select business_id from app_users where id=auth.uid()` — بدون JWT تُرجع NULL ⇒ `business_id = NULL` ⇒ السياسة false | `pg_proc` (observed) |
| V-03 | **حارس المستأجر في دوال البيع:** `private.post_sale` أول سطر: `if p_business_id is distinct from private.current_business_id() then raise exception 'tenant mismatch'` + تحقق منتج/مخزن داخل نفس المستأجر + قفل `FOR UPDATE` ضد سباق المخزون + مفتاح idempotency | `pg_proc` (observed) |
| V-04 | **27 دالة SECURITY DEFINER** كلها بـ`search_path` مثبَّت (معظمها `public, pg_temp`)، ومعظمها في مخطط `private` | `pg_proc` (observed) |
| V-05 | **GoTrue:** `disable_signup=true` · `mailer_autoconfirm=false` · `mailer_allow_unverified_email_sign_ins=false` · `jwt_exp=3600` · `refresh_token_rotation_enabled=true` + reuse interval 10 · MFA TOTP متاح · rate limits مفعّلة · لا OAuth خارجي مفتوح · hooks كلها معطّلة | `get_auth_config` (observed) |
| V-06 | **Storage:** نجمة `uploads` **خاصة** (`public=false`)، حد 5MB، allowlist.mime مطابق للتطبيق، و4 سياسات tenant على `storage.objects` بمجلد `business_id` | `storage.buckets` + `pg_policies` (observed) |
| V-07 | **Edge:** دالة `z-syst-api` ACTIVE بـ`verify_jwt=true` · كل خدمات المشروع `ACTIVE_HEALTHY` (GoTrue v2.197.0، rest exposes `public,graphql_public` فقط) | `list_edge_functions` + `get_project_health` (observed) |
| V-08 | **Vercel env:** كل المتغيرات `sensitive/encrypted`، كثيرة منها `visibility: secret`، `securityIssues: []` في كل صف، ولم يُفكّ أي تشفير أثناء التدقيق | `list_env_vars` (observed) |
| V-09 | **طبقة الويب:** throttle ‏10/دقيقة على sign-in و5/5 على استعادة كلمة المرور بروابط `signed` (`routes/api.php:8-19`، `routes/auth.php:31-35`) · RateLimiter ‏5 محاولات للويب (`LoginRequest.php:108`) · CORS بأصول صريحة و`supports_credentials=false` (`config/cors.php`) · allowlist امتداد+MIME للرفع (`HasUploader.php:18-37`) · لا `create($request->all())` في أي مكان · توقيع Paymob/Fawry بـ`hash_equals` مع تعطيل simulation في production (`PaymentGatewayService.php:56-120` + `mode()`) · مسار تحميل الباركود API مُحكَّم realpath+tenant+`.pdf` · صفر نتائج لأنماط أسرار حقيقية · workflows بكتل `permissions:` وبلا `pull_request_target` · gitleaks في CI | مراجعة يدوية (observed) |
| V-10 | **عزل المستأجرين — فحص انتكاسة تقرير 23:** `TenantScoped` مُطبَّق على Unit/BoxSize/Category/Manufacturer/MedicineType/Tax/Supplier/DueCollect/Income/Product/Stock/Sale/Purchase/Party/Expense ✅ · `User` يُحمى عبر `TenantAccessCheck` الذي يفحص **كل** route-parameter يملك `business_id` (`TenantAccessCheck.php:30-41`) والـ`users` apiResource داخل مجموعة `auth:sanctum + tenant.check` (`routes/api.php:21,152`) · `Purchase::create($request->only([...]))` ✅ · `{!! HtmlSanitizer::clean() !!}` في صفحات اللاندنج ✅ · استثناءات CSRF الميتة أُزيلت (`VerifyCsrfToken.php:14-20`) · Receipt/DoseLabel بفحص ملكية صريح (403/404) | مراجعة يدوية (observed) |
| V-11 | **حزمة المهارات:** 62/62 `SKILL.md` سليم الواجهة ومطابق للأسماء؛ الأصل من مستودع مرخّص Apache-2.0 وCI صالح؛ لا كود تطبيق مُعدَّل في عملية التثبيت (`5f1d6a9` = ملفات `.agents` + `.claude` فقط) | فحص برمجي (observed) |

---

## مقارنة سريعة مع تقرير 23 (Regression Check)

| تقرير 23 | الحالة اليوم |
|---|---|
| C-01 حذف مستخدمين عبر tenants (12 موديلاً) | ✅ مغلق — `TenantScoped` + `TenantAccessCheck` على كل الـbindings (V-10) |
| H-01 ثمن/ربح من العميل في POS | خارج عمق هذه الجولة (منطق أعمال) — لم يُفَجَّأ بشيء جديد؛ يبقى ضمن خطة المالك |
| M-01 سباق FEFO | ✅ مُعالَج جزئياً على مستوى DB: `private.post_sale` يستخدم `FOR UPDATE` (V-03)؛ مسار PHP القديم يحتاج مراجعة مستقلة |
| M-02/M-03 تعيين جماعي | ✅ مغلق — صفر نتائج `create/update($request->all())` (V-10) |
| M-04 HTML خام في اللاندنج | ✅ مغلق — `HtmlSanitizer::clean()` (V-10) |
| L-01 استثناءات CSRF ميتة | ✅ مغلق — `$except` فارغ ومُعلَّق (V-10) |

---

## خطة المعالجة المقترحة (بأولوية)

1. **فوري (يوم):** M-01 (أوقف حفظ كلمة المرور في Flutter) · O-01 (أعد تثبيت GitHub App لإصلاح نشر Vercel).
2. **أسبوع:** M-02 (سياسة كلمة مرور ≥8+تعقيد) · M-03 (فعّل CAPTCHA أو وثّق القبول) · L-01 (أنهِ نسخة التكنون plaintext) · O-04 (نجمة `backups`).
3. **ربع:** L-03 (روابط مرفقات محمية) · L-05 (نظّف env Vercel) · L-06 (رؤوس أمان Vercel) · L-08 (ارفع أرضيات التبعيات) · L-02/L-04/L-07/L-09 (hardening سريع) · O-02/O-03 (سياسات `qual=true`).
4. **مستمر:** أعد تشغيل `/cybersec` بعد كل PR أمني؛ أضف فحص RLS (O-01 من خارطة الطريق: Postgres RLS CI job) لمشروع CI.

---

## القيود (Limitations)

- **قراءة فقط:** لم يُختبر أي endpoint حي عبر HTTP (لا pentest ديناميكي على `app.z-syst.com`)؛ لا تعديلات على الإعدادات الحية.
- **Flutter:** مراجعة static فقط (لا MobSF/frida في البيئة)؛ `APIConfig.domain` ما زال placeholder (`https://YOUR-Z-SYST-DOMAIN/`) — إعداد ما قبل الإصدار غير مُتحقَّق منه.
- **قيم الأسرار:** لا قيم مكشوفة في هذا التقرير — Vercel `decrypted:false` وSupabase أعادت تمثيلات مُخفّاة فقط، ولم تُطلب أي قيمة.
- **PHPUnit:** لا PHP في بيئة الفحص (O-08) — الشجرة بلا تعديل كود، والـCI هو المرجع.
- **نطاق الحزمة:** 62 من 818 مهارة (التقائية مع `code-vulnscan` و`.bug-hunter` بدل إغراقها) — التغطية الكاملة في `PROVENANCE.md`.

*انتهى التقرير — بواسطة أمر `/cybersec` · مهارة `z-syst-cybersec` · 2026-11-12.*
