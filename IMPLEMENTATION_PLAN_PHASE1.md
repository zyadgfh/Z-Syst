# خطة التنفيذ المرحلية - Phase 1

## الهدف

تنفيذ الوحدة الأساسية الأولى من النظام: إدارة المنتجات والأدوية مع الالتزام بالمعايير التالية:

- Tenant isolation صارم
- Service Layer منفصل
- Form Requests و DTOs
- اختبارات شاملة
- API جاهز للاستخدام

---

## المرحلة 1: الإعداد والتخطيط

### الأهداف
- مراجعة النماذج الحالية مثل Company و Branch و User
- التأكد من أن trait HasCompany متاح ومستخدم بشكل صحيح
- التأكد من أن middleware tenant يعمل على API routes

### الأعمال
1. مراجعة ملفات النماذج والـ traits الحالية
2. التأكد من أن جميع الجداول الجديدة ستستخدم company_id
3. التأكد من أن API routes تستخدم middleware tenant

---

## المرحلة 2: بناء قاعدة البيانات

### الجداول المطلوبة
1. categories
2. manufacturers
3. products

### الأعمال
1. إنشاء migrations للجدول الأول
2. إنشاء migrations للجدول الثاني
3. إنشاء migrations للجدول الثالث
4. تشغيل migrations محليًا
5. التحقق من صحة العلاقات الخارجية والـ indexes

---

## المرحلة 3: بناء النماذج

### النماذج المطلوبة
1. Category
2. Manufacturer
3. Product

### الأعمال
1. إضافة trait HasCompany لكل نموذج
2. تعريف العلاقات بين النماذج
3. إضافة fillable و casts المناسبة
4. إضافة scopes أو helper methods عند الحاجة

---

## المرحلة 4: بناء طبقة الخدمات

### الخدمات المطلوبة
1. ProductService
2. ProductSearchService

### الأعمال
1. إنشاء DTOs لعمليات الإنشاء والتحديث والبحث
2. إنشاء service methods التالية:
   - create()
   - update()
   - delete()
   - search()
3. إضافة التحقق من تكرار الباركود داخل الشركة
4. إضافة أحداث عند إنشاء أو تحديث المنتج

---

## المرحلة 5: بناء الـ Requests والـ Validation

### الـ Requests المطلوبة
1. StoreProductRequest
2. UpdateProductRequest
3. SearchProductRequest

### الأعمال
1. تعريف القواعد الخاصة بالـ validation
2. التأكد من صحة العلاقات مثل category_id و manufacturer_id
3. التأكد من صحة selling_price و cost_price

---

## المرحلة 6: بناء Controllers و Routes

### Controllers المطلوبة
1. ProductController
2. CategoryController
3. ManufacturerController

### الأعمال
1. إنشاء controller مع CRUD كامل
2. إضافة endpoints التالية:
   - GET /api/v1/products
   - POST /api/v1/products
   - GET /api/v1/products/{id}
   - PUT /api/v1/products/{id}
   - DELETE /api/v1/products/{id}
   - GET /api/v1/products/search
3. إضافة routes للفئات والمصنعين

---

## المرحلة 7: بناء السياسات والأذونات

### الملفات المطلوبة
1. ProductPolicy
2. CategoryPolicy
3. ManufacturerPolicy

### الأعمال
1. تعريف صلاحيات CRUD
2. ربط السياسات بالـ routes أو controllers
3. التأكد من أن المستخدم لا يستطيع الوصول لبيانات شركة أخرى

---

## المرحلة 8: الاختبارات

### أنواع الاختبارات
1. Unit tests للخدمات
2. Feature tests للـ APIs

### حالات الاختبار الأساسية
- إنشاء منتج بنجاح
- رفض المنتج المكرر بالباركود
- البحث عن منتج داخل الشركة الحالية
- منع الوصول لمنتجات شركة أخرى
- تحديث المنتج بنجاح

---

## المرحلة 9: التوثيق والـ QA

### الأعمال
1. مراجعة API responses
2. التأكد من أن الأخطاء موحدة
3. التحقق من صحة الـ status codes
4. إضافة documentation مبدئية في README أو API docs

---

## الترتيب العملي المقترح

1. المراجع الأساسية للنماذج والـ tenant
2. المigrations
3. النماذج
4. DTOs و Requests
5. Services
6. Controllers و Routes
7. Policies
8. Tests
9. Review و QA

---

## أولى الخطوات الفعلية

### اليوم 1
- مراجعة tenant infrastructure
- إنشاء migrations

### اليوم 2
- إنشاء models
- إنشاء DTOs و Requests

### اليوم 3
- إنشاء services و controllers

### اليوم 4
- كتابة tests
- تشغيلها وتصحيح الأخطاء

---

## ملاحظات مهمة

- لا تبدأ بالوحدات التالية قبل اكتمال هذه المرحلة
- لا تكتب منطق العمل داخل controller
- كل عملية حرجة يجب أن تكون داخل transaction
- استخدم soft deletes بدلاً من الحذف الفعلي
- حافظ على العزل بين الشركات في كل استعلام
