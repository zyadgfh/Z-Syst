# 13 · hCaptcha — التحقق من المفتاح وتوصيله بالكود

**التاريخ:** 31 أكتوبر 2026 · **المفتاح المستلم:** `ES_ad6674…c62afe` (hCaptcha **Secret Key**)

---

## ١. هل المفتاح صحيح؟ نعم — مُتحقَّق منه

فحصته على نقطة التحقق الرسمية `POST https://api.hcaptcha.com/siteverify` بثلاث حالات:

| الاختبار | رد hCaptcha | الدلالة |
|----------|-------------|---------|
| توكن وهمي `10000000-aaaa-…` | `not-using-dummy-secret` | المفتاح معروف لكنه ليس مفتاح الاختبار |
| توكن عشوائي `zzzz-not-a-real-token` | **`invalid-input-response`** | ✅ **المفتاح صحيح** — رُفض التوكن لا المفتاح |
| — | لم يظهر `invalid-input-secret` إطلاقًا | ✅ لو كان المفتاح خاطئًا لظهر هذا الخطأ |

الحالة الثانية هي الدليل الحاسم: hCaptcha تحققت من المفتاح أولًا وقبلته، ثم رفضت التوكن. أي أن **السر سليم وفعّال**.

## ٢. لماذا لم أفعّل CAPTCHA في Supabase الآن

سببان، كلاهما يمنع التنفيذ الآمن:

**أ) الموصّل لا يملك الحقول.** قائمة الحقول المسموحة في `update_auth_config` هي: `disable_signup`, `external_email_enabled`, `external_phone_enabled`, `jwt_exp`, `mailer_autoconfirm`, `security_refresh_token_reuse_interval`, `security_refresh_token_rotation_enabled`, `site_url`, `sms_autoconfirm`, `uri_allow_list`. لا وجود لـ`security_captcha_*`.

**ب) الأخطر — الترتيب.** لحظة تفعيل CAPTCHA في Supabase، يبدأ GoTrue برفض **كل** عملية دخول لا تحمل `captcha_token` صالحًا. ولواجهة Laravel الحالية لم تكن ترسل هذا التوكن إطلاقًا. أي أن التفعيل قبل تجهيز الواجهة = **قفل كامل لكل المستخدمين خارج النظام**.

لذلك نفّذت الطرف الآخر من السلك أولًا.

## ٣. ما نُفِّذ في الكود (5 ملفات)

| الملف | التغيير |
|-------|---------|
| `config/supabase.php` | بلوك `captcha` جديد: `enabled` / `provider` / `site_key` / `token_field`، مع تعليق يشرح قاعدة الترتيب |
| `app/Support/Supabase/SupabaseAuthClient.php` | `signInWithPassword(email, password, ?captchaToken)` يضيف `captcha_token` إلى جسم الطلب عند وجوده فقط |
| `app/Auth/SupabaseUserProvider.php` | يقرأ `captcha_token` من بيانات الاعتماد ويمرّره؛ وأي رفض يحتوي كلمة `captcha` يُصنَّف `captcha_failed` بدل رسالة خام |
| `app/Http/Requests/Auth/LoginRequest.php` | قاعدة تحقق شرطية (`required` عند التفعيل)، تمرير التوكن في `Auth::attempt`، ورسالة خطأ مستقلة للـCAPTCHA حتى لا يُقال للمستخدم «كلمة المرور خاطئة» وهي ليست كذلك |
| `resources/views/auth/login.blade.php` | واجهة hCaptcha (سكربت + widget + حقل مخفي) تظهر فقط عند التفعيل، مع `reset()` بعد كل محاولة فاشلة لأن التوكن يُستهلك مرة واحدة |
| `lang/en.json` · `lang/ar.json` | `auth.captcha_failed` بالعربية والإنجليزية |
| `.env.example` · `.env.production.example` | `SUPABASE_CAPTCHA_ENABLED=false` · `SUPABASE_CAPTCHA_PROVIDER=hcaptcha` · `HCAPTCHA_SITE_KEY=<<<NEEDED>>>` |

**الحالة الافتراضية:** كل شيء **مُعطَّل** (`false`). الكود الجديد لا يغيّر أي سلوك حتى تشعل المفتاح.

## ٤. لماذا لم أخزّن الـSecret داخل Laravel

التحقق من التوكن يجري داخل Supabase (GoTrue) لا داخل PHP. المتصفح يحتاج **Site Key** العام فقط. وضع الـSecret في `.env` الخاص بـLaravel يعني نسخة إضافية من سرّ لا يحتاجه التطبيق — تعريض بلا مقابل.

- محفوظ في: `/home/user/.secrets/hcaptcha.env` (صلاحيات 600) — للمرجع فقط.
- المكان الصحيح الوحيد له: Supabase → Authentication → Attack Protection.

## ٥. ما ينقص لإتمام التفعيل

### أ) Site Key (عام) — أحتاجه منك
من لوحة hCaptcha → Settings → **Sitekey**. سلسلة تشبه UUID. بدونها لا يمكن رسم الـwidget في صفحة الدخول، مهما فُعِّل في Supabase.

### ب) التفعيل في Supabase — يحتاج لوحة أو رمز وصول
1. Authentication → **Attack Protection** → فعّل *Enable CAPTCHA protection*.
2. المزوّد: **hCaptcha**.
3. الصق الـ**Secret**: `ES_ad6674…c62afe`.

أو أرسل `sbp_...` (Personal Access Token) وأنفّذها عبر Management API.

## ٦. تسلسل الإطلاق الصحيح (مهم)

```
1. ضع HCAPTCHA_SITE_KEY في بيئة الإنتاج
2. SUPABASE_CAPTCHA_ENABLED=true  →  انشر Laravel  →  افتح صفحة الدخول وتأكد أن الـwidget يظهر
3. جرّب دخولًا واحدًا ناجحًا  ←  هنا فقط نعرف أن السلك كامل
4. بعدها فقط: فعّل CAPTCHA في Supabase
5. جرّب دخولًا آخر للتأكيد
```

**لا تعكس 3 و4.** لو عكستها ستدخل في قفل دخول كامل، والحل حينها تعطيل CAPTCHA من اللوحة فورًا.

## ٧. ملاحظة على الأولوية

بعد إغلاق التسجيل الذاتي (`disable_signup=true`)، صار CAPTCHA يحمي فقط: تسجيل الدخول وإعادة تعيين كلمة المرور. الحماية الأهم الآن هي **طول كلمة المرور** (ما زالت 6)، لأنها الضعف الفعلي في مواجهة التخمين — وCAPTCHA لا تحمي من كلمة مرور ضعيفة. أنصح بإتمام الاثنين معًا قبل أول عميل حقيقي.
