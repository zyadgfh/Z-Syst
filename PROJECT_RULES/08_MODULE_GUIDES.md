# Z-Syst Module Guides

## 📦 دليل الوحدات النمطية

يحتوي هذا الدليل على وصف شامل لكل وحدة في نظام Z-Syst Pharmacy Management System.

---

## 🏢 إدارة الأعمال (Business Management)

### نظرة عامة
إدارة الصيدليات والاشتراكات والخطط.

### المكونات
- **Models:** `Business`, `Plan`, `PlanSubscribe`
- **Services:** `SubscriptionService`, `TenantService`
- **Controllers:** `ZSystBusinessController`, `ZSystPlanController`

### الوظائف الرئيسية
- إنشاء وإدارة الصيدليات
- إدارة الاشتراكات والخطط
- ترقية وتجديد الاشتراكات
- إحصائيات الأعمال

### الـ API Endpoints
```
GET    /business              - List businesses
POST   /business              - Create business
PUT    /business/{id}         - Update business
DELETE /business/{id}         - Delete business
PUT    /business/upgrade-plan/{id} - Upgrade plan

GET    /plans                 - List plans
POST   /plans                 - Create plan
PUT    /plans/{id}            - Update plan
DELETE /plans/{id}            - Delete plan
GET    /plans/statistics      - Plan statistics
GET    /plans/popular         - Popular plans
```

### قواعد الأمان
- Super Admin فقط يمكنه إدارة الأعمال
- التحقق من صلاحية الترقية
- حماية بيانات الاشتراك

---

## 📦 إدارة المخزون (Inventory Management)

### نظرة عامة
إدارة المنتجات والأصناف والمخزون.

### المكونات
- **Models:** `Product`, `Category`, `ProductStock`
- **Services:** `ProductService`, `InventoryService`
- **Controllers:** `ProductController`, `CategoryController`

### الوظائف الرئيسية
- إدارة المنتجات
- إدارة الأصناف
- تتبع المخزون
- تنبيهات المخزون المنخفض
- نظام FEFO (First Expired First Out)

### الـ API Endpoints
```
GET    /products              - List products
POST   /products              - Create product
PUT    /products/{id}         - Update product
DELETE /products/{id}         - Delete product
GET    /products/{id}/stock   - Product stock
POST   /products/{id}/stock   - Adjust stock

GET    /categories            - List categories
POST   /categories            - Create category
PUT    /categories/{id}       - Update category
DELETE /categories/{id}       - Delete category
```

### قواعد الأمان
- عزل بيانات المنتجات حسب business_id
- التحقق من الصلاحيات
- منع المخزون السالب

---

## 🏪 إدارة المبيعات (Sales Management)

### نظرة عامة
إدارة المبيعات والفواتير والعملاء.

### المكونات
- **Models:** `Sale`, `SaleDetail`, `Party` (Customer)
- **Services:** `SaleService`, `InvoiceService`
- **Controllers:** `SaleController`, `InvoiceController`

### الوظائف الرئيسية
- إنشاء المبيعات
- إدارة الفواتير
- إدارة العملاء
- عروض الأسعار
- المرتجعات
- خصومات المشتريات

### الـ API Endpoints
```
GET    /sales                 - List sales
POST   /sales                 - Create sale
PUT    /sales/{id}            - Update sale
DELETE /sales/{id}            - Delete sale
GET    /sales/{id}/invoice    - Sale invoice

GET    /customers             - List customers
POST   /customers             - Create customer
PUT    /customers/{id}        - Update customer
DELETE /customers/{id}        - Delete customer
```

### قواعد الأمان
- التحقق من صلاحية إنشاء المبيعات
- حماية بيانات العملاء
- التحقق من المخزون قبل البيع

---

## 🛒 إدارة المشتريات (Purchases Management)

### نظرة عامة
إدارة المشتريات والموردين.

### المكونات
- **Models:** `Purchase`, `PurchaseDetail`, `Party` (Supplier)
- **Services:** `PurchaseService`, `SupplierService`
- **Controllers:** `PurchaseController`, `SupplierController`

### الوظائف الرئيسية
- إنشاء أوامر الشراء
- إدارة الموردين
- الاستلام والفحص
- المرتجعات
- تتبع المشتريات

