# كل ما أحتاجه منك — متغيّرات وقرارات

مبنيّ على قراءة `.env.example` و**‎141** متغيّرًا تقرأه `config/` فعليًا.
منها **69** غير مذكورة في `.env.example` — لكن معظمها له قيمة افتراضية في الكود، **فلا أحتاجها منك**. ما يلي فقط ما لا أستطيع توليده بنفسي.

---

## ⚠️ قاعدة قبل أي شيء

**لا ترسل لي أسرارًا في المحادثة.** السرّ الذي أرسلته سابقًا (`sec_c23f…`) اعتُبر مكشوفًا.
الطريقة الصحيحة:

1. أنا أجهّز `.env.production` **بأسماء المتغيّرات وقيم نائبة**.
2. أنت تفتحه على السيرفر وتضع القيم الحقيقية بنفسك.
3. تخبرني «ضبطتها» — وأنا أتحقّق بالسلوك لا بالقيمة.

كل ما هو **سرّي** معلَّم بـ🔒. غير المعلَّم يمكن إرساله هنا بأمان.

---

## القسم أ — يمنع التشغيل الآن (٥ عناصر)

| المتغيّر | الحالة | من أين |
|---|---|---|
| `APP_KEY` | 🔒 **أولّده أنا** بأمر `artisan key:generate` — لا أحتاجه منك | — |
| `APP_URL` | أحتاجه | الدومين النهائي، مثل `https://app.example.com` |
| `APP_ENV` / `APP_DEBUG` | أضبطهما: `production` / `false` | تأكيد فقط |
| `DB_*` | 🔒 أحتاج القيم | انظر القسم د (قرار MySQL أم Supabase) |
| `SESSION_DRIVER` / `CACHE_DRIVER` / `QUEUE_CONNECTION` | قرار | `file`/`database` (بلا Redis) أو `redis` |

> ملاحظة مهمة: `.env.example` اليوم يفترض **Supabase PostgreSQL** (`DB_CONNECTION=pgsql`)، بينما خطتي كُتبت لـMySQL. أحتاج حسمًا — انظر القسم د.

---

## القسم ب — قبل أول صيدلية تدفع

### ١. البريد (بدونه لا تسجيل ولا استعادة كلمة سر)
| المتغيّر | ملاحظات |
|---|---|
| `MAIL_MAILER` | `smtp` |
| `MAIL_HOST` / `MAIL_PORT` / `MAIL_ENCRYPTION` | حسب المزوّد |
| `MAIL_USERNAME` / `MAIL_PASSWORD` | 🔒 |
| `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME` | يحتاج دومينًا مضبوطًا (SPF/DKIM) وإلا البريد يذهب للمهملات |

بديل: `POSTMARK_TOKEN` 🔒 أو `MAILGUN_SECRET` 🔒 + `MAILGUN_DOMAIN` — إن استخدمت مزوّدًا مُدارًا.

### ٢. الدفع — Paymob
هذا التطبيق يخزّن بيانات البوابات في **جدول `gateways`** (عمود `data` بصيغة JSON) تُدار من لوحة التحكم، **لا في `.env`**.

**أحتاج منك (تُدخلها أنت في اللوحة، أو أعطها لي كأسماء فقط):**

| العنصر | 🔒 |
|---|---|
| API Key | 🔒 |
| Public Key | لا |
| Integration ID — بطاقات | 🔒 |
| Integration ID — محافظ (فودافون/إتصالات…) | 🔒 |
| iFrame ID | لا |
| HMAC Secret (للتحقّق من الـwebhook) | 🔒 |
| Merchant/Account ID | لا |

إن فضّلت أن تكون في `.env` بدل قاعدة البيانات، سأضيف: `PAYMOB_API_KEY`, `PAYMOB_HMAC_SECRET`, `PAYMOB_INTEGRATION_CARD`, `PAYMOB_INTEGRATION_WALLET`, `PAYMOB_IFRAME_ID`.

### ٣. التخزين والنسخ الاحتياطي
| المتغيّر | الحالة |
|---|---|
| `FILESYSTEM_DISK` | `local` مؤقتًا، أو `s3` |
| `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY` / `AWS_BUCKET` / `AWS_DEFAULT_REGION` | 🔒 (أو DigitalOcean Spaces / Backblaze بـ`AWS_ENDPOINT`) |
| `BACKUP_DISK` / `BACKUP_PATH` / `BACKUP_SCHEDULE` / `BACKUP_RETENTION_DAYS` | أضبطها: نسخ كل ساعة، احتفاظ ٣٠ يومًا |
| `BACKUP_NOTIFY_EMAIL` | بريدك |
| `BACKUP_PASSPHRASE` | 🔒 **غير موجود في الكود اليوم — سأضيفه** لتشفير النسخ |

### ٤. المراقبة (مجانية بالكامل)
| المتغيّر | من أين |
|---|---|
| `SENTRY_DSN` | 🔒 من sentry.io بعد إنشاء مشروع PHP/Laravel (خطة مجانية) |
| `SENTRY_AUTH_TOKEN` (اختياري) | 🔒 لرفع source maps |
| `TELEGRAM_BOT_TOKEN` | 🔒 من `@BotFather` |
| `TELEGRAM_CHAT_ID` | من `@userinfobot` |

