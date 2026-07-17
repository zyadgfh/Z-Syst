# ميزة إرسال الفاتورة عبر الواتساب

## نظرة عامة
تم إضافة ميزة تلقائية لإرسال فاتورة البيع إلى العميل عبر الواتساب كصورة بمجرد حفظ الفاتورة، مع إمكانية الطباعة للنسخ المادية.

## الملفات المُنشأة

### 1. الخدمات (Services)
- `app/Services/WhatsAppService.php` - خدمة إرسال الرسائل عبر WhatsApp Business API
- `app/Services/InvoiceImageService.php` - خدمة تحويل الفاتورة إلى صورة

### 2. المتحكمات (Controllers)
- `Modules/ZSyst/App/Http/Controllers/PosSaleController.php` - مُعدل لإضافة إرسال الواتساب
- `app/Http/Controllers/API/InvoiceWhatsAppController.php` - متحكم API لإرسال الفاتورة

### 3. القوالب (Views)
- `resources/views/invoices/sale-print.blade.php` - قالب الفاتورة القابل للطباعة والإرسال

### 4. المسارات (Routes)
- `routes/api.php` - مُضيف للمسار `send-invoice-whatsapp`

### 5. الإعدادات (Config)
- `config/services.php` - مُضيف إعدادات WhatsApp API

## طريقة الاستخدام

### 1. طريقة API (الإرسال التلقائي)
```javascript
// عند إنشاء فاتورة جديدة، أضف الخانتين التاليتين:
{
    "customer_name": "العميل",
    "customer_phone": "01234567890", // رقم هاتف العميل
    "send_whatsapp": true, // إرسال تلقائي
    "items": [...],
    "total_amount": 100.00
}
```

### 2. طريقة الواجهة (الإرسال اليدوي)
افتح الفاتورة في المتصفح وستجد زرين:
- **🖨️ طباعة الفاتورة** - للطباعة المباشرة
- **📱 إرسال عبر الواتساب** - لإرسال الفاتورة كصورة

## الخطوات المطلوبة للإكمال

### 1. الحصول على WhatsApp Business API
1. اذهب إلى [Facebook Business Manager](https://business.facebook.com)
2. أنشئ حساب WhatsApp Business
3. احصل على:
   - `WHATSAPP_API_KEY` (Access Token)
   - `WHATSAPP_PHONE_NUMBER` (رقم الهاتف بدون +)

### 2. إضافة المتغيرات إلى ملف .env
```env
WHATSAPP_API_URL=https://graph.facebook.com/v17.0
WHATSAPP_API_KEY=your_whatsapp_business_api_key
WHATSAPP_PHONE_NUMBER=14155238886
WHATSAPP_API_VERSION=v17.0
```

### 3. تثبيت المكتبات المطلوبة (اختياري للإنتاج)
```bash
# لتحويل HTML إلى PDF
composer require barryvdh/laravel-dompdf

# لتحويل PDF إلى صورة (إذا لزم الأمر)
# تأكد من تثبيت ImageMagick على الخادم
```

### 4. ربط العلاقات في نموذج PosSale
أضف حقل الهاتف في قاعدة البيانات:
```sql
ALTER TABLE pos_sales ADD COLUMN customer_phone VARCHAR(20) NULL;
```

## أمثلة الاستخدام

### مثال 1: إرسال فاتورة جديدة مع الواتساب
```php
// في الواجهة الأمامية
const saleData = {
    customer_name: 'محمد أحمد',
    customer_phone: '01234567890',
    send_whatsapp: true,
    items: [
        { barcode: '123456', name: 'دواء', price: 50, quantity: 2 }
    ],
    total_amount: 100
};

fetch('/api/v1/sales', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(saleData)
});
```

### مثال 2: إرسال فاتورة موجودة
```javascript
// من صفحة الفاتورة
function sendExistingInvoice(invoiceId, phone) {
    fetch(`/api/v1/sales/${invoiceId}`)
        .then(response => response.json())
        .then(sale => {
            // استخدم html2canvas لتحويل الفاتورة إلى صورة
            // ثم أرسلها عبر الواتساب
        });
}
```

## ملاحظات مهمة

1. **الأمان**: تأكد من إعداد رمز WhatsApp API الخاص بك في ملف .env
2. **التنظيف**: قم بتنظيف ملفات الفواتير في `storage/app/invoices` دورياً
3. **النسخة الموجودة**: تم تعديل `PosSaleController.php` لإضافة الإرسال التلقائي
4. **التوثيق**: راجع [وثائق WhatsApp Business API](https://developers.facebook.com/docs/whatsapp/business-management-api) للحصول على تفاصيل إضافية

## المزايا
- ✅ إرسال تلقائي للفواتير عند الحفظ
- ✅ دعم الطباعة المباشرة
- ✅ تحويل الفاتورة إلى صورة عالية الجودة
- ✅ تنسيق الملف الدولي E.164 لأرقام الهواتف
- ✅ تسجيل الأخطاء للمراجعة لاحقاً

## الملفات الإضافية
- `sales_invoice_whatsapp.html` - تصميم توضيحي للميزة
- `IMPLEMENTATION_PLAN_WHATSAPP_INVOICE.md` - خطة التنفيذ التفصيلية