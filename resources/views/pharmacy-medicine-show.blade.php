<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تفاصيل الدواء: {{ $medicine->generic_name }}</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 24px; background: #f8fafc; color: #0f172a; }
        .panel { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); max-width: 800px; margin: 0 auto; }
        h1 { margin-top: 0; color: #2563eb; }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-top: 20px; }
        .info-item label { font-weight: bold; color: #64748b; font-size: 12px; display: block; margin-bottom: 4px; }
        .info-item span { font-size: 16px; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .btn { display: inline-block; background: #2563eb; color: white; padding: 8px 12px; border-radius: 8px; text-decoration: none; margin: 2px; }
        .btn-secondary { background: #64748b; }
        .section { margin-top: 24px; padding-top: 16px; border-top: 1px solid #e2e8f0; }
    </style>
</head>
<body>
    <div class="panel">
        <h1>{{ $medicine->generic_name }}</h1>

        <div class="info-grid">
            <div class="info-item">
                <label>الاسم التجاري</label>
                <span>{{ $medicine->brand_name ?? '-' }}</span>
            </div>
            <div class="info-item">
                <label>اسم المنتج</label>
                <span>{{ $medicine->product_name ?? '-' }}</span>
            </div>
            <div class="info-item">
                <label>الشكل الصيدلي</label>
                <span>{{ $medicine->dosage_form ?? '-' }}</span>
            </div>
            <div class="info-item">
                <label>القوة/التركيز</label>
                <span>{{ $medicine->strength ?? '-' }}</span>
            </div>
            <div class="info-item">
                <label>الفئة</label>
                <span>{{ $medicine->category->categoryName ?? 'غير محدد' }}</span>
            </div>
            <div class="info-item">
                <label>الشركة المصنعة</label>
                <span>{{ $medicine->manufacturer->name ?? 'غير محدد' }}</span>
            </div>
            <div class="info-item">
                <label>كود المنتج</label>
                <span>{{ $medicine->product_code ?? '-' }}</span>
            </div>
            <div class="info-item">
                <label>الباركود</label>
                <span>{{ $medicine->barcode ?? '-' }}</span>
            </div>
            <div class="info-item">
                <label>سعر الشراء</label>
                <span>{{ $medicine->purchase_price ?? 0 }} ج.م</span>
            </div>
            <div class="info-item">
                <label>سعر البيع</label>
                <span>{{ $medicine->sales_price ?? 0 }} ج.م</span>
            </div>
            <div class="info-item">
                <label>سعر الجملة</label>
                <span>{{ $medicine->wholesale_price ?? 0 }} ج.م</span>
            </div>
            <div class="info-item">
                <label>أدنى مخزون</label>
                <span>{{ $medicine->min_stock ?? 0 }}</span>
            </div>
            <div class="info-item">
                <label>مستوى إعادة الطلب</label>
                <span>{{ $medicine->reorder_level ?? 0 }}</span>
            </div>
            <div class="info-item">
                <label>عمر الصلاحية</label>
                <span>{{ $medicine->shelf_life_months ? $medicine->shelf_life_months . ' شهر' : '-' }}</span>
            </div>
        </div>

        <!-- الحالة والتحكم -->
        <div class="info-grid" style="margin-top: 16px;">
            <div class="info-item">
                <label>الحالة</label>
                @if($medicine->is_active)
                    <span class="badge badge-success">نشط</span>
                @else
                    <span class="badge badge-danger">غير نشط</span>
                @endif
            </div>
            <div class="info-item">
                <label>يتطلب وصفة طبية</label>
                @if($medicine->prescription_required)
                    <span class="badge badge-danger">نعم</span>
                @else
                    <span class="badge badge-success">لا</span>
                @endif
            </div>
            <div class="info-item">
                <label>مادة محكمة التحكم</label>
                @if($medicine->controlled_substance_schedule)
                    <span class="badge badge-danger">جدول {{ $medicine->controlled_substance_schedule }}</span>
                @else
                    <span class="badge badge-success">لا</span>
                @endif
            </div>
            <div class="info-item">
                <label>تتبع المخزون</label>
                @if($medicine->track_inventory)
                    <span class="badge badge-info">مفعل</span>
                @else
                    <span class="badge badge-danger">غير مفعل</span>
                @endif
            </div>
        </div>

        <!-- الوصف والتعليمات -->
        @if($medicine->description)
            <div class="section">
                <h3>الوصف</h3>
                <p>{{ $medicine->description }}</p>
            </div>
        @endif

        @if($medicine->usage_instructions)
            <div class="section">
                <h3>إرشادات الاستخدام</h3>
                <p>{{ $medicine->usage_instructions }}</p>
            </div>
        @endif

        @if($medicine->side_effects)
            <div class="section">
                <h3>الآثار الجانبية</h3>
                <p>{{ $medicine->side_effects }}</p>
            </div>
        @endif

        <!-- المخزون الحالي -->
        @if($medicine->inventory && $medicine->inventory->count() > 0)
            <div class="section">
                <h3>المخزون الحالي</h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr>
                            <th style="text-align: right; padding: 8px;">رقم الدفعة</th>
                            <th style="text-align: right; padding: 8px;">الكمية</th>
                            <th style="text-align: right; padding: 8px;">تاريخ الانتهاء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($medicine->inventory as $stock)
                            <tr>
                                <td style="padding: 8px;">{{ $stock->batch_number ?? '-' }}</td>
                                <td style="padding: 8px;">{{ $stock->quantity }}</td>
                                <td style="padding: 8px;">{{ $stock->expiry_date ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="section">
            <a href="{{ route('pharmacy.medicines.edit', $medicine->id) }}" class="btn">تعديل</a>
            <a href="{{ route('pharmacy.medicines.index') }}" class="btn btn-secondary">العودة للقائمة</a>
        </div>
    </div>
</body>
</html>