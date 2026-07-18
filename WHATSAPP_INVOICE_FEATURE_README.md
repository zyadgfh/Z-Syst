# نظام إرسال الفواتير عبر الواتساب والإشعارات المتقدمة

## 📋 نظرة عامة

يوفر هذا النظام إمكانية إرسال الفواتير تلقائياً أو يدوياً عبر الواتساب بعد إتمام عملية البيع، مع دعم متعدد القنوات (SMS, Email, Print).

## ✨ الميزات الرئيسية

### 1. إرسال الفاتورة عبر الواتساب
- إرسال الفاتورة كصورة (Image)
- إرسال الفاتورة كملف PDF
- إرسال الاثنين معاً
- رسائل مخصصة مع دعم المتغيرات

### 2. نافذة الخيارات بعد الحفظ
- نافذة منبثقة تظهر بعد حفظ الفاتورة
- خيارات طباعة متعددة (حرارية, A4)
- حفظ الإعدادات كافتراضية
- تخطي العملية

### 3. سجل الإشعارات
- تتبع جميع الإشعارات المرسلة
- عرض حالة كل إشعار (مرسل, مُسلم, فاشل)
- إعادة الإرسال للإشعارات الفاشلة

### 4. الإرسال التلقائي
- إرسال تلقائي للفواتير بعد الحفظ (اختياري)
- Queue للمعالجة الخلفية

## 📁 بنية الملفات

```
app/
├── Models/
│   ├── InvoiceNotification.php     # نموذج الإشعارات
│   └── Sale.php                    # محدث بالعلاقات
├── Services/
│   ├── WhatsAppService.php         # محسن لإرسال PDF
│   └── Invoice/
│       ├── InvoiceNotificationService.php  # الخدمة الرئيسية
│       ├── InvoicePDFGenerator.php       # توليد ملفات PDF
│       └── InvoiceImageGenerator.php     # توليد الصور (مُعاد تسميتها)
├── Http/Controllers/API/
│   ├── InvoiceNotificationController.php   # وحدة التحكم
│   └── InvoiceWhatsAppController.php       # محكم للإرسال الجديد
├── Jobs/
│   ├── AutoSendInvoiceJob.php            # Job للإرسال التلقائي
│   └── PrintInvoiceJob.php               # Job للطباعة
└── ...

database/
└── migrations/
    └── 2024_12_01_000000_create_invoice_notifications_table.php

resources/
└── views/
    └── components/
        └── invoice-action-modal.blade.php  # نافذة الخيارات
```

## 🚀 طريقة الاستخدام

### 1. تفعيل النظام

```env
# في ملف .env
WHATSAPP_API_URL=https://graph.facebook.com/v17.0
WHATSAPP_API_KEY=your_whatsapp_business_api_key
WHATSAPP_PHONE_NUMBER=your_phone_number_id
```

### 2. تشغيل Migration

```bash
php artisan migrate
```

### 3. استخدام الخدمة في الكود

```php
use App\Services\Invoice\InvoiceNotificationService;
use App\Models\Sale;

// بعد حفظ الفاتورة
$sale = Sale::create($saleData);

// إرسال الفاتورة عبر الواتساب
$service = app(InvoiceNotificationService::class);
$result = $service->sendInvoice(
    sale: $sale,
    channels: [
        'whatsapp' => [
            'sendImage' => true,
            'sendPDF' => false,
            'customMessage' => 'شكراً لتسوقكم من صيدليتنا!'
        ]
    ]
);
```

### 4. استخدام الـ API

```javascript
// بعد حفظ الفاتورة
const response = await fetch(`/api/v1/sales/${saleId}/send-notifications`, {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken
    },
    body: JSON.stringify({
        channels: {
            whatsapp: {
                sendImage: true,
                sendPDF: false,
                customMessage: 'رسالة مخصصة'
            }
        }
    })
});
```

## 🔧 الإعدادات المتقدمة

### إعدادات الشركة (تُخزن في جدول companies)

```php
// invoice_notification_settings في جدول companies
[
    'whatsapp_enabled' => true,
    'send_as_image' => true,
    'send_as_pdf' => false,
    'default_invoice_message' => 'مرحباً {customer_name}، فاتورتك رقم {invoice_number}',
    'auto_send_invoice' => true, // تفعيل الإرسال التلقائي
]
```

### إعدادات العميل (تُخزن في جدول parties)

```php
// notification_preferences في جدول parties
[
    'whatsapp' => true,
    'sms' => false,
    'email' => true,
    'preferred_language' => 'ar'
]
```

## 📊 متغيرات الرسالة

| المتغير | الوصف | مثال |
|---------|-------|------|
| `{customer_name}` | اسم العميل | "محمد أحمد" |
| `{invoice_number}` | رقم الفاتورة | "S-00001" |
| `{total_amount}` | المبلغ الإجمالي | "150.00" |
| `{date}` | التاريخ | "2024-12-01" |
| `{time}` | الوقت | "14:30" |
| `{company_name}` | اسم الشركة | "صيدلية النور" |
| `{branch_name}` | اسم الفرع | "الفرع الرئيسي" |

## 🔄 الإرسال التلقائي

لتمكين الإرسال التلقائي بعد الحفظ:

```php
// في SaleController بعد الحفظ
if ($company->invoice_notification_settings['auto_send_invoice'] ?? false) {
    AutoSendInvoiceJob::dispatch($sale, ['whatsapp' => ['sendImage' => true]]);
}
```

## 🛠️ التوافقية

- Laravel 9+
- PHP 8.1+
- WhatsApp Business API (Meta/Facebook)
- اختياري: barryvdh/laravel-dompdf لتوليد PDF
- اختياري: spatie/browsershot لتوليد الصور

## 📝 ملاحظات مهمة

1. النظام يدعم الآن الإرسال كـ PDF بالإضافة للصورة
2. تم تحسين WhatsAppService لإرجاع بيانات مفصلة (message_id, status)
3. InvoiceImageService تم تحويله إلى فئة InvoiceImageGenerator
4. النظام يدعم حفظ الإعدادات كافتراضية في localStorage
5. يدعم إعادة الإرسال للإشعارات الفاشلة (حتى 3 مرات)

## 🆘 الدعم

لأي استفسارات أو مشاكل، يرجى التواصل مع فريق التطوير.