### الـ API Endpoints
```
GET    /purchases             - List purchases
POST   /purchases             - Create purchase
PUT    /purchases/{id}        - Update purchase
DELETE /purchases/{id}        - Delete purchase

GET    /suppliers             - List suppliers
POST   /suppliers             - Create supplier
PUT    /suppliers/{id}        - Update supplier
DELETE /suppliers/{id}        - Delete supplier
```

### قواعد الأمان
- التحقق من الصلاحيات
- حماية بيانات الموردين
- التحقق من الموازنة

---

## 🏥 نظام التأمين (Insurance Module)

### نظرة عامة
إدارة شركات التأمين والوثائق والمطالبات.

### المكونات
- **Models:** `InsuranceCompany`, `InsurancePolicy`, `InsuranceClaim`, `InsuranceCoverage`
- **Services:** `InsuranceService`
- **Controllers:** `InsuranceCompanyController`, `InsurancePolicyController`, `InsuranceClaimController`

### الوظائف الرئيسية
- إدارة شركات التأمين
- إدارة وثائق التأمين
- معالجة المطالبات
- إدارة التغطيات
- تتبع الأدوية المؤمنة

### الـ API Endpoints
```
GET    /insurance/companies   - List insurance companies
POST   /insurance/companies   - Create company
PUT    /insurance/companies/{id} - Update company
DELETE /insurance/companies/{id} - Delete company

GET    /insurance/policies    - List policies
POST   /insurance/policies    - Create policy
PUT    /insurance/policies/{id} - Update policy
DELETE /insurance/policies/{id} - Delete policy

GET    /insurance/claims      - List claims
POST   /insurance/claims      - Create claim
PUT    /insurance/claims/{id} - Update claim
DELETE /insurance/claims/{id} - Delete claim
```

### قواعد الأمان
- حماية بيانات التأمين الحساسة
- التحقق من الصلاحيات
- تسجيل جميع العمليات

---

## 🏭 إدارة المستودعات (Warehouse Management)

### نظرة عامة
إدارة المستودعات المتعددة وانتقالات المخزون.

### المكونات
- **Models:** `Warehouse`, `WarehouseStock`, `StockTransfer`
- **Services:** `WarehouseService`
- **Controllers:** `WarehouseController`, `StockTransferController`

### الوظائف الرئيسية
- إدارة المستودعات
- تتبع المخزون في كل مستودع
- انتقالات المخزون بين المستودعات
- إدارة المخزون المتعدد
- تقارير المستودعات

### الـ API Endpoints
```
GET    /warehouses            - List warehouses
POST   /warehouses            - Create warehouse
PUT    /warehouses/{id}       - Update warehouse
DELETE /warehouses/{id}       - Delete warehouse

GET    /warehouses/{id}/stock - Warehouse stock
POST   /stock-transfers       - Create transfer
PUT    /stock-transfers/{id}  - Update transfer
DELETE /stock-transfers/{id}  - Delete transfer
```

### قواعد الأمان
- التحقق من الصلاحيات
- منع انتقالات المخزون غير المصرح بها
- حماية بيانات المستودعات

---

## 🔍 التتبع والاسترجاع (Traceability & Recall)

### نظرة عامة
تتبع الأدوية وإدارة الاسترجاع.

### المكونات
- **Models:** `BatchLot`, `RecallEvent`, `TraceabilityLog`
- **Services:** `TraceabilityService`
- **Controllers:** `TraceabilityController`, `RecallController`

### الوظائف الرئيسية
- إدارة الدفعات والأرقام التسلسلية
- تتبع كامل للمنتجات
- إدارة أحداث الاسترجاع
- سجلات التتبع
- تنبيهات الاسترجاع

### الـ API Endpoints
```
GET    /traceability/batches  - List batches
POST   /traceability/batches  - Create batch
GET    /traceability/track    - Track product
GET    /recalls               - List recalls
POST   /recalls               - Create recall
PUT    /recalls/{id}          - Update recall
DELETE /recalls/{id}          - Delete recall
```

### قواعد الأمان
- تسجيل جميع عمليات التتبع
- التحقق من الصلاحيات
- حماية بيانات الاسترجاع

---

## ⭐ نظام الولاء (Loyalty & CRM)

### نظرة عامة
إدارة برامج الولاء وعلاقات العملاء.

### المكونات
- **Models:** `LoyaltyProgram`, `LoyaltyTransaction`, `CustomerInteraction`
- **Services:** `LoyaltyService`
- **Controllers:** `LoyaltyController`

