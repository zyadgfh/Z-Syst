# Z-Syst Pharmacy Management System - Project Overview

## 📋 معلومات المشروع

**اسم المشروع:** Z-Syst Pharmacy Management System  
**النوع:** SaaS Multi-Tenant Pharmacy Management Platform  
**الإطار:** Laravel 12  
**تاريخ البدء:** 2026  
**الحالة:** Production Ready

---

## 🎯 الهدف العام

تطوير منصة إدارة صيدليات متكاملة متعددة المستأجرين (Multi-Tenant SaaS) تدعم:
- إدارة المخزون والمبيعات والمشتريات
- إدارة الاشتراكات والخطط
- نظام تأمين متقدم
- نظام ولاء وإدارة علاقات العملاء
- نظام تتبع واسترجاع الأدوية
- نظام طباعة وإيصالات
- تقارير Dashboard شاملة
- نظام أمان ومراجعة متقدم

---

## 🏗️ البنية المعمارية

### نموذج التعدد (Tenancy Model)
- **النوع:** Shared Multi-Tenant
- **العزل:** Database-level isolation via `business_id`
- **الحماية:** Global Scopes + Middleware
- **الدعم:** TenantResolver + TenantContextMiddleware

### الطبقات المعمارية
```
┌─────────────────────────────────────┐
│         Presentation Layer          │
│  (Controllers, Views, API Endpoints) │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│         Service Layer               │
│    (Business Logic, Validation)     │
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│         Model Layer                 │
│  (Eloquent Models, Scopes, Relations)│
└─────────────────────────────────────┘
              ↓
┌─────────────────────────────────────┐
│         Database Layer              │
│   (MySQL, Migrations, Seeders)      │
└─────────────────────────────────────┘
```

---

## 📦 الوحدات الأساسية

### 1. إدارة الأعمال (Business Management)
- إنشاء وإدارة الصيدليات
- إدارة الاشتراكات والخطط
- إدارة الفئات
- الترقية والتجديد

### 2. إدارة المخزون (Inventory Management)
- إدارة المنتجات
- إدارة الأصناف
- نظام FEFO (First Expired First Out)
- إدارة المستودعات المتعددة
- انتقالات المخزون

### 3. إدارة المبيعات (Sales Management)
- نقاط البيع (POS)
- الفواتير والإيصالات
- إدارة العملاء
- عروض الأسعار
- المرتجعات

### 4. إدارة المشتريات (Purchases Management)
- أوامر الشراء
- إدارة الموردين
- الاستلام والفحص
- المرتجعات

### 5. نظام التأمين (Insurance Module)
- شركات التأمين
- وثائق التأمين
- المطالبات
- التغطيات

### 6. نظام الولاء (Loyalty & CRM)
- برامج الولاء
- النقاط والمكافآت
- تفاعلات العملاء
- إدارة العلاقات

### 7. التتبع والاسترجاع (Traceability & Recall)
- إدارة الدفعات والأرقام التسلسلية
- أحداث الاسترجاع
- سجلات التتبع
- تتبع كامل للمنتجات

### 8. الطباعة والإيصالات (Receipt Printing)
- إعدادات الطباعة
- قوالب الإيصالات
- دعم PDF/HTML/Thermal
- تخصيص Header/Footer

### 9. التقارير (Reports & Analytics)
- تقارير Dashboard
- تقارير المبيعات والمشتريات
- تقارير المخزون
- تقارير الأرباح والخسائر
- تقارير الاشتراكات

### 10. إدارة المستخدمين (User Management)
- إدارة المستخدمين
- إدارة الأدوار والصلاحيات
- النسخ الاحتياطي
- المراجعة (Audit Logs)

---

## 🔐 نموذج الأمان

### العزل بين المستأجرين
- Global Scopes على جميع النماذج
- TenantAccessCheck Middleware
- التحقق من `business_id` في جميع العمليات

### الصلاحيات
- نظام Spatie Permission
- أدوار: Super Admin, Admin, Staff, Shop Owner
- صلاحيات دقيقة لكل وحدة

### المراجعة (Audit Logging)
- تسجيل جميع العمليات المهمة
- تسجيل CRUD operations
- تسجيل Login/Logout
- تسجيل Export/Import

---

## 🗄️ قاعدة البيانات

