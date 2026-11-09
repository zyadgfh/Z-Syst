# فواتير PDF مجانية — بدون نظام ضريبي

> اخترت "فاتورة PDF مجانية فقط" — لا فواتير ضريبية

## التنفيذ صفر تكلفة
- **المكتبة:** `barryvdh/laravel-dompdf` مجانية (موجودة؟) أو `jsPDF` في المتصفح مجاني
- **القالب:** Blade بسيط → PDF → ينزل على جهاز الصيدلي

## الكود (متصفح، صفر سيرفر)
```javascript
// في صفحة الفاتورة
function printInvoice(sale) {
  const doc = new jsPDF();
  doc.text(`فاتورة #${sale.invoice_number}`, 10, 10);
  doc.text(`الإجمالي: ${sale.total_amount} جنيه`, 10, 20);
  sale.items.forEach((item, i) => doc.text(`${item.product_name} - ${item.price}`, 10, 30+i*10));
  doc.save(`invoice-${sale.invoice_number}.pdf`);
}
```

## ماذا عن الفاتورة الإلكترونية المصرية لاحقاً؟
- اترك حقل `eta_invoices` موجود لكن لا تفعّله الآن
- عندما تربح، فعّل Eta عبر Edge Function مجانية

## الخلاصة
- PDF في المتصفح = 0 Storage، 0 تكلفة
- لا تحتاج سجل تجاري الآن — إيصال بسيط يكفي للـ 100 الأوائل
