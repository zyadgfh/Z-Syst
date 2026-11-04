# 🗃️ Valkey — بديل Redis في Z-Syst

> **الخلاصة:** Valkey هو الـ fork مفتوح المصدر (رخصة BSD-3) من Redis برعاية **Linux Foundation** ودعم AWS وGoogle وOracle. وهو **متوافق تمامًا مع بروتوكول Redis** — بديل مباشر (drop-in replacement). لذلك **لا يلزم أي تعديل في كود Laravel**: نُبقي `CACHE_DRIVER=redis` و`QUEUE_CONNECTION=redis` و`SESSION_DRIVER=redis` كما هي، ونستبدل الخادم فقط.

---

## 1. لماذا Valkey؟

| الميزة | التفاصيل |
|--------|----------|
| **الترخيص** | BSD-3 — مجاني بالكامل للأبد، بلا قيود تجارية، وتضمن Linux Foundation بقاءه مفتوحًا |
| **التوافق** | نفس بروتوكول Redis (RESP) ونفس الأوامر — عملاء `phpredis` و`predis` يعملان دون تعديل |
| **التطوير** | نشط جدًا — أحدث إصدار **9.1.3** (26 أكتوبر 2026)، وهو المحرك الافتراضي في AWS ElastiCache وGoogle Memorystore |
| **الأداء** | I/O متعدد الخيوط (`io-threads`)، وتوفير ذاكرة أفضل في 9.1 (حتى 20% للنصوص الصغيرة) |
| **المنفذ** | 6379 نفسه، ونفس صيغ RDB/AOF حتى Redis 7.2 |
| **Docker** | صورة رسمية متعددة المعماريات: `valkey/valkey` |

**نقطة مهمة:** اسم السائق `redis` في Laravel يشير إلى *البروتوكول* وليس إلى *الخادم*. لذلك كل شيء في Z-Syst (الكاش، الجلسات، الطوابير، البث، أقفال الكاش، rate limiting) يعمل مع Valkey دون أي تغيير برمجي.

---

## 2. ماذا أُضيف في هذا المستودع؟

| الملف | الغرض |
|-------|-------|
| `docker-compose.valkey.yml` | خدمة Valkey جاهزة للتشغيل عبر Docker (كلمة مرور إلزامية، تخزين دائم، فحص صحة) |
| `docker/valkey/valkey.conf` | إعداد Valkey مضبوط لـ Z-Syst (خيوط I/O، AOF لحماية الطوابير، سياسة ذاكرة آمنة) |
| `tools/valkey-compat-test.py` | سكربت تحقق يفحص توافق أي خادم Valkey/Redis مع عمليات Laravel الفعلية |
| هذا الدليل `docs/valkey-setup-ar.md` | التثبيت والترحيل والتحقق |

> ملفات `.env.example` و`.env.production` وأدلة النشر تحتفظ بمتغيرات `REDIS_*` كما هي — وهي نفسها التي يستخدمها Valkey.

---

## 3. التثبيت

### الطريقة الأولى: Docker (موصى بها)

```bash
# 1) ضع كلمة مرور حقيقية في .env (لا تتركها null)
#    REDIS_PASSWORD=your-strong-password

# 2) شغّل Valkey
docker compose -f docker-compose.valkey.yml up -d

# 3) تحقق
docker exec -it z-syst-valkey valkey-cli -a 'your-strong-password' ping
# المتوقع: PONG
```

### الطريقة الثانية: التثبيت المباشر على الخادم

- **Ubuntu/Debian:** تتوفر حزم `valkey-server` في مستودعات Debian 13/Ubuntu 25.04 وما بعدهما:
  ```bash
  sudo apt install valkey-server
  sudo systemctl enable --now valkey-server
  ```
- **أو البناء من المصدر** (أي توزيعة):
  ```bash
  sudo apt install build-essential pkg-config libssl-dev
  git clone https://github.com/valkey-io/valkey.git
  cd valkey && git checkout 9.1.3
  make -j$(nproc)
  sudo make install
  ```
- ملف الإعداد: `/etc/valkey/valkey.conf` — استرشد بـ `docker/valkey/valkey.conf` في هذا المستودع، واضبط `requirepass` و`appendonly yes`.

### الطريقة الثالثة: خدمة مُدارة (بدون تشغيل خادم)

- **Aiven:** خطة مجانية دائمة لـ Valkey (1 CPU / 1GB RAM).
- **Upstash:** متوافق مع Redis، خطة مجانية 500 ألف أمر/شهر.
- ضع بيانات الاتصال في `REDIS_HOST` / `REDIS_PORT` / `REDIS_PASSWORD` (ومع TLS استخدم `REDIS_SCHEME=tls` إن لزم).

---

## 4. إعداد `.env` (لا يتغير شيء)

```env
BROADCAST_DRIVER=redis
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_CLIENT=phpredis      # أو predis — كلاهما يعمل مع Valkey
REDIS_HOST=127.0.0.1       # عنوان خادم Valkey
REDIS_PASSWORD=your-strong-password
REDIS_PORT=6379
REDIS_DB=0
REDIS_CACHE_DB=1
```

ثم:

```bash
php artisan config:cache
```

> إذا كان امتداد `phpredis` غير مثبت على الخادم، اضبط `REDIS_CLIENT=predis` (مكتبة PHP موجودة أصلًا في composer).

---

