# Z-Syst Pharmacy - إعداد بيانات الاعتماد (Credentials Guide)

## 📋 نظرة عامة على البيانات المطلوبة

### البيانات الأساسية المطلوبة:
1. **Application Credentials** - بيانات التطبيق الأساسية
2. **Database Credentials** - بيانات قاعدة البيانات
3. **Stripe Payment Gateway** - بوابة الدفع (Stripe)
4. **Email Service** - خدمة البريد الإلكتروني
5. **Cache/Queue** - التخزين المؤقت والطابور
6. **File Storage** - تخزين الملفات
7. **API Keys** - مفاتيح API الخارجية (اختياري)

---

## 🔑 1. Application Credentials

### البيانات المطلوبة في `.env`:
```env
APP_NAME="Z-Syst Pharmacy"
APP_ENV=production
APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
APP_DEBUG=false
APP_URL=https://your-domain.com
```

### من أين الحصول على القيم:

#### APP_KEY:
**مصدر:** توليد تلقائي
**كيفية الحصول:**
```bash
# إذا لم يكن موجوداً، قم بتوليده
php artisan key:generate
```
**ملاحظة:** هذه القيمة تُولد تلقائياً عند أول تثبيت للمشروع. لا تشاركها مع أحد.

#### APP_URL:
**مصدر:** النطاق الخاص بك
**كيفية الحصول:**
- **التطوير المحلي:** `http://localhost:8000`
- **الإنتاج:** `https://your-pharmacy-domain.com`
- **Staging:** `https://staging.your-pharmacy-domain.com`

#### APP_ENV:
**القيم الممكنة:**
- `local` - للبيئة المحلية
- `staging` - لبيئة الاختبار
- `production` - للإنتاج

---

## 🗄️ 2. Database Credentials

### البيانات المطلوبة في `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=z_syst_pharmacy
DB_USERNAME=your_db_username
DB_PASSWORD=your_db_password
```

### من أين الحصول على القيم:

#### DB_HOST, DB_PORT:
**مصدر:** مزود قاعدة البيانات
**الخيارات:**
- **Localhost (Laragon/XAMPP):** `127.0.0.1:3306`
- **MySQL Server:** عنوان IP الخاص بالسيرفر
- **Cloud Database (AWS RDS, DigitalOcean, etc.):** من لوحة التحكم

#### DB_DATABASE:
**مصدر:** اسم قاعدة البيانات
**كيفية الحصول:**
```bash
# إنشاء قاعدة بيانات جديدة
mysql -u root -p
CREATE DATABASE z_syst_pharmacy;
```

#### DB_USERNAME, DB_PASSWORD:
**مصدر:** بيانات دخول MySQL
**كيفية الحصول:**
- **Localhost:** غالباً `root` بدون كلمة مرور (في Laragon)
- **Production:** من مزود الاستضافة أو سيرفر قاعدة البيانات
- **Cloud:** من لوحة التحكم (AWS RDS, DigitalOcean, etc.)

---

## 💳 3. Stripe Payment Gateway

### البيانات المطلوبة في `.env`:
```env
STRIPE_KEY=pk_test_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
STRIPE_SECRET=sk_test_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
STRIPE_CURRENCY=usd
```

### من أين الحصول على القيم:

#### الخطوة 1: إنشاء حساب Stripe
1. اذهب إلى: https://stripe.com
2. انقر على "Start now" أو "Sign up"
3. سجل الدخول أو أنشئ حساب جديد
4. أكمل التسجيل ببيانات العمل

#### الخطوة 2: الحصول على API Keys
1. بعد تسجيل الدخول، اذهب إلى: https://dashboard.stripe.com/apikeys
2. ستجد نوعين من المفاتيح:
   - **Publishable key (pk_test_...)** - للاستخدام في الواجهة الأمامية
   - **Secret key (sk_test_...)** - للاستخدام في الخلفية (لا تشاركها!)

#### الخطوة 3: إعداد Webhook Secret
1. اذهب إلى: https://dashboard.stripe.com/webhooks
2. انقر على "Add endpoint"
3. أدخل URL الويب هوك:
   - **Development:** `https://your-dev-domain.com/stripe/webhook`
   - **Production:** `https://your-domain.com/stripe/webhook`
