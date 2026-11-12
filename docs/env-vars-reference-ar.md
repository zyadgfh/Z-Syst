# مرجع متغيرات البيئة — Z-Syst (أمثلة لكل متغير)

كل المتغيرات التي يقرأها الكود فعلياً (مستخرجة من `config/*.php`) مع **مثال قيمة** لكل واحد.
العمود «الحالة»: 🔴 مطلوب للتشغيل، 🟡 مهم للنشر، ⚪ اختياري (له قيمة افتراضية في الكود).

> ⚠️ الأمثلة هنا **قيم وهمية** للتوضيح فقط. لا تضعها في الإنتاج ولا ترفع ملف `.env` الحقيقي إلى Git.

---

## 1) أساسيات التطبيق

| المتغير | مثال | الحالة | ملاحظات |
|---|---|---|---|
| `APP_NAME` | `"Z-Syst"` | 🟡 | يظهر في العناوين واسم المرسل في البريد |
| `APP_ENV` | `production` | 🔴 | `local` / `staging` / `production` / `testing` |
| `APP_KEY` | `base64:1vLlMT1zD+uxKe1h2zqz2OJnD1VtUUWySinrV34A/lY=` | 🔴 | `php artisan key:generate --show` — تغييره يفسد كل البيانات المشفّرة |
| `APP_DEBUG` | `false` | 🔴 | `true` يكشف ال stack traces للزوار — ممنوع في الإنتاج |
| `APP_URL` | `https://api.z-syst.com` | 🔴 | يؤثر على `asset()` وروابط التحقق في البريد |
| `ASSET_URL` | `https://cdn.z-syst.com` | ⚪ | لو الأصول على CDN |
| `DEMO_MODE` | `false` | ⚪ | `true` يمنع عمليات الكتابة (نسخة عرض) |
| `AUTH_USER_MODEL` | `App\Models\User` | ⚪ | غيّره فقط لو نقلت موديل المستخدم |
| `BCRYPT_ROUNDS` | `12` | ⚪ | 10 أسرع، 12 أأمن |
| `OTP_VISIBILITY_TIME` | `10` | ⚪ | مدة صلاحية كود الـ OTP بالدقائق |

## 2) قاعدة البيانات (Postgres)

| المتغير | مثال | الحالة | ملاحظات |
|---|---|---|---|
| `DB_CONNECTION` | `pgsql` | 🔴 | لا تستخدم `sqlite` في الإنتاج |
| `DATABASE_URL` | `postgresql://postgres:pwd@host:5432/postgres?sslmode=require` | ⚪ | بديل يلغي الحاجة لبقية `DB_*` |
| `DB_HOST` | `aws-0-eu-central-1.pooler.supabase.com` | 🔴 | من Supabase → Connect (pooler يشتغل على IPv4) |
| `DB_PORT` | `5432` | 🔴 | 5432 = session pooler، 6543 = transaction pooler |
| `DB_DATABASE` | `postgres` | 🔴 | اسم القاعدة في Supabase |
| `DB_NAME` | `postgres` | ⚪ | بديل تستخدمه منصات مثل Wasmer؛ الكود يقرأه لو `DB_DATABASE` فاضي |
| `DB_USERNAME` | `postgres.atvttthrqsybjjwrbiyl` | 🔴 | مع الـ pooler لازم لاحقة `.ref` |
| `DB_PASSWORD` | `Str0ng-Passw0rd!2026` | 🔴 | Supabase → Project Settings → Database |
| `DB_SSLMODE` | `require` | 🟡 | إجباري مع Supabase وWasmer DB (شهادات خاصة) |
| `DB_FOREIGN_KEYS` | `true` | ⚪ | يخص SQLite فقط |
| `DB_ENCRYPT` / `DB_TRUST_SERVER_CERTIFICATE` | `yes` / `false` | ⚪ | تخص SQL Server فقط |

## 3) Supabase (GoTrue + RLS)

