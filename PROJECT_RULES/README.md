# Z-Syst Pharmacy Management System - Project Rules

## 📚 دليل القواعد الشامل

هذا المجلد يحتوي على جميع القواعد والإرشادات المطلوبة لتطوير وصيانة مشروع Z-Syst Pharmacy Management System.

---

## 📑 المحتويات

### 1. [نظرة عامة على المشروع](./01_PROJECT_OVERVIEW.md)
- معلومات المشروع
- الأهداف والمقاييس
- البنية المعمارية
- الوحدات الأساسية
- التقنيات المستخدمة

### 2. [دليل البنية المعمارية](./02_ARCHITECTURE.md)
- هيكل المشروع
- Service Layer Pattern
- Database Architecture
- API Architecture
- Model Architecture
- Controller Architecture
- Security Architecture
- Caching Strategy
- Queue Architecture
- Testing Architecture
- Logging Architecture
- Environment Configuration
- Deployment Architecture
- Monitoring & Observability
- Development Workflow

### 3. [معايير البرمجة](./03_CODING_STANDARDS.md)
- PHP Standards (PSR)
- Code Style
- Naming Conventions
- Structure & Formatting
- SOLID Principles
- Type Hints & Return Types
- Comments & Documentation
- Magic Numbers & Strings
- DRY Principle
- Clean Code Practices
- Error Handling
- Database Queries
- Security Best Practices
- Performance Best Practices
- Testing Standards

### 4. [قواعد الأمان](./04_SECURITY_RULES.md)
- Tenant Isolation
- Authentication & Authorization
- Input Validation
- SQL Injection Prevention
- XSS Prevention
- Password Security
- File Upload Security
- CSRF Protection
- Rate Limiting
- Security Headers
- Audit Logging
- API Security
- Database Security
- HTTPS Enforcement
- Security Testing
- Common Vulnerabilities
- Dependency Security
- Security Checklist

### 5. [إرشادات API](./05_API_GUIDELINES.md)
- RESTful API Principles
- HTTP Methods
- URL Structure
- Response Format
- HTTP Status Codes
- Pagination
- Filtering & Sorting
- Authentication
- Authorization
- Validation
- Rate Limiting
- Resource Transformation
- Versioning
- API Documentation
- API Testing
- Error Handling

### 6. [دليل النشر](./06_DEPLOYMENT.md)
- Pre-Deployment Checklist
- Environment Configuration
- Database Deployment
- Asset Compilation
- Server Configuration
- Security Hardening
- Continuous Deployment
- Monitoring
- Logging
- Backup Strategy
- Queue Workers
- Optimization
- SSL Configuration
- CDN Configuration
- Load Balancing
- Health Checks
- Performance Monitoring
- Incident Response

### 7. [إرشادات الاختبار](./07_TESTING_GUIDELINES.md)
- Testing Principles
- Directory Structure
- Unit Testing
- Feature Testing
- Security Testing
- Tenancy Testing
- Database Testing
- Transaction Testing
- Mocking & Faking
- Performance Testing
- Test Data Management
- Code Coverage
- Common Testing Pitfalls

### 8. [دليل الوحدات](./08_MODULE_GUIDES.md)
- إدارة الأعمال (Business Management)
- إدارة المخزون (Inventory Management)
- إدارة المبيعات (Sales Management)
- إدارة المشتريات (Purchases Management)
- نظام التأمين (Insurance Module)
- إدارة المستودعات (Warehouse Management)
- التتبع والاسترجاع (Traceability & Recall)
- نظام الولاء (Loyalty & CRM)
- الطباعة والإيصالات (Receipt Printing)
- التقارير (Reports & Analytics)
- إدارة المستخدمين (User Management)
- المراجعة والتدقيق (Audit Logs)
- Landing Module

### 9. [اقتراحات التحسين والإضافات](./09_IMPROVEMENT_SUGGESTIONS.md)
- اقتراحات التحسين الفوري (High Priority)
- اقتراحات التحسين المتوسط (Medium Priority)
- اقتراحات التحسين المنخفض (Low Priority)
- إصلاحات ومشاكل محتملة
- خطة التنفيذ المقترحة
- التقديرات المالية والموارد

---

## 🚀 البدء السريع

### للمطورين الجدد
1. اقرأ [نظرة عامة على المشروع](./01_PROJECT_OVERVIEW.md)
2. راجع [دليل البنية المعمارية](./02_ARCHITECTURE.md)
3. تعرف على [معايير البرمجة](./03_CODING_STANDARDS.md)
4. راجع [قواعد الأمان](./04_SECURITY_RULES.md)

