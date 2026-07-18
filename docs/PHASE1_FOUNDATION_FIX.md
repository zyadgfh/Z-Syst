# مرحلة 1: إصلاح الأساس - Foundation Fixes

## السوق المستهدف
مصر (EDA - هيئة الدواء المصرية)

## البوابات المفضلة
- Fawry (البوابة المحلية لمصر)
- Paymob (بديل)

## الخطوات المطلوبة

### 1. إصلاح الهجرات المتعارضة
- حذف الهجرات المكررة (2026_07_06_* إذا كانت مكررة)
- دمج الجداول المتعارضة
- إضافة العموامل الناقصة

### 2. إنشاء migration للـ two_factor fields
```php
Schema::table('users', function (Blueprint $table) {
    $table->timestamp('two_factor_confirmed_at')->nullable();
    $table->string('two_factor_secret')->nullable();
    $table->text('two_factor_recovery_codes')->nullable();
});
```

### 3. التحقق من عمود slug
- التأكد من وجود slug في جدول companies

### 4. Rate Limiting حسب الخطة
- Free: 100 طلب/دقيقة
- Starter: 1000 طلب/دقيقة
- Professional: 10000 طلب/دقيقة
- Enterprise: غير محدود

### 5. Health Check endpoint
- `/health` - فحص عام
- `/ready` - فحص قاعدة البيانات والـ Redis
- `/live` - فحص تشغيل التطبيق