| المتغير | مثال | الحالة | ملاحظات |
|---|---|---|---|
| `SUPABASE_URL` | `https://atvttthrqsybjjwrbiyl.supabase.co` | 🔴 | بدون `/` في النهاية |
| `SUPABASE_ANON_KEY` | `eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJyb2xlIjoiYW5vbiJ9...` | 🔴 | مفتاح عام (يصل للمتصفح) |
| `SUPABASE_SERVICE_ROLE_KEY` | `eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJyb2xlIjoic2VydmljZV9yb2xlIn0...` | 🔴 | **سرّي جداً** — سيرفر فقط |
| `SUPABASE_JWT_SECRET` | `super-secret-jwt-token-with-at-least-32-characters-long` | 🟡 | Project Settings → API → JWT Settings (HS256) |
| `SUPABASE_JWT_ISSUER` | `https://atvttthrqsybjjwrbiyl.supabase.co/auth/v1` | ⚪ | افتراضياً يُبنى من `SUPABASE_URL` |
| `SUPABASE_JWT_AUDIENCE` | `authenticated` | ⚪ | |
| `SUPABASE_JWT_LEEWAY` | `10` | ⚪ | سماحية انحراف الساعة بالثواني |
| `SUPABASE_ENFORCE_RLS` | `true` | 🔴 | لا تُعطّله في الإنتاج (عزل المستأجرين) |
| `SUPABASE_FALLBACK_ELOQUENT` | `false` | ⚪ | `true` للتطوير المحلي بدون Supabase |
| `SUPABASE_HTTP_TIMEOUT` | `10` | ⚪ | مهلة نداءات GoTrue بالثواني |
| `SUPABASE_CAPTCHA_ENABLED` | `false` | ⚪ | لا تفعّله إلا بعد تفعيله في Supabase |
| `SUPABASE_CAPTCHA_PROVIDER` | `hcaptcha` | ⚪ | |
| `HCAPTCHA_SITE_KEY` | `10000000-ffff-ffff-ffff-000000000001` | ⚪ | مفتاح hCaptcha التجريبي (public site key) |

## 4) الجلسات / الكاش / الطوابير

| المتغير | مثال | الحالة | ملاحظات |
|---|---|---|---|
| `SESSION_DRIVER` | `database` | 🔴 | على Wasmer: `database` (لا قرص قابل للكتابة). محلياً `file` |
| `SESSION_LIFETIME` | `120` | ⚪ | بالدقائق |
| `SESSION_SECURE_COOKIE` | `true` | 🔴 | يتطلب HTTPS |
| `SESSION_DOMAIN` | `.z-syst.com` | ⚪ | اجعله `null` على نطاق Wasmer المؤقت |
| `SESSION_CONNECTION` | `pgsql` | ⚪ | اتصال قاعدة بيانات الجلسات |
| `SESSION_STORE` | `redis` | ⚪ | يخص برامج الجلسات المبنية على الكاش |
| `SESSION_FILES_PATH` | `/app/storage/framework/sessions` | ⚪ | أضفناه لنقل جلسات الملفات إلى volume |
| `CACHE_DRIVER` | `array` | 🔴 | على Wasmer: `array`. الإنتاج الكامل: `redis` |
| `CACHE_PREFIX` | `zsyst_cache` | ⚪ | يمنع التعارض على Redis مشترك |
| `CACHE_LIFETIME` | `3600` | ⚪ | بالثواني |
| `QUEUE_CONNECTION` | `database` | 🔴 | على Wasmer لا يوجد عامل يشغّل الطابور |
| `QUEUE_FAILED_DRIVER` | `database-uuids` | ⚪ | |
| `REDIS_CLIENT` | `phpredis` | ⚪ | `predis` لا يحتاج إضافة PHP |
| `REDIS_HOST` | `127.0.0.1` | ⚪ | أو `valkey.z-syst.internal` |
| `REDIS_PASSWORD` | `null` | ⚪ | |
| `REDIS_PORT` | `6379` | ⚪ | |
| `REDIS_USERNAME` | `null` | ⚪ | |
| `REDIS_DB` | `0` | ⚪ | |
| `REDIS_CACHE_DB` | `1` | ⚪ | |
| `REDIS_QUEUE` | `2` | ⚪ | |
| `REDIS_PREFIX` | `zsyst_` | ⚪ | |
| `REDIS_CLUSTER` | `redis` | ⚪ | |
| `REDIS_URL` | `redis://default:pass@host:6379` | ⚪ | بديل عن الـ host/port |
| `MEMCACHED_HOST` | `127.0.0.1` | ⚪ | |
| `MEMCACHED_PORT` | `11211` | ⚪ | |
| `MEMCACHED_USERNAME` / `MEMCACHED_PASSWORD` | `null` / `null` | ⚪ | |
| `MEMCACHED_PERSISTENT_ID` | `zsyst` | ⚪ | |
| `DYNAMODB_CACHE_TABLE` | `cache` | ⚪ | لو استخدمت DynamoDB |
| `DYNAMODB_ENDPOINT` | `https://dynamodb.eu-central-1.amazonaws.com` | ⚪ | |
| `SQS_PREFIX` | `https://sqs.eu-central-1.amazonaws.com/123456789012` | ⚪ | |
| `SQS_QUEUE` | `zsyst-jobs` | ⚪ | |
| `SQS_SUFFIX` | `null` | ⚪ | |

