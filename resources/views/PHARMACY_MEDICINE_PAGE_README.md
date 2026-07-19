# نظام إدارة الأدوية - Pharmacy Medicine Management

## 📋 نظرة عامة
تم إنشاء وحدة متكاملة لإدارة الأدوية في الصيدلة تتضمن:

### 1. نموذج الدواء (MedicineController)
- يستخدم نموذج `Product` الموجود في النظام
- يدعم جميع حقول الصيدلة:
  - الاسم العلمي، التجاري، اسم المنتج
  - الشكل الصيدلي، القوة
  - الأسعار: الشراء، البيع، الجملة
  - إدارة المخزون: الحد الأدنى، مستوى إعادة الطلب
  - المواد محكمة التحكم (جدول 1-5)
  - وصفة طبية
  - إرشادات الاستخدام والآثار الجانبية

### 2. الصفحات المُنشأة

#### pharmacy-medicine-form.blade.php
- نموذج إدخال/تعديل دواء
- حقول مُنظمة حسب الفئات:
  - المعلومات الأساسية
  - التصنيف والشركة المصنعة
  - المعلومات الصيدلانية
  - المعرفات (SKU، الباركود)
  - الأسعار
  - إدارة المخزون
  - الحالة والتحكم

#### pharmacy-medicines.blade.php (مُحدث)
- عرض قائمة الأدوية
- أزرار الإجراءات: عرض، تعديل، حذف
- تنقل بين الصفحات (pagination)
- رابط التقارير

#### pharmacy-medicine-show.blade.php
- عرض تفاصيل الدواء
- عرض المخزون الحالي
- عرض معلومات إضافية

#### pharmacy-medicine-reports.blade.php
- إحصائيات عامة (إجمالي، نشط، منخفض المخزون، قريبة الانتهاء)
- تقارير الأدوية منخفضة المخزون
- تقارير الأدوية قريبة الانتهاء
- الأدوية حسب الفئات
- الأدوية حسب الشركات المصنعة
- مواد محكمة التحكم

### 3. المسارات (routes/web.php)
```
GET    /pharmacy/medicines              -> pharmacy.medicines.index  (القائمة)
GET    /pharmacy/medicines/create       -> pharmacy.medicines.create (نموذج إدخال)
POST   /pharmacy/medicines              -> pharmacy.medicines.store  (حفظ)
GET    /pharmacy/medicines/{id}         -> pharmacy.medicines.show   (التفاصيل)
GET    /pharmacy/medicines/{id}/edit    -> pharmacy.medicines.edit   (نموذج تعديل)
PUT    /pharmacy/medicines/{id}         -> pharmacy.medicines.update (تحديث)
DELETE /pharmacy/medicines/{id}        -> pharmacy.medicines.destroy (حذف)
GET    /pharmacy/medicines/reports     -> pharmacy.medicines.reports (التقارير)
GET    /pharmacy/medicines/search      -> pharmacy.medicines.search (API البحث)
```

### 4. نموذج Inventory (مُنشأ)
- نموذج مختصر يربط بـ `ProductStock`
- يدعم batch_number و expiry_date

## 🚀 طريقة الاستخدام
1. انتقل إلى: `http://localhost/pharmacy/medicines`
2. ستظهر لك قائمة الأدوية
3. انقر على "إدخال دواء جديد" لإضافة دواء
4. انقر على "التقارير" لعرض تقارير المخزون