### الجداول الرئيسية
- `businesses` - الصيدليات
- `users` - المستخدمين
- `products` - المنتجات
- `categories` - الأصناف
- `parties` - العملاء والموردين
- `sales` - المبيعات
- `purchases` - المشتريات
- `warehouses` - المستودعات
- `insurance_companies` - شركات التأمين
- `loyalty_programs` - برامج الولاء
- `audit_logs` - سجلات المراجعة

---

## 🚀 التقنيات المستخدمة

### Backend
- Laravel 12
- PHP 8.2+
- MySQL 8.0+
- Redis
- Queue (Redis)

### Frontend
- Vue.js 3
- Tailwind CSS
- Vite

### Services
- AWS S3 (Storage)
- SendGrid/Mailgun (Email)
- Firebase (Notifications)
- Sentry (Error Tracking)

---

## 📊 مقاييس النجاح

### مقاييس SaaS
- **DAU/WAU:** Daily/Weekly Active Users
- **Trial Conversion:** تحويل التجربة إلى اشتراك
- **Churn Rate:** معدل إلغاء الاشتراكات
- **ARR/MRR:** Annual/Monthly Recurring Revenue

### مقاييس الأداء
- **Response Time:** < 200ms
- **Uptime:** 99.9%
- **Database Queries:** < 50 per request
- **Memory Usage:** < 256MB per request

---

## 🔄 دورة التطوير

### المراحل المكتملة
1. ✅ تحليل المتطلبات
2. ✅ تحسين نموذج التعدد
3. ✅ إكمال Landing Module
4. ✅ إكمال Insurance Module
5. ✅ إضافة Multi-Warehouse
6. ✅ مراجعة الأمان
7. ✅ إضافة اختبارات الأمان
8. ✅ تنفيذ Drug Recall و Traceability
9. ✅ إضافة Loyalty و CRM
10. ✅ دعم الطباعة والإيصالات
11. ✅ تحسين إدارة الاشتراكات
12. ✅ تحسين إدارة المستخدمين
13. ✅ إضافة تقارير Dashboard
14. ✅ تحسين الأمان والمراجعة
15. ✅ تحسين إعدادات الإنتاج

---

## 📚 التوثيق

### الملفات المتاحة
- `PROJECT_RULES/01_PROJECT_OVERVIEW.md` - هذا الملف
- `PROJECT_RULES/02_ARCHITECTURE.md` - البنية المعمارية
- `PROJECT_RULES/03_CODING_STANDARDS.md` - معايير البرمجة
- `PROJECT_RULES/04_SECURITY_RULES.md` - قواعد الأمان
- `PROJECT_RULES/05_API_GUIDELINES.md` - إرشادات API
- `PROJECT_RULES/06_DEPLOYMENT.md` - النشر
- `PROJECT_RULES/07_TESTING_GUIDELINES.md` - إرشادات الاختبار
- `PROJECT_RULES/08_MODULE_GUIDES.md` - دليل الوحدات

---

## 👥 الفريق والأدوار

### Super Admin (مالك المنصة)
- إدارة جميع الصيدليات
- إدارة الاشتراكات والخطط
- مراقبة الأداء
- إدارة المستخدمين

### Admin (مدير الصيدلية)
- إدارة الصيدلية
- إدارة المستخدمين
- إدارة المخزون
- إدارة المبيعات

### Staff (الموظف)
- إدارة المبيعات
- إدارة المخزون
- خدمة العملاء

---

## 🎨 معايير التصميم

### تصميم UI/UX
- تصميم نظيف وبسيط
- واجهة متجاوبة (Responsive)
- دعم اللغات (RTL/LTR)
- دعم الوضع الليلي (Dark Mode)

### معايير الأداء
- تحميل سريع للصفحات
- صور محسّنة
- CSS/JS مضغوط
- Lazy Loading

---

## 📞 الدعم والصيانة

### قنوات الدعم
- البريد الإلكتروني
- الدردشة المباشرة
- مركز المساعدة
- التوثيق

### الصيانة
- نسخ احتياطي يومي
- تحديثات أمنية
- مراقبة الأداء
- إصلاح الأخطاء

---

## 📝 الترخيص

هذا المشروع مملوك لشركة Z-Syst Pharmacy Management System. جميع الحقوق محفوظة.

---

## 🔗 روابط مهمة

- المستودع: [GitHub Repository]
- التوثيق: [Documentation Site]
- الدعم: [Support Portal]
- المدونة: [Blog]

---

**آخر تحديث:** 2026-08-07  
**الإصدار:** 1.0.0