## 5) التخزين والملفات والنسخ الاحتياطي

| المتغير | مثال | الحالة | ملاحظات |
|---|---|---|---|
| `FILESYSTEM_DISK` | `s3` | 🟡 | على Wasmer استخدم S3/Supabase Storage |
| `AWS_ACCESS_KEY_ID` | `AKIAIOSFODNN7EXAMPLE` | ⚪ | |
| `AWS_SECRET_ACCESS_KEY` | `wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY` | ⚪ | |
| `AWS_DEFAULT_REGION` | `eu-central-1` | ⚪ | منطقة مشروعك في Supabase |
| `AWS_BUCKET` | `z-syst-uploads` | ⚪ | |
| `AWS_ENDPOINT` | `https://atvttthrqsybjjwrbiyl.supabase.co/storage/v1/s3` | ⚪ | لـ Supabase Storage S3 |
| `AWS_URL` | `https://cdn.z-syst.com` | ⚪ | رابط عام للأصول |
| `AWS_USE_PATH_STYLE_ENDPOINT` | `true` | ⚪ | إجباري مع Supabase Storage وMinIO |
| `UPLOADS_CLOUD_ENABLED` | `true` | ⚪ | |
| `UPLOADS_CLOUD_BUCKET` | `uploads` | ⚪ | |
| `UPLOADS_ALLOWED_EXTENSIONS` | `png,jpg,jpeg,webp,gif,pdf` | ⚪ | قائمة مفصولة بفواصل |
| `UPLOADS_ALLOWED_MIMES` | `image/png,image/jpeg,image/webp,image/gif,application/pdf` | ⚪ | |
| `BACKUP_ENABLED` | `true` | ⚪ | لا يوجد scheduler على Wasmer |
| `BACKUP_DISK` | `s3` | ⚪ | |
| `BACKUP_PATH` | `backups` | ⚪ | |
| `BACKUP_SCHEDULE` | `"0 2 * * *"` | ⚪ | تعبير cron |
| `BACKUP_RETENTION_DAYS` | `30` | ⚪ | |
| `BACKUP_MAX_BACKUPS` | `10` | ⚪ | |
| `BACKUP_CLOUD_ENABLED` | `true` | ⚪ | |
| `BACKUP_CLOUD_BUCKET` | `backups` | ⚪ | |
| `BACKUP_CLOUD_KEEP_DAYS` | `30` | ⚪ | |
| `BACKUP_NOTIFY_ON_SUCCESS` / `..._ON_FAILURE` | `true` / `true` | ⚪ | |
| `BACKUP_NOTIFY_EMAIL` | `admin@z-syst.com` | ⚪ | |

## 6) البريد

