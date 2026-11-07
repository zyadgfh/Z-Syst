# Vulnerability Assessment Report — Z-Syst (full project scan)

**Target:** `zyadgfh/Z-Syst` @ `origin/main` = `aa4a363` (الشجرة المتتبعة كاملة + التبعيات + الكائنات التاريخية المتاحة)
**Date:** 2026-11-06 · **Assessor:** `/ecc` -> مهارة `02-vulnerability-scanner` v3.3.0
**Mode:** `offline` (ملفات مزوَّدة فقط — لا مسح نشط) · **Evidence contract:** الأدلة المُلاحَظة موسومة؛ الاستنتاج/غير-المتحقق موسوم صراحة.
**Evidence anchors:** composer.lock `0962787ad05281c4…` · package-lock.json `47385046ea18230a…`
**Limitations:** بلا شبكة خارجية (لا NVD/OSV حي لكل حزمة — تدقيق PHP اعتمد الإصدارات ووُسم unverified)، لا أدوات نشطة (nmap/trivy)، والاستقصاء التاريخي محدود بكائنات النسخة السطحية (shallow).

---

## Executive Summary
فُحصت شجرة main كاملة (تطبيق Laravel 12 + وحدات + أدوات Python/Node) بتدقيق سكوني: أنماط PHP خطرة، أسرار (شجرة + تاريخ)، إعدادات أمنية، تبعيات (composer.lock/package-lock/bandit/npm-audit). **النتيجة: 2 High · 3 Medium · 2 Low · 2 Info.** أخطر بندين: أسرار Stripe في **تاريخ** git (الشجرة الحالية نظيفة ✓) ورفع ملفات على قرص عام بلا قائمة أنواع (stored-XSS). لا وجود لأي eval/unserialize/md5-كلمات-مرور/SQL-خام-بمدخلات في كود التطبيق.

## Finding Summary
| ID | الخطورة | العنوان | أين |
|---|---|---|---|
| H-01 | High | أسرار في تاريخ git (3x `sk_test_` + `.env` تاريخي) | history (الشجرة ✓ نظيفة) |
| H-02 | High | رفع عام بلا allowlist -> Stored-XSS (SVG) + قراءة عامة | HasUploader + FILESYSTEM_DISK=public |
| M-01 | Medium | re2js ReDoS — GHSA-gg6h-894w-4m9c (High عند GHSA) | package.json (prod) |
| M-02 | Medium | `SESSION_SECURE_COOKIE` غير مفروض افتراضيًا | config/session.php |
| M-03 | Medium* | مسارات دفع عامة تحمل `business_id` (سطح IDOR/تعداد) — *غير متحقق العمق* | routes/web.php:8-10 |
| L-01 | Low | `exec()` بلا `escapeshellarg` (مصدره config) | BackupService:39,120 |
| L-02 | Low | B608 SQL نصي + B104 ربط 0.0.0.0 (أداة تطوير) | tools/supabase-web/server.py |
| I-01 | Info | مجلدات مهارات فحص مورَّدة داخل المستودع (hygiene) | .agent/.claude/.cline/.github/.windsurf |
| I-02 | Info | إصدارات composer رئيسية — تحقق CVE كامل غير ممكن offline | composer.lock |

## Findings Detail

### H-01: أسرار Stripe في تاريخ git
**CVSS:** غير قابل للتقييم (تعرُّض تشغيلي) | **الحالة الحالية:** الشجرة نظيفة ✓ (مُتحقق: `ls-tree origin/main database/` بلا ملفات sql، و`git grep sk_test_ origin/main -- database/` = صفر)
**Evidence (observed):** `git log --all -S "sk_test_KbfoWyw7…"` = **6 كوميتات**؛ كوميت الإزالة `a9b4c26f "security: remove leaked MySQL export (payment keys, user hashes, tokens)"` موجود على أطراف فروع جلسات قديمة (`arena/0a7677bc/367e0449/39415603…`) وليس في سلالة main المتاحة هنا؛ `.env` دخل التاريخ تاريخيًا (أُزيل بـ`a7eca878`).
**Risk:** كائنات git تبقى قابلة للوصول لمن يملك وصولًا للمستودع (فروع قديمة) -> إعادة استخدام مفاتيح الاختبار تفتح تخريب مدفوعات الاختبار وسلسلة للإنتاج.
**Remediation:** (1) تدوير المفاتيح في Stripe — إلزامي ويعوّض التاريخ (2) `git filter-repo` لإزالة `database/database_export.sql` و`.env` من كل التاريخ + force-push (3) إبطال/حذف فروع الجلسات القديمة (4) gitleaks في CI.
**Refs:** docs/zsyst/15-post-path-b-remediation.md.