4. اختر الأحداث المطلوبة:
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
5. انقر "Add endpoint"
6. انقر على الويب هوك المنشأ
7. انقر على "Reveal" للإظهار لنسخ الـ webhook secret

#### الخطوة 4: الانتقال إلى الإنتاج
1. في لوحة تحكم Stripe، انقر على "Settings" → "API keys"
2. انقر على "Enable Live Mode"
3. ستحتاج إلى مفاتيح جديدة للإنتاج:
   - `pk_live_...` - للاستخدام في الإنتاج
   - `sk_live_...` - للاستخدام في الإنتاج

---

## 📧 4. Email Service

### الخيار A: SMTP (موصى به للإنتاج)
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="${APP_NAME}"
```

#### من أين الحصول على القيم (Gmail):
1. تفعيل 2FA (المصادقة الثنائية) على حساب Gmail
2. اذهب إلى: https://myaccount.google.com/apppasswords
3. اختر "Mail" أو "Other (Custom name)"
4. أدخل اسماً للتطبيق (مثلاً: "Z-Syst Pharmacy")
5. سيظهر لك كلمة مرور 16 خانة - انسخها واستخدمها

#### الخيارات الأخرى:
- **SendGrid:** https://sendgrid.com - يقدم 100 رسالة مجانية يومياً
- **Mailgun:** https://www.mailgun.com - يقدم 5000 رسالة مجانية شهرياً
- **AWS SES:** https://aws.amazon.com/ses - 62000 رسالة مجانية شهرياً

### الخيار B: Mailtrap (للتطوير فقط)
```env
MAIL_MAILER=smtp
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-mailtrap-username
MAIL_PASSWORD=your-mailtrap-password
MAIL_ENCRYPTION=null
```

#### من أين الحصول على القيم:
1. اذهب إلى: https://mailtrap.io
2. سجل الدخول أو أنشئ حساب جديد
3. من لوحة التحكم، انقر على "Inboxes"
4. سيظهر لك SMTP credentials

---

## 🗃️ 5. Cache and Queue

### Redis (موصى به):
```env
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

#### من أين الحصول على القيم:
- **Localhost:** تأكد من تثبيت Redis على جهازك
  - Windows: استخدم Laragon (يتضمن Redis)
  - Mac: `brew install redis`
  - Linux: `sudo apt install redis-server`
- **Production:** من مزود الاستضافة أو سيرفر Redis

### Database (بدون Redis):
```env
CACHE_DRIVER=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database
```

---

## 📁 6. File Storage

### Amazon S3 (موصى به للإنتاج):
```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-access-key
AWS_SECRET_ACCESS_KEY=your-secret-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket-name
AWS_URL=https://your-bucket-name.s3.amazonaws.com
```

#### من أين الحصول على القيم:
1. اذهب إلى: https://aws.amazon.com/console/
2. سجل الدخول أو أنشئ حساب AWS
3. اذهب إلى: IAM → Users → Create user
4. امنح الصلاحيات (AmazonS3FullAccess)
5. انشئ Access Key و Secret Key
6. اذهب إلى: S3 → Create bucket
7. استخدم اسم الـ bucket الذي أنشأته

### Local Storage (للتطوير):
```env
FILESYSTEM_DISK=local
```

---

## 🔐 7. Security Settings

### البيانات المطلوبة:
```env
SANCTUM_STATEFUL_DOMAINS=your-domain.com
SESSION_DOMAIN=.your-domain.com
TRUSTED_PROXIES=*
```

#### من أين الحصول على القيم:
- **SANCTUM_STATEFUL_DOMAINS:** نطاق موقعك
- **SESSION_DOMAIN:** نطاق موقعك مع نقطة في البداية (للسيتي كوكيز عبر النطاقات الفرعية)

---