| المتغير | مثال | الحالة | ملاحظات |
|---|---|---|---|
| `MAIL_MAILER` | `smtp` | 🟡 | `log` يكتب الرسائل في اللوج فقط (اختبار) |
| `MAIL_HOST` | `smtp.gmail.com` | 🟡 | |
| `MAIL_PORT` | `587` | 🟡 | 587 = TLS، 465 = SSL |
| `MAIL_USERNAME` | `noreply@z-syst.com` | 🟡 | |
| `MAIL_PASSWORD` | `abcd efgh ijkl mnop` | 🟡 | App Password وليس كلمة مرور Gmail |
| `MAIL_ENCRYPTION` | `tls` | 🟡 | |
| `MAIL_FROM_ADDRESS` | `"noreply@z-syst.com"` | 🟡 | |
| `MAIL_FROM_NAME` | `"${APP_NAME}"` | ⚪ | |
| `MAIL_EHLO_DOMAIN` | `z-syst.com` | ⚪ | يحسّن تسليم البريد |
| `MAIL_SENDMAIL_PATH` | `/usr/sbin/sendmail -bs -i` | ⚪ | مع `MAIL_MAILER=sendmail` |
| `MAIL_LOG_CHANNEL` | `mail` | ⚪ | |
| `MAILGUN_DOMAIN` | `mg.z-syst.com` | ⚪ | |
| `MAILGUN_SECRET` | `key-0123456789abcdef` | ⚪ | |
| `MAILGUN_ENDPOINT` | `api.mailgun.net` | ⚪ | eu → `api.eu.mailgun.net` |
| `POSTMARK_TOKEN` | `xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx` | ⚪ | |

## 7) البث اللحظي (Realtime)

| المتغير | مثال | الحالة | ملاحظات |
|---|---|---|---|
| `BROADCAST_DRIVER` | `log` | 🟡 | `pusher`/`ably`/`redis` حسب الحاجة |
| `PUSHER_APP_ID` | `1234567` | ⚪ | |
| `PUSHER_APP_KEY` | `1a2b3c4d5e6f7g8h9i0j` | ⚪ | |
| `PUSHER_APP_SECRET` | `abcdef1234567890abcdef` | ⚪ | |
| `PUSHER_HOST` | `soketi.z-syst.com` | ⚪ | اتركه فاضي لـ Pusher السحابي |
| `PUSHER_PORT` | `443` | ⚪ | |
| `PUSHER_SCHEME` | `https` | ⚪ | |
| `PUSHER_APP_CLUSTER` | `mt1` | ⚪ | |
| `ABLY_KEY` | `aBcDeF.gHiJkL:MnOpQrStUvWxYz123456` | ⚪ | |

## 8) الأمان والـ CORS والـ API

| المتغير | مثال | الحالة | ملاحظات |
|---|---|---|---|
| `FORCE_HTTPS` | `true` | 🔴 | على Wasmer الشهادة تلقائية |
| `HSTS_ENABLED` | `true` | ⚪ | |
| `HSTS_MAX_AGE` | `31536000` | ⚪ | سنة |
| `CSP_ENABLED` | `true` | ⚪ | |
| `ALLOWED_ORIGINS` | `"https://app.z-syst.com"` | ⚪ | طبقة `config/security.php` |
| `CORS_ALLOWED_ORIGINS` | `"https://app.z-syst.com,http://localhost:3000"` | 🔴 | لا تستخدم `*` مع Authorization |
| `CORS_ALLOWED_ORIGINS_PATTERNS` | `"https://*.z-syst.com"` | ⚪ | |
| `CORS_SUPPORTS_CREDENTIALS` | `false` | ⚪ | `true` يتطلب نطاقاً صريحاً |
| `SANCTUM_STATEFUL_DOMAINS` | `app.z-syst.com,localhost,localhost:3000` | ⚪ | لازم لتوكنات Sanctum من المتصفح |
| `2FA_ENABLED` | `false` | ⚪ | |
| `2FA_FORCE_FOR_ADMINS` | `false` | ⚪ | |
| `AUDIT_LOGGING_ENABLED` | `true` | ⚪ | |
| `AUDIT_LOG_RETENTION_DAYS` | `90` | ⚪ | |
| `RATE_LIMIT_ENABLED` | `true` | ⚪ | |
| `RATE_LIMIT_ATTEMPTS` | `60` | ⚪ | |
| `RATE_LIMIT_DECAY_MINUTES` | `1` | ⚪ | |
| `API_RATE_LIMIT` | `1000` | ⚪ | |
| `API_RATE_LIMIT_PERIOD` | `60` | ⚪ | بالدقائق |
| `API_REQUIRE_KEY` | `true` | ⚪ | |
| `API_IP_WHITELIST_ENABLED` | `false` | ⚪ | |
| `API_IP_WHITELIST` | `"203.0.113.10,198.51.100.0/24"` | ⚪ | |

