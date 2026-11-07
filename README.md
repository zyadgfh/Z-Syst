# Z-Syst Pharmacy Management System

نظام شامل متعدد المستأجرين (SaaS) لإدارة الصيدليات — Laravel 12 + Supabase، بواجهة بأسلوب Apple Design، دعم PWA والعمل بلا إنترنت.

---

## 📋 نظرة عامة

Z-Syst منصة SaaS متعددة المستأجرين لإدارة الصيدليات مبنية على **Laravel 12 (PHP 8.2+)** فوق **Supabase PostgreSQL**:
- الهوية عبر Supabase Auth (GoTrue) مع Path B للمصادقة المحلية الاحتياطية (`app_users` UUID ↔ `auth.users`)
- عزل المستأجرين بـ**Row Level Security** + `TenantScoped` global scope على مستوى Eloquent (26+ موديل أعمال)
- تطبيق Flutter للـPOS في `pharmacy-store-app-codecanyon-main/`
- واجهة ويب أدمن كاملة + REST API (267+ مسارًا) للـPOS

## 🚀 المزايا

### الأساسية
- ✅ SaaS متعدد المستأجرين (عزل Eloquent + RLS)
- ✅ نقطة بيع POS (FEFO تلقائي، بيع حتى أوفلاين مع مزامنة تلقائية)
- ✅ مخزون متعدد المخازن + تحويلات بين المخازن بدورة موافقات
- ✅ مشتريات وأوامر شراء آلية بالتنبؤ (AutoOrder)
- ✅ فواتير، آجل، تحصيلات، مرتجعات
- ✅ تأمين (شركات/وثائق/مطالبات) · ولاء وكوبونات · تذكير تفاعل الطبيب
- ✅ تتبع الدفعات والاستدعاءات (من المورد للمريض)
- ✅ **نظام باركود متكامل**: توليد EAN-13/UPC-A/CODE128 برقم تحقق صحيح، صور PNG عبر GD أو SVG بديلًا، مسح POS، شيت طباعة ملصقات جاهز للطابعة، وPDF عبر dompdf
- ✅ **إدارة الأجهزة والطابعات**: كل جهاز يفتح اللوحة يُسجَّل تلقائيًا (نوعه، IP، آخر ظهور) مع اعتماد/تعليق/تعطيل، طابعات باركود/إيصالات (شبكة/USB/بلوتوث/نظام) مع افتراضيات وتعيين لكل جهاز — «جهازان بطابعة POS واحدة ولكل منهما طابعة باركود» يعمل من الصندوق، ماسح الباركود keyboard-wedge يعمل بلا تعريف، مسح شبكة LAN اختياري للتشغيل الذاتي
- ✅ **شاشة بيع ويب (POS)**: `/admin/pos` — المسح يفرد الصنف تلقائيًا (سعر + دفعة FEFO + صوت تأكيد)، الدفع وإتمام البيع بنفس منطق API الخادمي (H-01)، وقواعد الطباعة بعد الإتمام قابلة للتخصيص
- ✅ **قواعد طباعة حسب سير العمل**: عند إغلاق فاتورة شراء → اسأل «طباعة باركود؟» (افتراضيًا نعم) ويفتح شيت ملصقات بعدد المستلم الفعلي، وعند البيع → بلا سؤال باركود (افتراضيًا) والإيصال (تلقائي/اسأل/أبدًا) — كل شيء من إعدادات الأجهزة
- ✅ **API الأجهزة للـPOS/Flutter**: `POST /api/v1/devices/enroll` (توكن لمرة واحدة)، `GET /api/v1/devices/me` (الطابعات المعينة + إعدادات الماسح والطباعة)، heartbeat، وCRUD طابعات مع اختبار اتصال شبكي حي
- ✅ تقارير شاملة + تقارير لوحة قابلة للتصدير
- ✅ ميزانيات شراء بتنبيهات 80%/100%
- ✅ بطاقات أداء الموردين وتقييماتهم
- ✅ سجلات تدقيق شاملة (من/إلى/IP)
- ✅ بدائل دوائية ذكية + تحذيرات تعارض دوائي
- ✅ شريط حي «يومك في سطر» يتحدث كل 30 ثانية
- ✅ PWA + وضع داكن + POS بلوحة المفاتيح (F2/F8/F9)

### التكاملات (سقالات جاهزة — تُفعَّل بمفاتيح)
| التكامل | متغير التفعيل | الوظيفة |
|---|---|---|
| Paymob (مصر) | `PAYMOB_ENABLED=true` | دفع بطاقات/محافظ + تحقق HMAC للويب هوك |
| WhatsApp Cloud API | `WHATSAPP_ENABLED=true` | تأكيد بيع، تذكير آجل، استدعاء دفعة |
| الفاتورة الإلكترونية ETA | `ETA_ENABLED=true` | مُنشئ مستند eInvoice كامل |
| Supabase Storage | `UPLOADS_CLOUD_ENABLED=true` · `BACKUP_CLOUD_ENABLED=true` | رفع الملفات والنسخ الاحتياطي سحابيًا (fallback محلي تلقائي) |

