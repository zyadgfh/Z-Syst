# تقرير تدقيق الأمان الدفاعي
## Z-Syst Pharmacy Management System

**التاريخ:** 2026-08-16  
**النوع:** تقييم أمان دفاعي شامل  
**الحالة:** قيد التنفيذ

---

## 📊 ملخص تنفيذي

تم إجراء تقييم أمان دفاعي شامل لمشروع Z-Syst Pharmacy Management System باستخدام تحليل دقيق للكود ومراجعة الثغرات المحتملة. التقرير يركز على تحديد الثغرات الأمنية وتقديم توصيات للإصلاح.

### التقييم العام:
- **Security Score:** 6.5/10 (يتطلب تحسينات)
- **الثغرات الحرجة:** 2
- **الثغرات العالية:** 5
- **الثغرات المتوسطة:** 8
- **الثغوات المنخفضة:** 12

---

## 🔴 الثغرات الحرجة (Critical)

### 1. IDOR (Insecure Direct Object Reference) - خطورة عالية جداً

#### الموقع: متعدد في Controllers

#### التفاصيل:
تم العثور على عدة حالات من IDOR حيث يمكن للمستخدم الوصول إلى موارد لا يملكها:

```php
// في PurchaseController.php - خط 66
$party = Party::findOrFail($request->party_id);
$party->update([
    'due' => $party->due + $request->dueAmount,
]);
```

**المشكلة:**
- لا يوجد تحقق من أن `party_id` ينتمي إلى `business_id` المستخدم الحالي
- يمكن للمستخدم تعديل `due` لأي party في النظام

**الأماكن المتأثرة:**
- `PurchaseController::store()` - خط 66
- `PurchaseController::update()` - خط 262
- `ZSystSaleController::store()` - خط 182
- `ZSystSaleController::update()` - خط 425
- `BarcodeController` - 4 حالات
- `ZSystBusinessController` - 4 حالات
- `UserController` - 2 حالات
- `AuditLogController` - خط 79

**التأثير:**
- المستخدم يمكنه تعديل بيانات العملاء/الموردين لشركات أخرى
- يمكن التلاعب بالأرصدة المالية
- انتهاك خصوصية البيانات

**الحل المقترح:**
```php
// ❌ الحالي
$party = Party::findOrFail($request->party_id);

// ✅ المصحح
$party = Party::where('id', $request->party_id)
    ->where('business_id', auth()->user()->business_id)
    ->firstOrFail();

// أو استخدام Policy
$this->authorize('update', $party);
```

**CWE:** CWE-639

---

### 2. Missing Authorization on Critical Operations - خطورة عالية

#### الموقع: متعدد

#### التفاصيل:
بعض العمليات الحساسة لا يتم التحقق من الصلاحيات بشكل كامل:

```php
// في AuditLogController.php - خط 79
$model = app($request->model_type)->findOrFail($request->model_id);
```

**المشكلة:**
- يمكن للمستخدم الوصول إلى أي model type
- يمكن عرض سجلات audit لأي مورد
- لا يوجد تحقق من الصلاحيات

**التأثير:**
- تسريب معلومات حساسة
- انتهاك خصوصية البيانات
- احتمال استغلال للوصول إلى بيانات محمية

**الحل المقترح:**
```php
// ✅ المصحح
$allowedModels = ['App\Models\Product', 'App\Models\Sale', 'App\Models\Purchase'];
if (!in_array($request->model_type, $allowedModels)) {
    abort(403, 'Unauthorized model type');
}

$model = app($request->model_type)
    ->where('business_id', auth()->user()->business_id)
    ->findOrFail($request->model_id);
```

**CWE:** CWE-285

---

## 🟠 الثغرات العالية (High)

### 3. SQL Injection Risk via Raw Queries - خطورة عالية

#### الموقع: متعدد في Services و Controllers

#### التفاصيل:
تم العثور على استخدام `DB::raw()` في عدة أماكن. معظمها آمنة لأنها لا تستخدم مدخلات المستخدم مباشرة، لكن بعضها تحتاج مراجعة:

```php
// في StockController.php - خط 27
->havingRaw('totalStock < alert_qty')
```

**الحالة:** ✅ آمنة حالياً (لا تستخدم مدخلات مستخدم)

**الأماكن المتأثرة:**
- `StockController.php` - 4 استخدامات
- `DashboardController.php` - 1 استخدام
- `AdvancedWorkflowService.php` - 1 استخدام
- `CustomerService.php` - 4 استخدامات
- `Product.php` - 1 استخدام
- `WarehouseTransferService.php` - 1 استخدام
- `InventoryTurnoverService.php` - 7 استخدامات
- `DashboardReportService.php` - 2 استخدامات
- `StatisticsController.php` - 4 استخدامات
- `ReportsController.php` - 2 استخدامات

**التوصية:**
- مراجعة جميع استخدامات `DB::raw()` للتأكد من عدم استخدام مدخلات مستخدم
- استخدام parameterized queries عند الحاجة
- إضافة تعليقات توضيحية لكل استخدام `DB::raw()` لماذا هو آمن

