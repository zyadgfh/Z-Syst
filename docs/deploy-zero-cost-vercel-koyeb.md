# نشر Z-Syst بميزانية صفر — Vercel (واجهة) + Koyeb (باك اند)

> سؤالك: "لما لا ننشر الباك اند على Render والفرونت على Vercel؟" — فكرة ممتازة، لكن Render المجاني ينام. هذا هو الحل الأفضل بدون نوم.

## الخلاصة (صفر تكلفة)
- **الواجهة (Frontend):** Vercel مجاني — لا ينام أبداً، تحديث تلقائي عند كل push
- **الباك اند (Laravel):** Koyeb أو Fly.io مجاني — لا ينام (أو ينام أقل بكثير من Render)
- **قاعدة البيانات:** Supabase مجاني (500MB، 50k مستخدم)
- **الدومين:** `z-syst.vercel.app` مجاني (أو `is-a.dev` مجاني ثانٍ)

## لماذا ليس Render؟
- Render Free: ينام بعد 15 دقيقة → أول طلب بعده 30 ثانية → صيدلي لن ينتظر عند البيع
- Koyeb Free: 1 خدمة مجانية، لا تنام طالما في حدود 5GB bandwidth
- Fly.io Free: 3 VMs صغيرة، لا تنام إذا استخدمت `auto_stop_machines = false` + `min_machines_running = 1` (مجاني حتى 160 ساعة)

## الحل المقترح: Vercel + Koyeb

### 1) الواجهة على Vercel (جاهز)
- `vercel.json` موجود بالفعل
- `npm run build` → يبني Vite → ينشر تلقائياً
- كل push لـ `main` → Deploy تلقائي في 40 ثانية

### 2) الباك اند Laravel على Koyeb (30 دقيقة إعداد)
```bash
# 1. إنشاء Dockerfile (موجود في docker/)
# 2. ربط GitHub بـ Koyeb
# 3. إعداد المتغيرات في Koyeb:
APP_URL=https://z-syst-api.koyeb.app
APP_KEY=base64:...
DB_CONNECTION=pgsql
DB_HOST=db.atvttthrqsybjjwrbiyl.supabase.co
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=...
SUPABASE_URL=https://atvttthrqsybjjwrbiyl.supabase.co
```

**Koyeb إعداد مجاني:**
- Instance: Nano (512MB RAM) — مجاني
- Regions: Frankfurt (قريب من EU Supabase)
- Health check: `/api/health`
- Auto-deploy on Git push

### 3) البديل: Fly.io (لو Koyeb لم يعجبك)
```toml
# fly.toml
app = "z-syst-api"
primary_region = "fra"

[http_service]
  internal_port = 8000
  force_https = true
  auto_stop_machines = false
  min_machines_running = 1
```

## مقارنة التكلفة الشهرية (100 صيدلية)

|  | Vercel + Koyeb | Vercel + Render Free | Vercel + VPS 5$ |
|---|---|---|---|
| التكلفة | 0 | 0 لكن ينام | 5$ |
| النوم | لا | نعم 30ث | لا |
| السرعة | 50-100ms | 30ث بعد النوم | 50ms |
| التحديث | تلقائي | تلقائي | يدوي |

## خطة النشر بميزانية صفر (اليوم)

### اليوم 1
1. ارفع الواجهة على Vercel (موجودة)
2. اربط `z-syst.vercel.app` + أضف `is-a.dev` كدومين ثانٍ مجاني

### اليوم 2
1. أنشئ حساب Koyeb مجاني
2. اربط GitHub → اختر فرع `main` → Deploy Laravel
3. أضف متغيرات البيئة (نسخ من `.env.example` بدون كلمات سر حقيقية)

### اليوم 3
1. اختبر: `https://z-syst-api.koyeb.app/api/health` → يجب 200
2. اختبر POS: مسح باركود → بيع → يجب <1 ثانية
3. فعّل GitHub Actions: كل push → Deploy تلقائي

## هل نستطيع إبقاء Laravel على Vercel فقط (بدون Koyeb)؟
- **نعم عبر `vercel-php` (مجتمع):** يحوّل Laravel لـ Serverless Functions على Vercel — مجاني ولا ينام
- **المشكلة:** بعض إضافات PHP لا تعمل Serverless + الـ Storage يحتاج S3
- **التوصية:** ابق على Vercel للواجهة + Koyeb للباك اند — الأكثر استقراراً بميزانية صفر

## ماذا عن الدومين المجاني بعد Vercel؟
- **اقتراحك "اقترح لي أفضل خيار بعد Vercel":** `is-a.dev` (مجاني، موثوق، عبر GitHub) أو `js.org`
- **التسجيل:** طلب Pull Request على GitHub → يضاف DNS → يصبح `z-syst.is-a.dev` مجاناً للأبد
- **البديل:** ابق على `z-syst.vercel.app` حتى أول 1000$ ربح ثم اشتر `z-syst.com` بـ 12$ / سنة

## الخلاصة
- **لا تستخدم Render Free للـ POS** — ستنام وتخسر صيدلية
- **استخدم Vercel (واجهة) + Koyeb (باك اند) + Supabase (DB)** — كلها مجانية ولا تنام
- **الدومين:** `vercel.app` يكفي الآن، `is-a.dev` ثانٍ مجاني إذا أردت
