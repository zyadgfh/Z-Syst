# دليل إعداد بيانات الاعتماد - ملخص سريع

## 📋 البيانات المطلوبة ومصادرها

### 1. 🔑 بيانات التطبيق الأساسية

| المتغير | القيمة | من أين الحصول عليه |
|---------|--------|-------------------|
| `APP_KEY` | توليد تلقائي | `php artisan key:generate` |
| `APP_URL` | رابط موقعك | نطاقك (domain) |
| `APP_ENV` | بيئة التشغيل | `local`/`staging`/`production` |

### 2. 🗄️ قاعدة البيانات

| المتغير | القيمة | من أين الحصول عليه |
|---------|--------|-------------------|
| `DB_HOST` | سيرفر قاعدة البيانات | Laragon: `127.0.0.1` |
| `DB_PORT` | منفذ قاعدة البيانات | غالباً `3306` |
| `DB_DATABASE` | اسم قاعدة البيانات | أنشئه من MySQL |
| `DB_USERNAME` | اسم المستخدم | Laragon: `root` |
| `DB_PASSWORD` | كلمة المرور | Laragon: فارغ |

### 3. 💳 Stripe (بوابة الدفع)

#### الخطوات:
1. **إنشاء حساب:** https://stripe.com → Sign up
2. **الحصول على المفاتيح:** https://dashboard.stripe.com/apikeys
3. **إعداد Webhook:** https://dashboard.stripe.com/webhooks

| المتغير | القيمة | من أين الحصول عليه |
|---------|--------|-------------------|
| `STRIPE_KEY` | `pk_test_...` | من Dashboard → API Keys |
| `STRIPE_SECRET` | `sk_test_...` | من Dashboard → API Keys |
| `STRIPE_WEBHOOK_SECRET` | `whsec_...` | من Dashboard → Webhooks |

**ملاحظة:** للإنتاج، ستحتاج مفاتيح `pk_live_...` و `sk_live_...`

### 4. 📧 البريد الإلكتروني

#### خيار Gmail (مجاني):
1. تفعيل 2FA على حساب Gmail
2. اذهب إلى: https://myaccount.google.com/apppasswords
3. أنشئ App Password
4. انسخ كلمة المرور الـ 16 خانة

| المتغير | القيمة | من أين الحصول عليه |
|---------|--------|-------------------|
| `MAIL_HOST` | `smtp.gmail.com` | ثابت |
| `MAIL_PORT` | `587` | ثابت |
| `MAIL_USERNAME` | بريدك الإلكتروني | حساب Gmail |
| `MAIL_PASSWORD` | App Password | من Google App Passwords |

#### خيار Mailtrap (للتطوير):
1. اذهب إلى: https://mailtrap.io
2. سجل الدخول
3. احصل على SMTP credentials من Inboxes

| المتغير | القيمة | من أين الحصول عليه |
|---------|--------|-------------------|
| `MAIL_HOST` | `sandbox.smtp.mailtrap.io` | من Mailtrap |
| `MAIL_PORT` | `2525` | ثابت |
| `MAIL_USERNAME` | من Mailtrap | من حساب Mailtrap |
| `MAIL_PASSWORD` | من Mailtrap | من حساب Mailtrap |

### 5. 🗃️ Redis (اختياري)

| المتغير | القيمة | من أين الحصول عليه |
|---------|--------|-------------------|
| `REDIS_HOST` | `127.0.0.1` | محلي أو سيرفر Redis |
| `REDIS_PORT` | `6379` | ثابت |
| `REDIS_PASSWORD` | كلمة المرور | (اختياري) |

**ملاحظة:** إذا كنت تستخدم Laragon، Redis مثبت تلقائياً.

### 6. 📁 AWS S3 (اختياري للإنتاج)

#### الخطوات:
1. اذهب إلى: https://aws.amazon.com/console/
2. أنشئ حساب AWS
3. اذهب إلى IAM → Users → Create user
4. امنح صلاحيات S3
5. احصل على Access Key
6. أنشئ Bucket في S3