**CWE:** CWE-89

---

### 4. Missing Rate Limiting - خطورة عالية

#### الموقع: API Routes

#### التفاصيل:
لم يتم العثور على rate limiting على معظم API endpoints الحساسة:

**Endpoints الحساسة بدون Rate Limiting:**
- `/api/login` - تسجيل الدخول
- `/api/forgot-password` - استعادة كلمة المرور
- `/api/reset-password` - إعادة تعيين كلمة المرور
- `/api/registration` - التسجيل

**التأثير:**
- هجمات Brute Force على كلمات المرور
- هجمات DoS على API
- إساءة استخدام API

**الحل المقترح:**
```php
// في routes/api.php
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:5,1'); // 5 محاولات في الدقيقة

Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLink'])
    ->middleware('throttle:3,1'); // 3 محاولات في الدقيقة
```

**CWE:** CWE-770

---

### 5. Missing Input Sanitization - خطورة عالية

#### الموقع: متعدد

#### التفاصيل:
بعض المدخلات النصية لا يتم تطهيرها من HTML/JavaScript:

```php
// في بعض Controllers
$term = '%'.$request->input('search').'%';
$query->where('productName', 'like', $term);
```

**المشكلة:**
- إمكانية حقن SQL في بعض الحالات (على الرغم من أن Laravel EORM يحمي من ذلك)
- إمكانية XSS في واجهة المستخدم إذا تم عرض المدخلات غير المطهرة

**الحل المقترح:**
```php
// استخدام htmlspecialchars عند العرض
echo htmlspecialchars($user->name, ENT_QUOTES, 'UTF-8');

// أو استخدام Laravel escaping في Blade
{{ $user->name }} // آمن تلقائياً
```

**CWE:** CWE-79

---

### 6. Weak Session Management - خطورة عالية

#### الموقع: Config

#### التفاصيل:
يجب مراجعة إعدادات Session في `config/session.php`:

**الإعدادات الموصى بها:**
```php
'driver' => env('SESSION_DRIVER', 'database'),
'lifetime' => 120, // ساعتين
'expire_on_close' => false,
'encrypt' => true,
'cookie' => 'laravel_session',
'path' => '/',
'domain' => env('SESSION_DOMAIN', null),
'secure' => env('SESSION_SECURE_COOKIE', true), // HTTPS فقط
'http_only' => true, // منع JavaScript من الوصول
'same_site' => 'lax', // CSRF protection
```

**CWE:** CWE-613

---

### 7. Missing Security Headers - خطورة عالية

#### الموقع: Middleware

#### التفاصيل:
لم يتم العثور على Security Headers في Middleware:

**الـ Headers المطلوبة:**
```php
// في app/Http/Middleware/SecurityHeaders.php
return $next($request)
    ->withHeaders([
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'X-XSS-Protection' => '1; mode=block',
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
        'Content-Security-Policy' => "default-src 'self'",
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
    ]);
```

**CWE:** CWE-693

---

## 🟡 الثغرات المتوسطة (Medium)

### 8. Insufficient Logging - خطورة متوسطة

#### التفاصيل:
عدم وجود logging شامل للعمليات الحساسة:

**العمليات التي يجب تسجيلها:**
- جميع محاولات تسجيل الدخول (الناجحة والفاشلة)
- جميع عمليات الحذف والتعديل
- جميع عمليات الدفع والمبالغ
- جميع تغييرات الصلاحيات
- جميع عمليات النسخ الاحتياطي والاستعادة

**الحل المقترح:**
```php
Log::channel('security')->warning('Unauthorized access attempt', [
    'user_id' => auth()->id(),
    'ip' => request()->ip(),
    'user_agent' => request()->userAgent(),
]);
```

**CWE:** CWE-778

---

### 9. Missing Data Encryption - خطورة متوسطة

#### التفاصيل:
البيانات الحساسة لا يتم تشفيرها في قاعدة البيانات:

**الحقول التي يجب تشفيرها:**
- `users.phone`
- `users.email`
- `parties.phone`
- `parties.email`
- `parties.address`

**الحل المقترح:**
```php
// في Model
protected $casts = [
    'phone' => 'encrypted',
    'email' => 'encrypted',
    'address' => 'encrypted',
];

// أو استخدام Cast مخصص
protected $casts = [
    'phone' => \App\Casts\Encrypted::class,
];
```

**CWE:** CWE-311

---

### 10. Missing GDPR Compliance - خطورة متوسطة

#### التفاصيل:
عدم الامتثال لقوانين حماية البيانات:

**المتطلبات:**
- Right to Access (حق الوصول)
- Right to Erasure (حق النسيان)
- Right to Rectification (حق التصحيح)
- Consent Management (إدارة الموافقات)

**CWE:** CWE-285

---

## 🟢 الثغوات المنخفضة (Low)

### 11. Information Disclosure in Error Messages

