# TODO - PharmaMaster Pro (Phase 1)

## Step 1: Recon (قبل أي تعديل)
- [x] فحص migrations الحالية داخل `database/migrations/`
- [x] فحص `routes/api.php` ووجود/عدم وجود `routes/api/v1/*`
- [x] فحص Controllers Auth الحالية ومساراتها
- [x] فحص Exception Handler المركزي وformat الـ responses الحالية
- [x] فحص Models (User/Company/Branch/Department/Medicine/Inventory إن وجدت) للتأكد من وجود `company_id` والعلاقات

## Step 2: Multi-Tenant Trait
- [ ] إنشاء/تحديث `app/Support/Traits/BelongsToCompany.php` حسب المواصفات (ملاحظة: المشروع يستخدم حاليًا `app/Core/Traits/HasCompany` + `app/Scopes/TenantScope`)
- [ ] تطبيق الـ Trait على النماذج المعنية في Phase 1 (على الأقل User + أي models فيها company_id)

## Step 3: API Versioning
- [x] إنشاء/تنظيم `routes/api/v1/` ووضع auth routes داخل `routes/api/v1/auth.php` (ملاحظة: المشروع يوفّر غالبًا prefix v1 داخل `routes/api.php`)
- [x] تحديث `routes/api.php` ليصبح router رئيسي مع prefix `v1`
- [x] التأكد من تفعيل `auth:sanctum` و/أو middleware `tenant` إن كان موجود

## Step 4: Exception Handler موحد
- [ ] إنشاء `app/Exceptions/BaseApiException.php`
- [ ] إنشاء exceptions نوعية أساسية مستخدمة في Phase 1
- [ ] ربط/تعديل `app/Exceptions/Handler.php` أو mekanism موجود لإرجاع JSON موحد
- [ ] تحديث Auth controllers لاستخدام Exceptions الجديدة

## Step 5: المigrations الأساسية (CREATE فقط)
- [x] إنشاء/تأكيد migrations `companies`, `branches`, `departments`, `users` (ملاحظة: موجودة)
- [x] إضافة `company_id` وقيود/فهرسة داخل migrations المناسبة

## Step 6: Tests
- [x] إنشاء/تعديل `tests/Feature/Auth/LoginTest.php` (ملاحظة: يوجد `tests/Feature/AuthLoginTest.php` يغطي login/logout)
- [x] إضافة tests للتحقق من auth behavior وglobal scope multi-tenant isolation
- [ ] ضمان أن coverage للمرحلة 1 >= 80%

## Step 7: Verification
- [ ] تشغيل `php artisan migrate:fresh --seed`
- [ ] تشغيل `php artisan test`
- [ ] التأكد من عدم كسر RBAC/Branch Limits


