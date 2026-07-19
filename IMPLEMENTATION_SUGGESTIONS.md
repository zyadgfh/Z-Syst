# خطة تنفيذ اقتراحات التحسين

## 1. ForecastingService - خدمة التنبؤات المنفصلة

### المنطلقات:
- استخراج منطق التنبؤات من `PrescriptionController` إلى خدمة منفصلة
- توحيد حساب متوسط الطلب اليومي
- تحسين خوارزمية حساب الثقة والاتجاه
- دعم تصدير التنبؤات إلى ملف Excel

### الملفات المطلوبة:
- `app/Services/ForecastingService.php` (جديد)
- تحديث `PrescriptionController` لاستخدام الخدمة

---

## 2. PrescriptionService - خدمة الوصفات

### المنطلقات:
- فصل منطق الأعمال عن Controller
- إدارة العمليات المعقدة مثل التصدير والصرف
- دعم المنطق الخاص بالمخزون FEFO

### الملفات المطلوبة:
- `app/Services/PrescriptionService.php` (جديد)
- تحديث `PrescriptionController` لاستخدام الخدمة

---

## 3. نظام المراسلة الداخلي (Company Messaging)

### المنطلقات:
- نظام مراسلة بين الموظفين داخل الشركة
- أنواع الرسائل:
  - طلب تعبئة مخزون
  - تنبيه جاهزية وصفة
  - تنبيه مخزون منخفض
  - طلب مشتريات
- دعم التعليمات (replies) والإشارات (mentions)

### الملفات المطلوحة:
- `app/Models/Message.php` (جديد)
- `app/Services/MessagingService.php` (جديد)
- `app/Http/Controllers/API/V1/MessageController.php` (جديد)
- `app/Http/Resources/MessageResource.php` (جديد)
- `app/Http/Requests/StoreMessageRequest.php` (جديد)
- `app/Events/Messaging/*` (جديد)

---

## 4. نظام الإشعارات الموحد

### المنطلقات:
- إشعارات موحدة للمخزون، الوصفات، المشتريات
- دعم الإشعارات الحقيقية (real-time) عبر Reverb
- دعم قراءة/غير مقروءة

### الملفات المطلوبة:
- `app/Notifications/StockAlertNotification.php` (جديد)
- `app/Notifications/PrescriptionReadyNotification.php` (جديد)
- `app/Listeners/SendNotification*` (جديد)

---

## 5. Rate Limiting مخصص لكل خطة اشتراك

### المنطلقات:
- تطبيق حدود الطلبات حسب خطة الاشتراك
- Free Plan: 100 طلب/دقيقة
- Starter Plan: 1,000 طلب/دقيقة
- Professional Plan: 10,000 طلب/دقيقة

### الملفات المطلوبة:
- `app/Http/Middleware/SubscriptionRateLimit.php` (جديد)
- تحديث `app/Http/Kernel.php`