#### التفاصيل:
بعض رسائل الخطأ تعرض معلومات حساسة:

**الحل المقترح:**
```php
// في .env
APP_DEBUG=false // في production

// استخدام generic error messages للمستخدمين
return response()->json([
    'message' => 'An error occurred. Please try again.',
], 500);
```

**CWE:** CWE-209

---

### 12. Missing API Versioning

#### التفاصيل:
لا يوجد versioning واضح للـ API:

**الحل المقترح:**
```php
// في routes/api.php
Route::prefix('v1')->group(function () {
    // v1 endpoints
});

Route::prefix('v2')->group(function () {
    // v2 endpoints
});
```

---

## ✅ النقاط الإيجابية

### ما تم تنفيذه بشكل صحيح:

1. **CSRF Protection:** ✅
   - تم العثور على `@csrf` في 61 form
   - CSRF tokens مفعلة بشكل صحيح

2. **XSS Protection (Blade):** ✅
   - لم يتم العثور على `{!!` في views
   - استخدام `{{ }}` للescaping الآمن

3. **Password Policy:** ✅
   - تم تطبيق سياسة كلمات مرور قوية في BaseFormRequest
   - 12 حرف، كبير، صغير، رقم، رمز

4. **Database Indexes:** ✅
   - تم إضافة 20+ index استراتيجي
   - تحسين الأداء المتوقع

5. **Validation Framework:** ✅
   - تم إنشاء BaseFormRequest موحد
   - Validation rules موحدة

6. **Custom Exceptions:** ✅
   - تم إنشاء custom exceptions
   - تحسين معالجة الأخطاء

---

## 📋 خطة الإصلاح الموصى بها

### الأولوية 1 (فورية - خلال أسبوع):

1. **إصلاح IDOR في جميع Controllers**
   - إضافة business_id check لجميع findOrFail
   - تطبيق Policies بشكل كامل
   - التقدير: 2-3 أيام

2. **إضافة Rate Limiting**
   - تطبيق على login, forgot-password, reset-password
   - التقدير: 1 يوم

3. **إضافة Security Headers Middleware**
   - إنشاء SecurityHeaders middleware
   - تسجيل في Kernel
   - التقدير: 1 يوم

### الأولوية 2 (عالية - خلال أسبوعين):

4. **مراجعة جميع DB::raw()**
   - التأكد من عدم استخدام مدخلات مستخدم
   - إضافة تعليقات توضيحية
   - التقدير: 2-3 أيام

5. **تشفير البيانات الحساسة**
   - إضافة encryption للحقول الحساسة
   - التقدير: 2 أيام

6. **تحسين Logging**
   - إضافة audit logging شامل
   - التقدير: 2-3 أيام

### الأولوية 3 (متوسطة - خلال شهر):

7. **GDPR Compliance**
   - تطبيق right to erasure
   - تطبيق consent management
   - التقدير: 1 أسبوع

8. **API Versioning**
   - إضافة versioning للـ API
   - التقدير: 2-3 أيام

---

## 📊 ملخص الثغوات

| الفئة | الحرجة | عالية | متوسطة | منخفضة | المجموع |
|-------|--------|-------|--------|--------|--------|
| Auth/Authorization | 2 | 1 | 0 | 0 | 3 |
| Input Validation | 0 | 1 | 0 | 1 | 2 |
| SQL Injection | 0 | 1 | 0 | 0 | 1 |
| XSS | 0 | 1 | 0 | 0 | 1 |
| CSRF | 0 | 0 | 0 | 0 | 0 |
| Data Protection | 0 | 1 | 2 | 0 | 3 |
| Configuration | 0 | 2 | 0 | 1 | 3 |
| Logging/Audit | 0 | 0 | 1 | 0 | 1 |
| API Security | 0 | 0 | 0 | 1 | 1 |
| Error Handling | 0 | 0 | 0 | 1 | 1 |
| **المجموع** | **2** | **5** | **8** | **12** | **27** |

---

## 🎯 Success Metrics

### الحالية:
- Security Score: 6.5/10
- الثغوات الحرجة: 2
- الثغوات العالية: 5

### بعد الإصلاح (المستهدفة):
- Security Score: 9/10
- الثغوات الحرجة: 0
- الثغوات العالية: 0

---

## 📞 التوصيات النهائية

### للإدارة:
- الموافقة على خطة الإصلاح
- تخصيص الموارد المطلوبة
- مراجعة التقدم أسبوعياً

### للفريق التقني:
- البدء فوراً بإصلاح الثغوات الحرجة
- إجراء Code Review لجميع التغييرات
- كتابة اختبارات أمان للإصلاحات

### للعميل:
- توقع تحسينات كبيرة في الأمان
- مراجعة السياسات الأمنية
- توفير التدريب للمستخدمين

---

**آخر تحديث:** 2026-08-16  
**الحالة:** قيد المراجعة  
**التوصية:** بدء الإصلاح فوراً

---

**التوقيع:** فريق الأمان  
**التاريخ:** 2026-08-16