## 9) إعدادات SaaS والاشتراكات

| المتغير | مثال | الحالة | ملاحظات |
|---|---|---|---|
| `SAAS_ENABLED` | `true` | ⚪ | |
| `SAAS_MAX_TENANTS` | `1000` | ⚪ | |
| `SAAS_MAX_USERS_PER_TENANT` | `50` | ⚪ | |
| `SAAS_MAX_PRODUCTS_PER_TENANT` | `10000` | ⚪ | |
| `SAAS_DEFAULT_PLAN_ID` | `1` | ⚪ | |
| `SAAS_TRIAL_DAYS` | `14` | ⚪ | |
| `SAAS_GRACE_PERIOD_DAYS` | `7` | ⚪ | |
| `SAAS_ISOLATION_MODE` | `shared` | ⚪ | `shared` / `dedicated` / `hybrid` |
| `SAAS_REGISTRATION_ENABLED` | `true` | ⚪ | |
| `SAAS_REQUIRE_EMAIL_VERIFICATION` | `true` | ⚪ | |
| `SAAS_AUTO_ACTIVATE_TRIAL` | `true` | ⚪ | |
| `SAAS_FEATURE_MULTI_WAREHOUSE` | `true` | ⚪ | |
| `SAAS_FEATURE_INSURANCE` | `true` | ⚪ | |
| `SAAS_FEATURE_LOYALTY_CRM` | `true` | ⚪ | |
| `SAAS_FEATURE_DRUG_TRACEABILITY` | `true` | ⚪ | |
| `SAAS_FEATURE_RECEIPT_PRINTING` | `true` | ⚪ | |
| `SAAS_FEATURE_API_ACCESS` | `false` | ⚪ | |

## 10) التكاملات المصرية (الدفع / واتساب / الفاتورة الإلكترونية)

| المتغير | مثال | الحالة | ملاحظات |
|---|---|---|---|
| `PAYMOB_ENABLED` | `true` | ⚪ | |
| `PAYMOB_BASE_URL` | `https://accept.paymob.com/api` | ⚪ | |
| `PAYMOB_API_KEY` | `ZXlKaGJHY2lPaUpJVXpJMU5pS3...` | ⚪ | من لوحة Paymob |
| `PAYMOB_INTEGRATION_ID` | `1234567` | ⚪ | |
| `PAYMOB_IFRAME_ID` | `876543` | ⚪ | |
| `PAYMOB_HMAC_SECRET` | `ABCDEF0123456789ABCDEF0123456789` | ⚪ | للتحقق من الـ webhook |
| `PAYMOB_CURRENCY` | `EGP` | ⚪ | |
| `WHATSAPP_ENABLED` | `true` | ⚪ | |
| `WHATSAPP_VERSION` | `v21.0` | ⚪ | |
| `WHATSAPP_TOKEN` | `EAAG...` | ⚪ | Meta Cloud API |
| `WHATSAPP_PHONE_NUMBER_ID` | `123456789012345` | ⚪ | |
| `MESSAGE_ENABLED` | `true` | ⚪ | |
| `SMS_DRIVER` | `log` | ⚪ | `log` للاختبار حتى توصيل بوابة SMS |
| `ETA_ENABLED` | `false` | ⚪ | الفاتورة الإلكترونية |
| `ETA_SANDBOX` | `true` | ⚪ | `false` = الإنتاج |
| `ETA_BASE_URL` | `https://api.invoicing.eta.gov.eg/api/v1` | ⚪ | |
| `ETA_CLIENT_ID` | `eta-client-id-12345` | ⚪ | |
| `ETA_CLIENT_SECRET` | `eta-client-secret-67890` | ⚪ | |
| `ETA_ISSUER_TAX_ID` | `123456789` | ⚪ | الرقم الضريبي للصيدلية |
| `ETA_ISSUER_NAME` | `"صيدلية النور"` | ⚪ | |
| `ETA_ISSUER_GOVERNATE` | `Sohag` | ⚪ | |
| `ETA_ISSUER_CITY` | `"Sohag"` | ⚪ | |
| `ETA_ISSUER_STREET` | `"شارع الجمهورية"` | ⚪ | |
| `ETA_ACTIVITY_CODE` | `4772` | ⚪ | كود نشاط الصيدلية |
| `INTEGRATIONS_HTTP_TIMEOUT` | `15` | ⚪ | بالثواني |

