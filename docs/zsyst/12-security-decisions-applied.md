# 12 · تنفيذ القرارات الأمنية الأربعة

**التاريخ:** 31 أكتوبر 2026 · **المشروع:** `atvttthrqsybjjwrbiyl`

| # | القرار | الحالة | الدليل |
|---|--------|--------|--------|
| 1 | إغلاق التسجيل الذاتي | ✅ **مُنفَّذ** | `disable_signup = true` · طلب تسجيل فعلي أعاد `422 signup_disabled` |
| 2 | رفع الحد الأدنى لكلمة المرور إلى 10 | ⛔ **محجوب** | الموصّل لا يملك هذا الحقل أصلًا |
| 3 | تفعيل CAPTCHA | ⛔ **محجوب** | يحتاج مزوّدًا + مفتاحًا سريًا، ولا حقل له في الموصّل |
| 4 | `research-control` → `verify_jwt=true` | ✅ **لا ينطبق — مُتحقَّق** | الدالة غير موجودة (404). الوحيدة المنشورة `z-syst-api` و`verify_jwt=true` أصلًا |

---

## ١. إغلاق التسجيل — مُنفَّذ ومُختبَر

```
PATCH auth config → disable_signup = true
```

اختبار حقيقي بعد التغيير:

```
POST /auth/v1/signup  →  422  {"error_code":"signup_disabled",
                               "msg":"Signups not allowed for this instance"}
```

وتأكدت أن تسجيل الدخول لم يتأثر: `POST /auth/v1/token?grant_type=password` ما زال يعمل ويرد `400 invalid_credentials` لبيانات خاطئة — أي أن المسار نفسه سليم.

**أثر جانبي يجب معرفته:** إنشاء المستخدمين الآن لا يتم بالتسجيل الذاتي، بل من:
- لوحة Supabase → Authentication → Users → **Add user**، أو
- Admin API (`/auth/v1/admin/users`) بمفتاح `service_role`.

كلاهما يتجاوز `disable_signup` ويعمل طبيعيًا. وهذا هو المسار الصحيح أصلًا: الصيدلية تُجهَّز بعد الدفع، لا قبله.

**ملاحظة تقنية:** نداء `update_auth_config` يستبدل الإعدادات المرسلة ويعيد الباقي إلى قيم افتراضية. بعد النداء لاحظت أن `security_captcha_provider` و`sms_provider` و`rate_limit_email_sent` صارت `null`. لا أثر وظيفي (CAPTCHA وSMS معطّلان أصلًا)، لكن يجب الحذر: **أي تعديل لاحق على إعدادات Auth قد يعيد ضبط إعدادات أخرى**، فتُراجع القائمة كاملة بعد كل نداء.

## ٢. الحد الأدنى لكلمة المرور — محجوب

الموصّل يرفض الحقل صراحةً:

```
Unsupported property. Allowed properties: disable_signup, external_email_enabled,
external_phone_enabled, jwt_exp, mailer_autoconfirm, project_ref,
security_refresh_token_reuse_interval, security_refresh_token_rotation_enabled,
site_url, sms_autoconfirm, uri_allow_list.
```

القيمة الحالية: `password_min_length = 6` و`password_hibp_enabled = false`.

**التنفيذ اليدوي (٣٠ ثانية):**
Supabase Dashboard → **Authentication → Sign In / Providers → Email** → *Minimum password length* = `10`، وفعّل *Check against leaked password databases* (HaveIBeenPwned).

**أو** أرسل لي Personal Access Token (`sbp_...`) من Account → Access Tokens، وأنفّذها أنا عبر Management API مع بقية إعدادات Auth في نداء واحد.

> لا يمكن فرض هذا من قاعدة البيانات: GoTrue يخزّن البصمة (bcrypt) فقط، فالمحرّك لا يرى كلمة المرور الأصلية ولا يستطيع قياس طولها في trigger.

## ٣. CAPTCHA — محجوب

`security_captcha_enabled = false`، و`security_captcha_provider = null`، و`security_captcha_secret = null`.

**لماذا لم أفعّلها بمفتاح وهمي:** GoTrue يتحقق من كل طلب Auth عبر مزوّد CAPTCHA بمجرد التفعيل. مفتاح خاطئ أو فارغ يعني **تعطيل تسجيل الدخول بالكامل** — أي أن «التنفيذ» كان سيكسر النظام بدلًا من تأمينه.

**ما تحتاجه:**
1. حساب على Cloudflare Turnstile (مجاني) أو hCaptcha.
2. Site key + Secret key.
3. Dashboard → Authentication → **Attack Protection** → Enable CAPTCHA، اختر المزوّد، الصق الـSecret، ثم ضع الـSite key في واجهة تسجيل الدخول.

**رأيي:** بعد إغلاق التسجيل الذاتي، صار أثر CAPTCHA محدودًا — يحمي فقط نموذج تسجيل الدخول وإعادة تعيين كلمة المرور من التخمين الآلي. أولويته الآن أقل من تفعيل **تحديد معدّل المحاولات** (موجود افتراضيًا: 150 تحديث توكن/ساعة) ومن كلمة مرور قوية. أنصح بإتمامه قبل فتح التسجيل للعامة، لا الآن.

## ٤. دالة `research-control` — لا وجود لها

```
GET /v1/projects/atvttthrqsybjjwrbiyl/functions/research-control → 404 Function not found
```

`list_edge_functions` يعيد دالة واحدة فقط:

| الدالة | الحالة | verify_jwt |
|--------|--------|------------|
| `z-syst-api` | ACTIVE · v4 | **true** ✅ |

أي أن البند الرابع مُغلق تلقائيًا: **لا توجد دالة منشورة بلا توثيق**. كنت قد أشرت إلى `research-control` و`jobscope-probe` في تقرير سابق؛ الفحص المباشر الآن يقول إنهما غير موجودتين على هذا المشروع. أبلّغك بالفرق بدلًا من تنفيذ إجراء على شيء غير موجود.

---

## الحالة الأمنية بعد التنفيذ

| البند | قبل | بعد |
|-------|-----|-----|
| تسجيل ذاتي مفتوح | نعم | **لا** — `422` |
| تصعيد صلاحيات عبر `app_users` | ممكن | **مُغلق** (تقرير 11) |
| عزل بيانات الصيدليات | سليم | سليم (مُثبت بـJWT حقيقي) |
| دالة Edge بلا توثيق | — | لا توجد |
| طول كلمة المرور | 6 | **6 — يحتاج تدخلك** |
| CAPTCHA | معطّل | **معطّل — يحتاج مفتاح** |
