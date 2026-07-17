# Z-Syst Pharmacy/POS Backend API Documentation

## ملاحظة
هذا الملف يوضح بنية الـ API الكاملة للباكند.

## المسارات الأساسية للـ API

### المصادقة (Authentication)
| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| POST | `/api/v1/register` | تسجيل مستخدم جديد | ❌ |
| POST | `/api/v1/login` | تسجيل الدخول | ❌ |
| POST | `/api/v1/logout` | تسجيل الخروج | ✅ |
| POST | `/api/v1/refresh` | تجديد التوكن | ✅ |
| GET | `/api/v1/me` | معلومات المستخدم الحالي | ✅ |

### إدارة المرضى
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/patients` | قائمة المرضى (paginated) |
| POST | `/api/v1/patients` | إنشاء مريض جديد |
| GET | `/api/v1/patients/{id}` | عرض مريض محدد |
| PUT | `/api/v1/patients/{id}` | تحديث مريض |
| DELETE | `/api/v1/patients/{id}` | حذف مريض |

### إدارة الأطباء
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/doctors` | قائمة الأطباء (paginated) |
| POST | `/api/v1/doctors` | إنشاء طبيب جديد |
| GET | `/api/v1/doctors/{id}` | عرض طبيب محدد |
| PUT | `/api/v1/doctors/{id}` | تحديث طبيب |
| DELETE | `/api/v1/doctors/{id}` | حذف طبيب |

### إدارة الوصفات
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/prescriptions` | قائمة الوصفات (paginated) |
| POST | `/api/v1/prescriptions` | إنشاء وصفة جديدة |
| GET | `/api/v1/prescriptions/{id}` | عرض وصفة محددة |
| PUT | `/api/v1/prescriptions/{id}` | تحديث وصفة |
| DELETE | `/api/v1/prescriptions/{id}` | حذف وصفة |
| POST | `/api/v1/prescriptions/{id}/dispense` | تصدير دواء من الوصفة |
| POST | `/api/v1/prescriptions/{id}/dispense-by-barcode` | تصدير دواء بالباركود |
| POST | `/api/v1/pharmacy/checkout` | عملية دفع سريعة |
| GET | `/api/v1/pharmacy/demand-forecast` | توقع الطلب |
| GET | `/api/v1/pharmacy/pos-summary` | ملخص نقطة البيع |

### أوامر الشراء
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/purchase-orders` | قائمة أوامر الشراء |
| POST | `/api/v1/purchase-orders` | إنشاء أمر شراء |
| GET | `/api/v1/purchase-orders/{id}` | عرض أمر شراء |
| PUT | `/api/v1/purchase-orders/{id}` | تحديث أمر شراء |
| DELETE | `/api/v1/purchase-orders/{id}` | حذف أمر شراء |
| POST | `/api/v1/purchase-orders/{id}/approve` | موافقة على أمر الشراء |
| POST | `/api/v1/purchase-orders/{id}/send` | إرسال أمر الشراء |
| POST | `/api/v1/purchase-orders/{id}/cancel` | إلغاء أمر الشراء |

### تحويل المخزون
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/stock-transfers` | قائمة تحويلات المخزون |
| POST | `/api/v1/stock-transfers` | إنشاء تحويل جديد |
| GET | `/api/v1/stock-transfers/{id}` | عرض تحويل محدد |
| POST | `/api/v1/stock-transfers/{id}/approve` | موافقة على التحويل |
| POST | `/api/v1/stock-transfers/{id}/reject` | رفض التحويل |
| POST | `/api/v1/stock-transfers/{id}/ship` | شحن التحويل |
| POST | `/api/v1/stock-transfers/{id}/receive` | استلام التحويل |
| POST | `/api/v1/stock-transfers/{id}/cancel` | إلغاء التحويل |
| GET | `/api/v1/stock-transfers/statistics` | إحصائيات التحويل |

### مطالبات التأمين
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/insurance-claims` | قائمة المطالبات |
| POST | `/api/v1/insurance-claims` | إنشاء مطالبة |
| GET | `/api/v1/insurance-claims/{id}` | عرض مطالبة |
| PUT | `/api/v1/insurance-claims/{id}` | تحديث مطالبة |
| DELETE | `/api/v1/insurance-claims/{id}` | حذف مطالبة |

### الشركات التأمين
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/insurance-companies` | قائمة الشركات |
| POST | `/api/v1/insurance-companies` | إنشاء شركة |
| GET | `/api/v1/insurance-companies/{id}` | عرض شركة |
| PUT | `/api/v1/insurance-companies/{id}` | تحديث شركة |
| DELETE | `/api/v1/insurance-companies/{id}` | حذف شركة |

### الخطط التأمين
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/insurance-plans` | قائمة الخطط |
| POST | `/api/v1/insurance-plans` | إنشاء خطة |
| GET | `/api/v1/insurance-plans/{id}` | عرض خطة |
| PUT | `/api/v1/insurance-plans/{id}` | تحديث خطة |
| DELETE | `/api/v1/insurance-plans/{id}` | حذف خطة |

### المنتجات والمخزون
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/products` | قائمة المنتجات |
| POST | `/api/v1/products` | إنشاء منتج |
| GET | `/api/v1/products/{id}` | عرض منتج |
| PUT | `/api/v1/products/{id}` | تحديث منتج |
| DELETE | `/api/v1/products/{id}` | حذف منتج |
| GET | `/api/v1/products/barcode/{barcode}` | بحث باركود |
| GET | `/api/v1/drugs` | قائمة الأدوية (عام) |

### التقارير
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/purchase-report` | تقرير المشتريات |
| GET | `/api/v1/sales-report` | تقرير المبيعات |
| GET | `/api/v1/low-stock-report` | تقرير المخزون المنخفض |
| GET | `/api/v1/income-report` | تقرير الإيرادات |
| GET | `/api/v1/expense-report` | تقرير المصاريف |

## رموز الحالة (Status Codes)
- `200` - نجاح العملية
- `201` - تم الإنشاء بنجاح
- `403` - غير مخول
- `422` - فشل التحقق من المدخلات