## 11) اللوج والمثبّت والواجهة

| المتغير | مثال | الحالة | ملاحظات |
|---|---|---|---|
| `LOG_CHANNEL` | `errorlog` | 🟡 | على Wasmer: `errorlog` (يكتب على stderr). محلياً `stack`/`daily` |
| `LOG_LEVEL` | `warning` | ⚪ | `debug`/`info`/`warning`/`error` |
| `LOG_DEPRECATIONS_CHANNEL` | `null` | ⚪ | |
| `LOG_SLACK_WEBHOOK_URL` | `https://hooks.slack.com/services/T000/B000/XXXX` | ⚪ | |
| `INSTALLER_HEADER_KEYS` | `purchase_code,envato_username` | ⚪ | تخصّ المثبّت فقط |
| `INSTALLER_API_URL` | `https://api.envato.com` | ⚪ | |
| `INSTALLER_DOWNLOAD_PATH` | `/storage/app/installer` | ⚪ | |
| `VIEW_COMPILED_PATH` | `/app/storage/framework/views` | 🟡 | ضروري على Wasmer (القرص للقراءة فقط) |
| `VITE_FIREBASE_API_KEY` | `AIzaSyD-EXAMPLE_KEY_1234567890` | ⚪ | تُبنى في الواجهة، ليست سرية |
| `VITE_FIREBASE_PROJECT_ID` | `z-syst` | ⚪ | |
| `VITE_PUSHER_APP_KEY` | `1a2b3c4d5e6f7g8h9i0j` | ⚪ | |
| `OPENPANEL_ENABLED` | `false` | ⚪ | |
| `OPENPANEL_CLIENT_ID` | `op_xxxxxxxxxxxx` | ⚪ | |
| `OPENPANEL_CLIENT_SECRET` | `sk_xxxxxxxxxxxx` | ⚪ | سرّي |
| `OPENPANEL_API_URL` | `https://api.openpanel.dev` | ⚪ | |

---

## الحد الأدنى للتشغيل على Wasmer Edge

هذه الـ 17 فقط كافية لتشغيل التطبيق هناك (الباقي له قيم افتراضية):

```dotenv
APP_NAME="Z-Syst"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:1vLlMT1zD+uxKe1h2zqz2OJnD1VtUUWySinrV34A/lY=
APP_URL=https://z-syst.zyadgfh.wasmer.app

DB_CONNECTION=pgsql
DB_HOST=aws-0-eu-central-1.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.atvttthrqsybjjwrbiyl
DB_PASSWORD=Str0ng-Passw0rd!2026
DB_SSLMODE=require

SESSION_DRIVER=database
CACHE_DRIVER=array
LOG_CHANNEL=errorlog
VIEW_COMPILED_PATH=/app/storage/framework/views

SUPABASE_URL=https://atvttthrqsybjjwrbiyl.supabase.co
SUPABASE_ANON_KEY=eyJhbGciOi...anon
SUPABASE_SERVICE_ROLE_KEY=eyJhbGciOi...service_role
SUPABASE_JWT_SECRET=super-secret-jwt-token-with-at-least-32-characters-long
SUPABASE_ENFORCE_RLS=true
```
