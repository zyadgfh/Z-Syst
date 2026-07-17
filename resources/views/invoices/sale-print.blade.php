<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فاتورة مبيعات - {{ $invoice_number }}</title>
    <style>
        @media print {
            body { margin: 0; }
            .no-print { display: none !important; }
            .invoice-container { 
                box-shadow: none; 
                border: none;
                width: 100%;
                padding: 0;
            }
        }
        
        body {
            font-family: 'Arial', sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .invoice-header {
            text-align: center;
            border-bottom: 2px solid #667eea;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        
        .invoice-title {
            color: #667eea;
            font-size: 28px;
            font-weight: bold;
            margin: 0;
        }
        
        .invoice-number {
            color: #4a5568;
            font-size: 18px;
            margin: 5px 0;
        }
        
        .invoice-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .detail-group {
            margin: 10px 0;
        }
        
        .detail-label {
            color: #718096;
            font-size: 14px;
        }
        
        .detail-value {
            color: #2d3748;
            font-weight: bold;
            font-size: 16px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .items-table th {
            background: #667eea;
            color: white;
            padding: 12px;
            text-align: right;
        }
        
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .items-table tr:hover {
            background: #f7fafc;
        }
        
        .total-section {
            text-align: left;
            margin-top: 30px;
        }
        
        .total-row {
            display: flex;
            justify-content: flex-end;
            padding: 8px 0;
            font-size: 16px;
        }
        
        .total-label {
            width: 150px;
            color: #4a5568;
        }
        
        .total-value {
            width: 150px;
            text-align: left;
            font-weight: bold;
        }
        
        .grand-total {
            color: #667eea;
            font-size: 20px;
            border-top: 2px solid #667eea;
            padding-top: 10px;
        }
        
        .invoice-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px dashed #cbd5e0;
            text-align: center;
            color: #718096;
            font-size: 14px;
        }
        
        .action-buttons {
            text-align: center;
            margin: 30px 0;
        }
        
        .btn {
            padding: 12px 25px;
            margin: 0 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .btn-print {
            background: #667eea;
            color: white;
        }
        
        .btn-print:hover {
            background: #5a67d8;
        }
        
        .btn-whatsapp {
            background: #25D366;
            color: white;
        }
        
        .btn-whatsapp:hover {
            background: #1da850;
        }
    </style>
</head>
<body>
    <div class="invoice-container" id="invoice">
        <div class="invoice-header">
            <h1 class="invoice-title">فاتورة مبيعات</h1>
            <p class="invoice-number">رقم الفاتورة: {{ $invoice_number }}</p>
        </div>
        
        <div class="invoice-details">
            <div>
                <div class="detail-group">
                    <div class="detail-label">التاريخ</div>
                    <div class="detail-value">{{ $date }}</div>
                </div>
                <div class="detail-group">
                    <div class="detail-label">العميل</div>
                    <div class="detail-value">{{ $customer }}</div>
                </div>
                @if($customer_phone)
                <div class="detail-group">
                    <div class="detail-label">رقم الهاتف</div>
                    <div class="detail-value">{{ $customer_phone }}</div>
                </div>
                @endif
            </div>
            <div>
                <div class="detail-group">
                    <div class="detail-label">طريقة الدفع</div>
                    <div class="detail-value">{{ $payment_method ?? 'نقداً' }}</div>
                </div>
                @if(isset($company))
                <div class="detail-group">
                    <div class="detail-label">الشركة</div>
                    <div class="detail-value">{{ $company->name ?? '' }}</div>
                </div>
                @endif
            </div>
        </div>
        
        <table class="items-table">
            <thead>
                <tr>
                    <th>الصنف</th>
                    <th>الكمية</th>
                    <th>السعر</th>
                    <th>الإجمالي</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td>{{ is_object($item) ? $item->name : ($item['name'] ?? 'منتج') }}</td>
                    <td>{{ is_object($item) ? $item->quantity : ($item['quantity'] ?? 1) }}</td>
                    <td>{{ number_format(is_object($item) ? $item->unit_price : ($item['price'] ?? 0), 2) }} ج.م</td>
                    <td>{{ number_format(is_object($item) ? $item->line_total : ($item['line_total'] ?? 0), 2) }} ج.م</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <div class="total-section">
            @if($subtotal)
            <div class="total-row">
                <span class="total-label">المجموع الفرعي</span>
                <span class="total-value">{{ number_format($subtotal, 2) }} ج.م</span>
            </div>
            @endif
            @if($tax_amount)
            <div class="total-row">
                <span class="total-label">الضريبة</span>
                <span class="total-value">{{ number_format($tax_amount, 2) }} ج.م</span>
            </div>
            @endif
            <div class="total-row grand-total">
                <span class="total-label">الإجمالي الكلي</span>
                <span class="total-value">{{ number_format($total_amount, 2) }} ج.م</span>
            </div>
        </div>
        
        @if($notes)
        <div class="detail-group" style="margin-top: 20px;">
            <div class="detail-label">ملاحظات</div>
            <div class="detail-value">{{ $notes }}</div>
        </div>
        @endif
        
        <div class="action-buttons">
            <button onclick="printInvoice()" class="btn btn-print no-print">🖨️ طباعة الفاتورة</button>
            @if($customer_phone)
            <button onclick="sendWhatsApp(event)" class="btn btn-whatsapp no-print">📱 إرسال عبر الواتساب</button>
            @endif
        </div>
        
        <div class="invoice-footer">
            <p>شكراً لتعاملكم معنا</p>
            <p>تم إنشاء هذه الفاتورة إلكترونياً</p>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script>
        function printInvoice() {
            window.print();
        }
        
        function sendWhatsApp(event) {
            const invoiceElement = document.getElementById('invoice');
            const customerPhone = '{{ $customer_phone }}';

            // Show loading
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = 'جاري الإرسال...';
            btn.disabled = true;
            
            html2canvas(invoiceElement, {
                scale: 2,
                useCORS: true,
                allowTaint: true
            }).then(canvas => {
                canvas.toBlob(blob => {
                    const formData = new FormData();
                    formData.append('invoice_image', blob, 'invoice.jpg');
                    formData.append('customer_phone', customerPhone);
                    formData.append('invoice_id', {{ $sale ? $sale->id : 0 }});
                    
                    fetch('/api/v1/send-invoice-whatsapp', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('✅ تم إرسال الفاتورة عبر الواتساب بنجاح');
                        } else {
                            alert('❌ حدث خطأ أثناء الإرسال: ' + (data.message || 'حاول مرة أخرى'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('❌ حدث خطأ أثناء الإرسال');
                    })
                    .finally(() => {
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    });
                }, 'image/jpeg', 0.9);
            });
        }
    </script>
</body>
</html>