### H-02: رفع عام بلا allowlist — Stored-XSS + قراءة عامة
**CVSS:** 6.1 `AV:N/AC:L/PR:L/UI:R/S:C/C:L/I:L/A:N` (مُعايَر High تشغيليًا في سياق صيدلية)
**Affected:** `app/Helpers/HasUploader.php` (لا فحص mime/امتداد — `isValid()` فقط) + `config/filesystems.php` default **`public`** -> الملفات تُخدم inline من /storage بنفس الأصل.
**Evidence (observed):** الكود أعلاه مقروء من main؛ المسار `uploads/YY/MM/…` مُتحقق حيًا سابقًا.
**Impact:** مستخدم مصرَّح يرفع SVG يحمل سكربتًا -> تنفيذ same-origin عند أي زائر للرابط (سرقة جلسة أدمن) + تعداد/قراءة الرفع العام كله.
**Remediation:** allowlist صارم (png/jpeg/webp/pdf — SVG ممنوع أو attachment) + تفعيل الرفع السحابي الخاص المبني مدموجًا (`UPLOADS_CLOUD_ENABLED=true` + `SupabaseStorage::signedUrl`) + `FILESYSTEM_DISK=local`.

### M-01: re2js ReDoS (GHSA-gg6h-894w-4m9c)
**CVSS:** High عند GHSA (Quadratic-time DoS) | **Affected:** re2js 2.2.0–2.8.6 (prod dep)
**Evidence (observed):** `npm audit --omit=dev` -> 1 high. **Remediation:** `npm audit fix` (>=2.8.7).

### M-02: كوكي الجلسة بلا `secure` مفروض
**Evidence:** `config/session.php`: `'secure' => env('SESSION_SECURE_COOKIE')` (null افتراضيًا). **Remediation:** `'secure' => env('SESSION_SECURE_COOKIE', ! app()->isLocal())` + HSTS.

### M-03: مسارات دفع عامة بمعرفات في المسار (unverified-depth)
**Evidence:** `routes/web.php:8-10` — `payments-gateways/{plan_id}/{business_id}` عامة؛ تفويض الـController لم يُراجَع هنا. **Next step:** مراجعة `PaymentController` (تعداد أعمال/بدء دفع عبر عمل) + التأكد من تحقق تواقيع webhooks (ssl-commerz/phonepe/paytm مستثناة من CSRF).

### L-01 / L-02 / I-01 / I-02
- **L-01** `BackupService:39,120`: exec بأمر mysqldump مركّب من config بلا escapeshellarg — استخدم Symfony Process/escapeshellarg.
- **L-02** `tools/supabase-web/server.py:124` B608 SQL نصي، `:286` B104 0.0.0.0 — أداة تطوير؛ معلمة + ربط محدد.
- **I-01** مجلدات `.agent/.claude/.cline/.github/.windsurf/skills/code-vulnscan` متتبعة — أخرجها (تضخيم + أمثلة أنماط أسرار داخل الشجرة).
- **I-02** composer: laravel/framework **v12.69.3**، sanctum 4.3.3، spatie 6.25.0، guzzle 7.15.5، dompdf wrapper v3.1.2 — لا advisory معروف مطابق عند الكتابة (**unverified** بلا NVD حي)؛ حدّث دوريًا + `composer audit`.

## نظيف (بأدلة)
لا eval/assert ولا unserialize(مدخلات) ولا md5/sha1-كلمات-مرور ولا SQL خام بمدخلات مباشرة (whereRaw بمعاملات مرتبطة) · CORS allowlist من env · http_only=true + same_site=lax · APP_DEBUG=false · bandit: صفر على app (فقط L-02 بأدوات dev) · تفويض /admin مُتحقق حيًا سابقًا (spatie gate) · RLS Supabase مُدقَّقة 7/7 سابقًا · bucket uploads خاص (5MB + mime allowlist).

## Remediation Roadmap (بالأولوية)
1. **الآن:** تدوير مفاتيح Stripe (H-01) — لا يعوّضه شيء.
2. **هذا الأسبوع:** `npm audit fix` (M-01) + فرض secure cookie (M-02) + escapeshellarg (L-01).
3. **هذا الأسبوع:** تفعيل الرفع السحابي الخاص + allowlist MIME (H-02) — الكود مدموج؛ إعداد فقط.
4. **خلال أسبوعين:** `git filter-repo` + إبطال الفروع القديمة + gitleaks في CI (H-01 جذريًا).
5. **مراجعة:** تدقيق تفويض PaymentController (M-03) وإخراج مجلدات المهارات (I-01).