### الوظائف الرئيسية
- إدارة برامج الولاء
- إدارة النقاط والمكافآت
- تتبع تفاعلات العملاء
- إدارة علاقات العملاء
- تقارير الولاء

### الـ API Endpoints
```
GET    /loyalty/programs      - List programs
POST   /loyalty/programs      - Create program
PUT    /loyalty/programs/{id} - Update program
DELETE /loyalty/programs/{id} - Delete program

GET    /loyalty/transactions  - List transactions
GET    /loyalty/interactions  - List interactions
POST   /loyalty/interactions  - Create interaction
GET    /loyalty/statistics    - Loyalty statistics
GET    /loyalty/top-customers - Top loyal customers
```

### قواعد الأمان
- حماية بيانات الولاء
- التحقق من الصلاحيات
- منع التلاعب بالنقاط

---

## 🖨️ الطباعة والإيصالات (Receipt Printing)

### نظرة عامة
إدارة الإيصالات والطباعة.

### المكونات
- **Models:** `Receipt`, `ReceiptSetting`
- **Services:** `ReceiptService`
- **Controllers:** `ReceiptController`

### الوظائف الرئيسية
- إنشاء الإيصالات
- تخصيص القوالب
- دعم PDF/HTML/Thermal
- إعدادات الطباعة
- تتبع الإيصالات

### الـ API Endpoints
```
GET    /receipts              - List receipts
POST   /receipts/generate-sale - Generate sale receipt
POST   /receipts/generate-purchase - Generate purchase receipt
GET    /receipts/{id}/download-pdf - Download PDF
GET    /receipts/{id}/view-html - View HTML
POST   /receipts/{id}/mark-printed - Mark as printed
DELETE /receipts/{id}        - Delete receipt
GET    /receipts/settings     - Receipt settings
PUT    /receipts/settings     - Update settings
```

### قواعد الأمان
- حماية بيانات الإيصالات
- التحقق من الصلاحيات
- منع الوصول غير المصرح به

---

## 📊 التقارير (Reports & Analytics)

### نظرة عامة
تقارير Dashboard وتحليلات الأداء.

### المكونات
- **Services:** `DashboardReportService`
- **Controllers:** `DashboardReportController`, `ReportController`

### الوظائف الرئيسية
- تقارير الإيرادات
- تقارير المبيعات والمشتريات
- تقارير المخزون
- تقارير الأرباح والخسائر
- تقارير الاشتراكات
- تقارير المستودعات
- تقارير الولاء

### الـ API Endpoints
```
GET    /dashboard-reports/overall - Overall statistics
GET    /dashboard-reports/sales-by-date - Sales by date
GET    /dashboard-reports/top-products - Top products
GET    /dashboard-reports/top-customers - Top customers
GET    /dashboard-reports/profit-loss - Profit & loss
GET    /dashboard-reports/warehouse - Warehouse statistics
GET    /dashboard-reports/transfers - Transfer statistics
GET    /dashboard-reports/recalls - Recall statistics
GET    /dashboard-reports/loyalty - Loyalty statistics
GET    /dashboard-reports/comprehensive - Comprehensive report
```

### قواعد الأمان
- التحقق من الصلاحيات
- حماية بيانات التقارير
- منع الوصول لتقارير أخرى

---

## 👥 إدارة المستخدمين (User Management)

### نظرة عامة
إدارة المستخدمين والأدوار والصلاحيات.

### المكونات
- **Models:** `User`, `Role`, `Permission`
- **Services:** `UserManagementService`
- **Controllers:** `UserController`, `RoleController`

### الوظائف الرئيسية
- إدارة المستخدمين
- إدارة الأدوار
- إدارة الصلاحيات
- نسخ الصلاحيات
- إحصائيات المستخدمين

### الـ API Endpoints
```
GET    /users                 - List users
POST   /users                 - Create user
PUT    /users/{id}            - Update user
DELETE /users/{id}            - Delete user
POST   /users/bulk-change-status - Bulk change status
GET    /users/statistics      - User statistics
GET    /users/role-statistics - Role statistics
GET    /users/permissions-grouped - Grouped permissions
GET    /users/{id}/permissions - User permissions
POST   /users/clone-permissions - Clone permissions
```

### قواعد الأمان
- حماية بيانات المستخدمين
- منع حذف Super Admin
- التحقق من الصلاحيات

---

