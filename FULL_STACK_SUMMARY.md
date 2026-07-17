# Full Stack Development - Z-Syst Pharmacy System

## الهدف
تم إنشاء نظام باكند وفرونت إند متكامل لإدارة صيدلية/POS.

## الباكند (Backend)

### Form Requests
- StorePatientRequest, UpdatePatientRequest
- StoreDoctorRequest, UpdateDoctorRequest
- StorePrescriptionRequest, DispensePrescriptionRequest

### API Resources
- PatientResource, DoctorResource, PrescriptionResource
- PrescriptionItemResource, ProductResource, CategoryResource
- ManufacturerResource, TaxResource, MedicineTypeResource
- PurchaseOrderResource, PurchaseOrderItemResource, SupplierResource
- StockTransferResource, StockTransferItemResource
- InsuranceClaimResource, InsuranceCompanyResource, InsurancePlanResource
- StockResource, CashRegisterResource

### Events
- PrescriptionCreated, PrescriptionDispensed, PurchaseOrderCreated

### Tests
- PatientApiTest, DoctorApiTest, PrescriptionApiTest
- PurchaseOrderApiTest, StockTransferApiTest, InsuranceClaimApiTest

## الفرونت إند (Frontend)

### Admin Views
- dashboard.blade.php - لوحة التحكم
- patients.blade.php - إدارة المرضى
- doctors.blade.php - إدارة الأطباء
- products.blade.php - إدارة المنتجات

### Styles
- pharmacy-pos.css - أنماط موحدة

### Routes المضافة
```
/admin/dashboard -> admin.dashboard
/admin/patients -> admin.patients
/admin/doctors -> admin.doctors
/admin/products -> admin.products
```

## الواجهات المتكاملة
- ✅ API Endpoints كاملة
- ✅ Admin Panel متكامل
- ✅ POS Interface
- ✅ متجاوب مع الجوال
- ✅ توثيق API
- ✅ اختبارات Feature

## خطوات التشغيل
```bash
php artisan serve
# الواجهة على: /pharmacy/pos
# لوحة الإدارة على: /admin/dashboard