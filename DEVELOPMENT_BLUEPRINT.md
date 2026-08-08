# DEVELOPMENT BLUEPRINT

## الهدف

هذا الملف يمثل خطة تطوير عملية ومركزة للمرحلة القادمة من مشروع Z-Syst. يعتمد على نتائج تحليل الفجوات الحالية ويهدف إلى تحويلها إلى خطة تنفيذ واضحة يمكن العمل عليها مباشرة.

## 1. أولويات التطوير

### A. المرحلة الأولى: إكمال الأساس العملي

1. إكمال Landing Module كـ API حقيقي ومفيد
2. تحسين حماية الوصول للنسخ الاحتياطي والتقارير والإدارة
3. إضافة اختبارات أساسية لعمليات الصلاحيات وعمليات الأعمال الحساسة
4. مراجعة إعدادات الإنتاج والتخزين والـ queue/cache

### B. المرحلة الثانية: إكمال الوحدات المتقدمة

1. إكمال Insurance Module بالكامل
2. إضافة Multi-Warehouse و Warehouse Transfers
3. تنفيذ Drug Recall و Traceability

### C. المرحلة الثالثة: تحسين تجربة التشغيل

1. إضافة Loyalty و CRM
2. دعم الطباعة والإيصالات
3. تحسين الاختبارات و CI/CD والـ deployment readiness

## 2. المتطلبات الفنية الواجب تنفيذها

### 2.1 Landing Module

المطلوب:
- استبدال المسار الحالي placeholder بـ API حقيقي
- إرجاع بيانات مناسبة مثل:
  - sections
  - features
  - pricing
  - testimonials
  - contact info
- دعم التهيئة عبر قاعدة البيانات أو ملف config

الملفات المستهدفة:
- [Modules/Landing/routes/api.php](Modules/Landing/routes/api.php)
- Modules/Landing/App/Http/Controllers/Api/LandingController

### 2.2 Insurance Module

المطلوب:
- نموذج Companies
- نموذج Policies
- نموذج Claims
- نموذج Coverages
- خدمات Business Logic منفصلة
- Endpoints API كاملة
- صلاحيات مناسبة لكل دور

الملفات/المجالات المقترحة:
- app/Models/InsuranceCompany.php
- app/Models/InsurancePolicy.php
- app/Models/InsuranceClaim.php
- app/Models/InsuranceCoverage.php
- app/Services/InsuranceService.php
- app/Http/Controllers/Api/InsuranceController.php

### 2.3 Multi-Warehouse

المطلوب:
- جدول warehouses
- جدول warehouse_stocks
- جدول stock_transfers
- ربط المخزون الحالي بالـ warehouse
- تعديل منطق Stock و Product لاستخدام warehouse-aware logic

الملفات المقترحة:
- app/Models/Warehouse.php
- app/Models/WarehouseStock.php
- app/Models/StockTransfer.php
- app/Services/WarehouseStockService.php

### 2.4 Drug Recall و Traceability

المطلوب:
- تتبع batch/lot numbers
- سجل recall events
- تتبع نقل المنتجات عبر السلسلة
- APIs للاستعلام عن traceability

الملفات المقترحة:
- app/Models/BatchLot.php
- app/Models/RecallEvent.php
- app/Services/TraceabilityService.php

### 2.5 Loyalty و CRM

المطلوب:
- نقاط ولاء
- مكافآت أو كوبونات
- سجل تعاملات العملاء
- تقارير بسيطة للـ CRM

الملفات المقترحة:
- app/Models/LoyaltyProgram.php
- app/Models/LoyaltyTransaction.php
- app/Services/LoyaltyService.php

### 2.6 Receipt و Printing

المطلوب:
- قالب طباعة للإيصالات
- endpoint أو خدمة توليد PDF/HTML
- ربط مع sales و payments

الملفات المقترحة:
- app/Services/ReceiptService.php
- resources/views/receipts/*.blade.php

## 3. بنية التنفيذ المقترحة

### 3.1 Pattern المعماري

استخدم نفس النمط الحالي في المشروع:
- Controllers: للتعامل مع HTTP فقط
- Services: للتعامل مع منطق الأعمال
- Models: للتعامل مع البيانات
- Requests: للتعامل مع validation
- Resources: لتنسيق الإخراج

### 3.2 التوافق مع Laravel

- استخدام Form Requests للتحقق
- استخدام Service Layer للمنطق المعقد
- استخدام Policy أو Middleware للسياسات
- استخدام API Resources للتنسيق
- استخدام Database Migrations لتنظيم التغييرات

## 4. خطة العمل اليومية

### يوم 1
- مراجعة وتحضير الجداول والـ migrations المطلوبة
- إنشاء هيكل Modules و Models الأساسية

### يوم 2
- تنفيذ Landing Module API
- إنشاء الخدمات الأساسية للـ Insurance

### يوم 3
- تنفيذ Insurance endpoints و validation
- إضافة اختبارات أولية

### يوم 4
- تنفيذ Multi-Warehouse basics
- إعداد الجداول والـ services

### يوم 5
- تنفيذ Drug Recall / Traceability basics
- إضافة اختبارات إضافية

### يوم 6
- إضافة Loyalty/CRM و Receipt printing
- مراجعة التوافق مع الواجهة الحالية

### يوم 7
- مراجعة الأمان، الأداء، والتشغيل
- إعداد deployment checklist

## 5. متطلبات الاختبارات

يجب أن تشمل الاختبارات على الأقل:
- Feature tests للـ Landing API
- Feature tests للـ Insurance workflows
- Feature tests للـ Warehouse transfers
- Feature tests لعمليات Recall و Traceability
- Tests لعمليات الصلاحيات والحماية

## 6. متطلبات الأمان

- التحقق من صلاحيات كل endpoint
- منع الوصول غير المصرح بين الأعمال المختلفة
- استخدام Validation و Authorization بشكل واضح
- حماية القيم الحساسة وتجنب التعديل المباشر للـ env في الإنتاج

## 7. قائمة التحقق النهائية

قبل اعتبار المرحلة جاهزة، يجب التحقق من:

- [ ] Landing API يعمل فعليًا
- [ ] Insurance flow مكتمل
- [ ] Multi-Warehouse يعمل
- [ ] Traceability و Recall متاحان
- [ ] Loyalty/CRM يعمل بشكل أساسي
- [ ] الإيصالات قابلة للطباعة
- [ ] الاختبارات تغطي العمليات الحساسة
- [ ] المشروع جاهز للإنتاج بشكل معقول

## 8. الخلاصة

المشروع يمتلك أساسًا جيدًا، والآن تحتاج المرحلة القادمة إلى التركيز على إكمال الوحدات المتقدمة، وليس على بناء البنية الأساسية من الصفر. التوجه الأفضل هو تنفيذ هذه الوحدات بشكل تدريجي مع الحفاظ على جودة الكود، الاختبارات، والأمان.