### للمطورين الحاليين
1. راجع [دليل الوحدات](./08_MODULE_GUIDES.md) للوحدة التي تعمل عليها
2. اتبع [معايير البرمجة](./03_CODING_STANDARDS.md)
3. تأكد من [قواعد الأمان](./04_SECURITY_RULES.md)
4. اكتب [اختبارات](./07_TESTING_GUIDELINES.md)

### لنشر النظام
1. راجع [دليل النشر](./06_DEPLOYMENT.md)
2. تأكد من [قواعد الأمان](./04_SECURITY_RULES.md)
3. افحص [التحقق من النشر](./06_DEPLOYMENT.md#pre-deployment-checklist)

---

## 📖 كيفية استخدام هذا الدليل

### قبل البدء في التطوير
```bash
# اقرأ هذه الملفات بالترتيب:
1. 01_PROJECT_OVERVIEW.md
2. 02_ARCHITECTURE.md
3. 03_CODING_STANDARDS.md
4. 04_SECURITY_RULES.md
```

### أثناء التطوير
```bash
# راجع هذه الملفات حسب الحاجة:
- 05_API_GUIDELINES.md      - لتطوير API
- 07_TESTING_GUIDELINES.md   - لكتابة الاختبارات
- 08_MODULE_GUIDES.md       - لفهم الوحدات
```

### قبل النشر
```bash
# راجع هذه الملفات:
- 06_DEPLOYMENT.md          - لإعدادات النشر
- 04_SECURITY_RULES.md      - للتحقق من الأمان
```

---

## 🎯 مبادئ التطوير

### المبادئ الأساسية
1. **Clean Code:** كود نظيف وقابل للقراءة
2. **Security First:** الأمان على رأس الأولويات
3. **Test-Driven:** اختبارات شاملة
4. **Documentation:** توثيق واضح
5. **Performance:** أداء عالي

### قواعد التزامن
- اتباع PSR-12
- استخدام Type Hints
- كتابة DocBlocks
- اختبارات لكل feature
- مراجعة الكود قبل الدمج

---

## 🔗 روابط مهمة

### التوثيق الرسمي
- [Laravel Documentation](https://laravel.com/docs)
- [PHP Standards](https://www.php-fig.org/psr/)
- [OWASP Security](https://owasp.org/)

### أدوات التطوير
- [Laravel Forge](https://forge.laravel.com/)
- [Laravel Vapor](https://vapor.laravel.com/)
- [Sentry](https://sentry.io/)
- [New Relic](https://newrelic.com/)

---

## 📝 التحديثات

### آخر تحديث
- **التاريخ:** 2026-08-07
- **الإصدار:** 1.0.0
- **الحالة:** Production Ready

### التغييرات الأخيرة
- إضافة جميع قواعد المشروع
- تحديث معايير الأمان
- إضافة دليل الوحدات الشامل
- تحديث إرشادات النشر

---

## 👥 الفريق

### الأدوار
- **Super Admin:** مالك المنصة
- **Admin:** مدير الصيدلية
- **Staff:** الموظف
- **Developer:** المطور

### التواصل
- البريد الإلكتروني: support@z-syst.com
- Slack: #z-syst-development
- Jira: Project Board

---

## 🚨 ملاحظات هامة

### ⚠️ تحذيرات
- لا تلتزم بـ `.env` files
- لا تقدم بيانات حساسة
- اتبع معايير الأمان دائماً
- اختبر قبل النشر

### ✅ أفضل الممارسات
- اقرأ التوثيق قبل البدء
- اكتب اختبارات شاملة
- راجع الكود قبل الدمج
- احتفظ بالتوثيق محدثاً

---

## 📞 الدعم

### الحصول على المساعدة
1. راجع التوثيق أولاً
2. ابحث في الـ Issues
3. اسأل في Slack
4. أنشأ Issue إذا لزم الأمر

### الإبلاغ عن مشاكل
- Bugs: أنشأ Issue مع tag `bug`
- Security: أرسل email إلى security@z-syst.com
- Feature Request: أنشأ Issue مع tag `enhancement`

---

## 📄 الترخيص

هذا المشروع مملوك لشركة Z-Syst Pharmacy Management System. جميع الحقوق محفوظة.

---

**تم إنشاء هذا الدليل:** 2026-08-07  
**آخر تحديث:** 2026-08-07  
**الإصدار:** 1.0.0