## 5. الترحيل من Redis قائم

### الحالة الأسهل: لا توجد بيانات مهمة بعد
شغّل Valkey مباشرة — الكاش يُعاد بناؤه تلقائيًا، والجلسات تبدأ من جديد (يسجل المستخدمون الدخول مجددًا).

### إذا كانت هناك طوابير/بيانات تريد الحفاظ عليها

1. **أفرغ الطوابير أولًا:**
   ```bash
   php artisan queue:restart   # العمال ينهون مهامهم الحالية ثم يتوقفون
   # انتظر حتى تصبح الطوابير فارغة
   ```
2. **انقل البيانات:**
   - **من Redis 7.2 أو أقدم:** ملفات RDB متوافقة — أوقف Redis، انسخ `dump.rdb` إلى مجلد بيانات Valkey (`dir`)، ثم شغّل Valkey. (AOF بصيغة multi-part من Redis 7.2 متوافقة أيضًا لأن Valkey fork من 7.2.4.)
   - **بديل بلا توقف تقريبًا:** شغّل Valkey ثم نفّذ عليه `REPLICAOF <redis-host> 6379` (و`MASTERAUTH` إن لزم)، انتظر اكتمال المزامنة، ثم `REPLICAOF NO ONE` ووجّه التطبيق إلى Valkey.
   - **من Redis 7.4 / 8+:** صيغة RDB الأحدث قد لا يقرأها Valkey — الأسلم إفراغ الطوابير والبدء من جديد، أو نقل المفاتيح برمجيًا (SCAN + DUMP/RESTORE).
3. **حدّث `REDIS_HOST`/`REDIS_PASSWORD` في `.env` ثم:**
   ```bash
   php artisan config:cache
   sudo supervisorctl restart laravel-worker:*
   ```

---

## 6. التحقق بعد التشغيل

### تم التحقق عمليًا ✅ (2026-11-04)

بُني **Valkey 9.1.3** من المصدر وشُغّل بإعداد `docker/valkey/valkey.conf` نفسه، ثم اجتاز **36/36 فحصًا** تغطي كل ما يستخدمه Z-Syst:

- الكاش: `SET EX` / `SET NX` / `INCR` / `DECRBY` / `SCAN` + الحذف الجماعي
- الجلسات: كتابة/قراءة/TTL/إتلاف
- **الطوابير: نصوص Lua الحقيقية من Laravel 10.x** — `push` / `pop` / `size` / `release` / `migrateExpiredJobs` / `clear` (بما فيها زيادة المحاولات عبر cjson)
- الأقفال الذرية: `SET NX PX` + نص Lua الحقيقي لتحرير القفل (يرفض المالك الخاطئ)
- محدد المعدل (rate limiter)، البث (Pub/Sub)، المعاملات (MULTI/EXEC)، خطوط الأنابيب، الهاشات (نمط Horizon)، `EVALSHA`
- **الثبات:** البيانات نجت من إعادة تشغيل الخادم کاملة (AOF + RDB)

لإعادة الفحص على خادمك (محلي أو Aiven أو غيره):

```bash
pip install redis
VALKEY_PASSWORD='your-strong-password' python3 tools/valkey-compat-test.py
# عدّل VALKEY_HOST / VALKEY_PORT عند الحاجة
```

### فحوص يدوية سريعة

```bash
# 1) الخادم يستجيب
valkey-cli -h 127.0.0.1 -a 'your-strong-password' ping                 # → PONG
valkey-cli -h 127.0.0.1 -a 'your-strong-password' info server | grep valkey_version

# 2) Laravel يرى الخادم
php artisan tinker
>>> Cache::put('valkey-test', 'ok', 60);
>>> Cache::get('valkey-test');        // → "ok"
>>> Redis::connection()->ping();      // → PONG

# 3) الطوابير
php artisan queue:work redis --once
```

- **Supervisor لا يتغير:** الأمر `queue:work redis` صحيح كما هو (اسم الاتصال وليس اسم الخادم).
- **Horizon** (إن استُخدم لاحقًا) يعمل مع Valkey لأنه يعتمد على أوامر Redis القياسية.

---

## 7. ملاحظات تشغيلية

- **الأمان:** لا تعرّض المنفذ 6379 للإنترنت. ملف الـ compose يربطه بـ `127.0.0.1` فقط، وكلمة المرور إلزامية.
- **الذاكرة:** سياسة `volatile-lru` تُخرج مفاتيح الكاش/الجلسات (ذات TTL) فقط عند الامتلاء، ولا تُخرج مهام الطوابير (بلا TTL). لا تستخدم `allkeys-*` مع الطوابير.
- **الخيوط:** `io-threads 2` نقطة بداية جيدة على خادم 2-4 أنوية.
- **النسخ الاحتياطي:** انسخ `dump.rdb` من حجم `valkey-data` دوريًا (يمكن ضمّه إلى `backup:run`).
- **الترقية:** راقب إصدارات 9.x — الترقية بين إصدارات patch آمنة عبر تبديل وسم الصورة وإعادة التشغيل.

---

## 8. روابط

- الموقع الرسمي: https://valkey.io
- المستودع: https://github.com/valkey-io/valkey
- التوثيق: https://valkey.io/topics/
- صورة Docker: https://hub.docker.com/r/valkey/valkey
- دليل الترحيل من Redis: https://valkey.io/topics/migration/
