# دليل تشغيل الموقع السريع (Quick Start)

## طريقة 1: تشغيل سريع (بدون قاعدة بيانات)

افتح Terminal (cmd) ونفّذ:

```bash
cd c:\laragon\www
php artisan serve
```

سيفتح الموقع على: http://127.0.0.1:8000

---

## طريقة 2: تشغيل كامل (مع قاعدة البيانات)

### خطوة 1: إعداد قاعدة البيانات
1. افتح Laragon
2. تأكّد من تشغيل MySQL
3. أنشئ قاعدة بيانات باسم: `pharmacy`

### خطوة 2: تعديل ملف .env
عدل السطرين التاليين في ملف `.env`:
```
DB_DATABASE=pharmacy
DB_USERNAME=root
DB_PASSWORD=
```

### خطوة 3: تنفيذ الأوامر
```bash
cd c:\laragon\www
php artisan migrate
php artisan storage:link
php artisan serve
```

---

## طريقة 3: باستخدام Laragon مباشرة
إذا كان المشروع داخل مجلد Laragon (www)، يمكنك:
1. اضغط على **Start All** في Laragon
2. افتح المتصفح على: http://pharmacy.test (أو اسم المجلد)