## 👥 الأدوار والصلاحيات

### طبقة الدخول (عمود `role` في `app_users`)
| الدور | لوحة `/admin` | API | النطاق |
|---|---|---|---|
| `superadmin` | ✅ كامل المنصة، يتخطى عزل المستأجرين | ❌ | كل الأعمال |
| `admin` | ✅ لوحة عمله | ❌ | عمله فقط |
| `staff` | ❌ | ✅ | عمله فقط |
| `shop-owner` (legacy) | ❌ | ✅ | عمله فقط |

### طبقة القدرات (spatie Permissions — مربوطة تلقائيًا بعمود role)
| دور spatie | الصلاحيات (33) |
|---|---|
| **Super Admin** | الكل — يشمل `sale.price-override` (البيع بغير سعر القائمة) |
| **Admin** | المستخدمون (بلا حذف) · المنتجات (الكل) · مبيعات/مشتريات/مخزون (بلا حذف) · تقارير+تصدير · إعدادات · ولاء (بلا حذف) · إيصالات (عرض/إنشاء) · تدقيق (عرض) |
| **Staff** | قراءة فقط + `sales-create` (يبيع، لا يعدل/يحذف) |

الربط تلقائي: كل مستخدم جديد يُربط بدور spatie الموافق لعمود `role` (hook في `AppUser::created` + البذور). نقاط الإنفاذ: `AdminMiddleware` · Gate `view-dashboard` · `TenantAccessCheck` · `EnsureBusinessContext` · بوابات `can()` في الكنترولرات الحساسة.

## 🔢 حسابات التجربة (بعد `migrate:fresh --seed`)

| الحساب | كلمة المرور | الدور |
|---|---|---|
| `superadmin@z-syst.com` | `SuperAdmin123!` | مالك المنصة |
| `admin@test.com` | `Admin123!` | صاحب "Test Pharmacy #1" |
| `staff@test.com` | `Staff123!` | موظف الصيدلية 1 (API) |
| `staff-b@test.com` | `StaffB123!` | موظف "Pharmacy B" (عزل مستأجر) |

## 🚀 التشغيل

### المتطلبات
PHP 8.2+ · Composer · Node 18+ · (اختياري) مشروع Supabase

### خطوات
```bash
composer install && npm install && npm run build
cp .env.example .env && php artisan key:generate
# اضبط قاعدة البيانات (sqlite للتجربة أو Postgres/Supabase للإنتاج)
php artisan migrate --seed
npm run dev   # أو php artisan serve
```

### بيئة التشغيل الحية (php-wasm — للتطوير/المعاينة بدون PHP محلي)
```bash
cp -r tools_local/phpw-runner ../phpw && cd ../phpw && npm install
python3 ../Z-Syst/tools_local/vendor_install.py   # يبني vendor من composer.lock
mv ../Z-Syst/vendor ../zvendor
node runner.mjs artisan migrate:fresh --seed
node guardian.mjs    # يخدم على 0.0.0.0:8080
```

## 🧪 تجربة سريعة (بيع POS)
```bash
TOKEN=$(curl -s -X POST <HOST>/api/v1/sign-in -H "Content-Type: application/json" \
  -d '{"email":"staff@test.com","password":"Staff123!"}' | jq -r .data.token)
curl -X POST <HOST>/api/v1/sales -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" -d '{"saleDate":"2026-11-07","products":[{"product_id":1,"price":5.5,"lossProfit":2.3,"quantities":2,"batch_no":"BATCH-A1"}],"paidAmount":11,"dueAmount":0,"isPaid":true,"paymentType":"cash"}'
```

## 🗂️ الوثائق
- `docs/zsyst/` — 24 وثيقة: قرارات البنية، تدقيق RLS، تقارير الفحص الأمني (21/23)، خارطة المزايا (20)، جرد النواقص (24)
- `PROJECT_KB.md` · `PROJECT_RULES/` — قاعدة معرفة المشروع
- `tools_local/` — مثبّت vendor (بلا composer) + بيئة php-wasm + مولّد الواجهات

## 🔐 الأمان
- فحوص شاملة موثقة بتواريخها: `docs/zsyst/21` و`23` والإصلاحات في `22` و`24`
- npm audit: 0 · bandit: 0 · أسرار الشجرة: 0 · عزل مستأجر مفحوص لكل موديل
- قبل الإنتاج: دوّر مفاتيح Stripe القديمة، ونظّف تاريخ git، وفعّل gitleaks في CI (تفاصيل doc 22 §H-01)

## 📄 الرخصة
انظر [LICENSE](LICENSE).