### ٥. الرسائل النصية / OTP (اختياري الآن)
الكود يدعم `SMS_DRIVER=log` فقط — **لا يوجد مزوّد حقيقي مبني**. إن أردت OTP حقيقيًا أحتاج:
`SMS_DRIVER` + مزوّد (Twilio / Unifonic / Vodafone SMS) ومفاتيحه 🔒 — **ويحتاج بناءً** (ليس مجرد متغيّر).

### ٦. Push / Realtime (اختياري)
`FIREBASE_*` (٦ قيم، بعضها 🔒) و`PUSHER_*` أو `ABLY_KEY` 🔒 — فقط إن أردت إشعارات فورية للتطبيق.

### ٧. OpenPanel (منجز ✅)
`OPENPANEL_CLIENT_ID` و`OPENPANEL_CLIENT_SECRET` 🔒 — **موجودان**.
أنصح بإنشاء Client جديد واستبدال السرّ لأنه ظهر في محادثة.

---

## القسم ج — قرارات أرقام لا أسرار (أقترح قيمًا، أكّدها أو غيّرها)

| المتغيّر | اقتراحي | لماذا |
|---|---|---|
| `SAAS_REGISTRATION_ENABLED` | `false` | دخول بالدعوة فقط (قرارك) |
| `SAAS_TRIAL_DAYS` | `14` | تجربة قبل الدفع |
| `SAAS_MAX_TENANTS` | `20` | سقف واقعي للمرحلة الأولى |
| `SAAS_GRACE_PERIOD_DAYS` | `3` | مهلة قبل تعليق حساب متأخر |
| `SAAS_FEATURE_INSURANCE` / `LOYALTY_CRM` / `MULTI_WAREHOUSE` / `DRUG_TRACEABILITY` / `RECEIPT_PRINTING` / `API_ACCESS` | `false` | النواة فقط (قرارك) — تُفتح لاحقًا |
| `SAAS_REGISTRATION_ENABLED` + `SAAS_REQUIRE_EMAIL_VERIFICATION` | `false` + `true` | دعوة + تحقّق بريد |
| `RATE_LIMIT_ATTEMPTS` / `RATE_LIMIT_DECAY_MINUTES` | `60` / `1` | افتراضي معقول |
| `AUDIT_LOG_RETENTION_DAYS` | `730` | سجل تدقيق سنتين |
| `BACKUP_RETENTION_DAYS` | `30` | مع نسخ كل ساعة |
| `2FA_ENABLED` / `2FA_FORCE_FOR_ADMINS` | `false` / `false` | يمكن تفعيله لاحقًا |
| `BCRYPT_ROUNDS` | `12` | أمان/أداء |
| `ALLOWED_ORIGINS` | دومينك فقط | CORS |
| `SESSION_SECURE_COOKIE` / `FORCE_HTTPS` / `HSTS_ENABLED` | `true` | إنتاج |

---

## القسم د — القرارات التي لا يمكنني اتخاذها عنك

| # | السؤال | لماذا يهم |
|---|---|---|
| ١ | **MySQL أم PostgreSQL/Supabase؟** الكود يُهيّأ لـpgsql، وخطتي كُتبت لـMySQL | يحدّد النسخ الاحتياطي، الاستعادة، وملفات Docker كاملة |
| ٢ | **هل يوجد Redis على السيرفر؟** | إن لا: أستخدم `database`/`file` (أبطأ قليلًا لكن أبسط وأرخص) |
| ٣ | **الدومين** (ونطاق فرعي للوحة) | `APP_URL`، الشهادة، البريد |
| ٤ | **مواصفات VPS وعنوانه** + طريقة دخول آمنة (مفتاح SSH لا كلمة سر) | النشر الفعلي |
| ٥ | **حالة حساب Paymob** (بدأت التسجيل؟) | بوابة الإطلاق |
| ٦ | **مزوّد البريد** (Gmail / Zoho / Postmark) | التسجيل واستعادة كلمة السر |
| ٧ | **مكان التخزين خارج السيرفر** (S3 / Spaces / Backblaze) | النسخ الاحتياطي كل ساعة |
| ٨ | **اسم الشريك للدعم** ورقمه | التزام ٢٤/٧ لا يُعلَن قبله |
| ٩ | **٣ صيدليات مستهدفة** | بند التسويق في الأسبوع الثاني |
| ١٠ | **الاسم التجاري/الشعار/بريد الدعم** | استبدال هوية القالب |

---

## ملف جاهز للنسخ

كتبت لك قالبًا كاملًا بلا أسرار في: `zsyst-fix/env.production.template`
انسخه إلى السيرفر باسم `.env` واملأ القيم المعلَّمة بـ`<<< >>>`.

---

## ملخّص سريع

- **تحتاجه الآن:** `APP_URL` + قرار قاعدة البيانات + قرار Redis. (٣ فقط)
- **تحتاجه قبل أول عميل:** مفاتيح البريد، Paymob، التخزين/النسخ، Sentry، Telegram.
- **لا أحتاجه:** ٦٩ متغيّرًا لها قيم افتراضية، و`APP_KEY` (أولّده)، وكل ما في `config/` بحسابات داخلية.