| المتغير | القيمة | من أين الحصول عليه |
|---------|--------|-------------------|
| `AWS_ACCESS_KEY_ID` | Access Key | من AWS IAM |
| `AWS_SECRET_ACCESS_KEY` | Secret Key | من AWS IAM |
| `AWS_DEFAULT_REGION` | المنطقة | مثل `us-east-1` |
| `AWS_BUCKET` | اسم الـ Bucket | من AWS S3 |

---

## 🚀 خطوات الإعداد السريع

### الخطوة 1: نسخ المثال
```bash
cp .env.example .env
```

### الخطوة 2: توليد المفتاح
```bash
php artisan key:generate
```

### الخطوة 3: تحديث البيانات الأساسية
في `.env`:
```env
APP_URL=http://localhost:8000
DB_DATABASE=z_syst_pharmacy
```

### الخطوة 4: إعداد البريد الإلكتروني
- **للتطوير:** استخدم Mailtrap
- **للإنتاج:** استخدم Gmail أو SendGrid

### الخطوة 5: إعداد Stripe (اختياري للبداية)
- أنشئ حساب Stripe
- احصل على المفاتيح
- أضفها إلى `.env`

### الخطوة 6: تشغيل المigrations
```bash
php artisan migrate
php artisan db:seed
```

---

## ⚠️ البيانات التي يمكن تخطيها للبداية

يمكنك البدء بدون هذه البيانات:
- ❌ Stripe (استخدم نظام الفواتير الموجود)
- ❌ AWS S3 (استخدم التخزين المحلي)
- ❌ Redis (استخدم قاعدة البيانات للتخزين المؤقت)
- ❌ Pusher (غير مطلوب للبداية)

البيانات الأساسية المطلوبة فقط:
- ✅ قاعدة البيانات
- ✅ البريد الإلكتروني (اختياري لكن موصى به)

---

## 🔐 تنبيهات أمنية

### لا تشارك أبداً:
- ❌ `APP_KEY`
- ❌ `STRIPE_SECRET`
- ❌ `STRIPE_WEBHOOK_SECRET`
- ❌ `DB_PASSWORD`
- ❌ `AWS_SECRET_ACCESS_KEY`
- ❌ `MAIL_PASSWORD`

### قواعد مهمة:
- 🔒 أضف `.env` إلى `.gitignore`
- 🔒 لا ترفع `.env` إلى GitHub
- 🔒 استخدم ملفات `.env` منفصلة لكل بيئة
- 🔒 استخدم كلمات مرور قوية

---

## 📞 للحصول على المساعدة

### Stripe:
- Dashboard: https://dashboard.stripe.com
- Documentation: https://stripe.com/docs

### Email Services:
- Mailtrap: https://mailtrap.io
- SendGrid: https://sendgrid.com/docs
- Gmail App Passwords: https://myaccount.google.com/apppasswords

### AWS:
- Console: https://aws.amazon.com/console/
- Documentation: https://docs.aws.amazon.com

---

## ✅ قائمة التحقق

### للبدء السريع:
- [ ] نسخ `.env.example` إلى `.env`
- [ ] تشغيل `php artisan key:generate`
- [ ] إعداد قاعدة البيانات في `.env`
- [ ] تشغيل `php artisan migrate`
- [ ] تشغيل `php artisan db:seed`
- [ ] إعداد البريد الإلكتروني (اختياري)
- [ ] إعداد Stripe (اختياري)

### للإنتاج:
- [ ] كل ما سبق +
- [ ] إعداد Stripe للإنتاج
- [ ] إعداد AWS S3
- [ ] إعداد Redis
- [ ] تفعيل HTTPS
- [ ] إعداد النطاق

---

**التالي:** بعد إعداد البيانات، شغّل `php artisan migrate` لإنشاء جداول قاعدة البيانات.