## 📋 نموذج `.env` كامل

```env
APP_NAME="Z-Syst Pharmacy"
APP_ENV=production
APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
APP_DEBUG=false
APP_URL=https://your-pharmacy-domain.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=z_syst_pharmacy
DB_USERNAME=your_db_username
DB_PASSWORD=your_db_password

BROADCAST_DRIVER=reverb
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-pharmacy-domain.com
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=
PUSHER_PORT=443
PUSHER_SCHEME=https
PUSHER_APP_CLUSTER=mt1

VITE_APP_NAME="${APP_NAME}"
VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
VITE_PUSHER_APP_CLUSTER="${PUSHER_APP_CLUSTER}"

# Stripe Payment Gateway
STRIPE_KEY=pk_test_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
STRIPE_SECRET=sk_test_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
STRIPE_WEBHOOK_SECRET=whsec_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
STRIPE_CURRENCY=usd

# Security
SANCTUM_STATEFUL_DOMAINS=your-pharmacy-domain.com
SESSION_DOMAIN=.your-pharmacy-domain.com
TRUSTED_PROXIES=*
```

---

## 🚀 خطوات الإعداد السريع

### 1. النسخ من المثال:
```bash
cp .env.example .env
```

### 2. توليد APP_KEY:
```bash
php artisan key:generate
```

### 3. إعداد قاعدة البيانات:
```bash
# تحديث .env ببيانات قاعدة البيانات
# ثم:
php artisan migrate
php artisan db:seed
```

### 4. إعداد البريد الإلكتروني:
- استخدم Mailtrap للتطوير
- استخدم Gmail أو SendGrid للإنتاج

### 5. إعداد Stripe:
- أنشئ حساب Stripe
- احصل على API keys
- أضفها إلى `.env`

### 6. إعداد Redis (اختياري):
- قم بتثبيت Redis
- اضبط `CACHE_DRIVER=redis` في `.env`

### 7. اختبار الإعدادات:
```bash
php artisan config:cache
php artisan route:cache
php artisan test
```

---

## ⚠️ تنبيهات أمنية مهمة

### لا تشارك أبداً:
- ❌ `APP_KEY`
- ❌ `STRIPE_SECRET`
- ❌ `STRIPE_WEBHOOK_SECRET`
- ❌ `DB_PASSWORD`
- ❌ `AWS_SECRET_ACCESS_KEY`
- ❌ `MAIL_PASSWORD`

### قم بتغيير كلمات المرور:
- 🔒 استخدم كلمات مرور قوية لقاعدة البيانات
- 🔒 قم بتغيير مفاتيح API الافتراضية
- 🔒 استخدم بيئة منفصلة للإنتاج

### احفظ `.env` بأمان:
- 📁 أضف `.env` إلى `.gitignore`
- 📁 لا ترفع `.env` إلى GitHub
- 📁 استخدم ملفات `.env` منفصلة لكل بيئة

---

## 🌐 مصادر إضافية

### Stripe:
- Dashboard: https://dashboard.stripe.com
- Documentation: https://stripe.com/docs
- Webhooks: https://stripe.com/docs/webhooks

### Email Services:
- SendGrid: https://sendgrid.com/docs
- Mailgun: https://documentation.mailgun.com
- AWS SES: https://docs.aws.amazon.com/ses

### Cloud Storage:
- AWS S3: https://docs.aws.amazon.com/s3
- DigitalOcean Spaces: https://docs.digitalocean.com/spaces

### Redis:
- Redis.io: https://redis.io/documentation
- Redis Cloud: https://redis.com/try-free

---

## 📞 الدعم

إذا واجهت مشاكل في الحصول على أي بيانات اعتماد:
1. راجع الوثائق الرسمية لكل خدمة
2. تحقق من لوحة التحكم الخاصة بالخدمة
3. تواصل مع دعم الخدمة إذا لزم الأمر

---

**التالي:** بعد إعداد جميع بيانات الاعتماد، قم بتشغيل `php artisan migrate` لإنشاء جداول قاعدة البيانات.