## 🔒 المراجعة والتدقيق (Audit Logs)

### نظرة عامة
تسجيل وتتبع جميع العمليات المهمة.

### المكونات
- **Models:** `AuditLog`
- **Services:** `AuditService`
- **Controllers:** `AuditLogController`

### الوظائف الرئيسية
- تسجيل CRUD operations
- تسجيل Login/Logout
- تسجيل Export/Import
- إحصائيات السجلات
- تنظيف السجلات القديمة

### الـ API Endpoints
```
GET    /audit-logs            - List audit logs
GET    /audit-logs/{id}       - Show audit log
GET    /audit-logs/statistics - Audit statistics
GET    /audit-logs/model-logs - Model logs
GET    /audit-logs/user-logs  - User logs
DELETE /audit-logs/{id}       - Delete audit log
POST   /audit-logs/clean-old  - Clean old logs
```

### قواعد الأمان
- حماية سجلات المراجعة
- التحقق من الصلاحيات
- منع حذف السجلات الحساسة

---

## 🌐 Landing Module

### نظرة عامة
صفحة هبوط SaaS للموقع.

### المكونات
- **Models:** `Feature`, `Pricing`, `Testimonial`
- **Services:** `LandingService`
- **Controllers:** `LandingController`

### الوظائف الرئيسية
- عرض معلومات الموقع
- عرض الميزات
- عرض الأسعار
- إدارة التقييمات
- نموذج التواصل

### الـ API Endpoints
```
GET    /api/landing/info      - Landing info
GET    /api/landing/features  - Features
GET    /api/landing/pricing   - Pricing
GET    /api/landing/testimonials - Testimonials
POST   /api/landing/contact   - Contact form
```

### قواعد الأمان
- حماية بيانات التواصل
- التحقق من البيانات المدخلة
- منع spam

---

## 📚 ملخص جميع الوحدات

### الوحدات النشطة
1. ✅ إدارة الأعمال (Business Management)
2. ✅ إدارة المخزون (Inventory Management)
3. ✅ إدارة المبيعات (Sales Management)
4. ✅ إدارة المشتريات (Purchases Management)
5. ✅ نظام التأمين (Insurance Module)
6. ✅ إدارة المستودعات (Warehouse Management)
7. ✅ التتبع والاسترجاع (Traceability & Recall)
8. ✅ نظام الولاء (Loyalty & CRM)
9. ✅ الطباعة والإيصالات (Receipt Printing)
10. ✅ التقارير (Reports & Analytics)
11. ✅ إدارة المستخدمين (User Management)
12. ✅ المراجعة والتدقيق (Audit Logs)
13. ✅ Landing Module

### الوحدات قيد التطوير
- 🔄 نظام الفواتير الإلكترونية (E-Invoicing)
- 🔄 تكامل مع أنظمة الموزعين (Distributor Integration)
- 🔄 تطبيق الجوال (Mobile App)

---

## 🔗 الموارد المشتركة

### Global Scopes
جميع النماذج تستخدم Global Scopes لعزل البيانات حسب `business_id`.

### Middleware
- `TenantContextMiddleware` - تعيين سياق المستأجر
- `TenantAccessCheck` - التحقق من صلاحية الوصول
- `Authenticate` - التحقق من المصادقة

### الصلاحيات المشتركة
- `{module}-create` - إنشاء
- `{module}-read` - قراءة
- `{module}-update` - تحديث
- `{module}-delete` - حذف

---

## 📝 أفضل الممارسات

### لكل وحدة
1. **Service Layer:** منطق العمل في Services
2. **Validation:** التحقق في Form Requests
3. **Audit Logging:** تسجيل العمليات المهمة
4. **Error Handling:** معالجة الأخطاء بشكل صحيح
5. **Testing:** اختبارات شاملة لكل وحدة

### التوثيق
- DocBlocks لكل method
- تعليقات للمنطق المعقد
- أمثلة الاستخدام
- دليل API

---

## 🚀 التطوير المستقبلي

### إضافات مخططة
- نظام إدارة الرواتب (Payroll)
- تكامل مع أنظمة المبيعات (POS Integration)
- تحليلات متقدمة (Advanced Analytics)
- AI للتنبؤ بالمخزون (AI Stock Prediction)
- تكامل مع منصات التجارة الإلكترونية (E-commerce Integration)

---

**آخر تحديث:** 2026-08-07
