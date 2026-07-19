<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقارير الأدوية</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 24px; background: #f8fafc; color: #0f172a; }
        .panel { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); margin-bottom: 20px; }
        h1 { margin-top: 0; color: #2563eb; }
        h2 { color: #1e40af; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: #f1f5f9; padding: 16px; border-radius: 8px; text-align: center; }
        .stat-card h3 { margin: 0; font-size: 28px; color: #2563eb; }
        .stat-card p { margin: 4px 0 0; color: #64748b; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: right; }
        th { background: #f8fafc; font-weight: bold; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-info { background: #dbeafe; color: #1e40af; }
        .btn { display: inline-block; background: #2563eb; color: white; padding: 8px 12px; border-radius: 8px; text-decoration: none; margin: 2px; font-size: 13px; }
        .btn-secondary { background: #64748b; }
        .section { margin-bottom: 32px; }
    </style>
</head>
<body>
    <div class="panel">
        <h1>📊 تقارير الأدوية</h1>
        @include('components.pharmacy-nav')

        <!-- الإحصائيات العامة -->
        <div class="section">
            <h2>إحصائيات عامة</h2>
            <div class="stats-grid">
                <div class="stat-card">
                    <h3>{{ $totalMedicines ?? 0 }}</h3>
                    <p>إجمالي الأدوية</p>
                </div>
                <div class="stat-card">
                    <h3>{{ $activeMedicines ?? 0 }}</h3>
                    <p>الأدوية النشطة</p>
                </div>
                <div class="stat-card">
                    <h3>{{ $lowStockCount ?? 0 }}</h3>
                    <p>أدوية منخفضة المخزون</p>
                </div>
                <div class="stat-card">
                    <h3>{{ $expiringSoonCount ?? 0 }}</h3>
                    <p>قريبة الانتهاء (90 يوم)</p>
                </div>
                <div class="stat-card">
                    <h3>{{ $prescriptionMedicines ?? 0 }}</h3>
                    <p>تتطلب وصفة طبية</p>
                </div>
                <div class="stat-card">
                    <h3>{{ $controlledMedicines ?? 0 }}</h3>
                    <p>مواد محكمة التحكم</p>
                </div>
            </div>
        </div>

        <!-- الأدوية منخفضة المخزون -->
        <div class="section">
            <h2>⚠️ الأدوية منخفضة المخزون</h2>
            @if(!empty($lowStockMedicines) && $lowStockMedicines->count() > 0)
                <table>
                    <thead>
                        <tr>
                            <th>الاسم العلمي</th>
                            <th>الاسم التجاري</th>
                            <th>المخزون الحالي</th>
                            <th>الحد الأدنى</th>
                            <th>مستوى إعادة الطلب</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($lowStockMedicines as $medicine)
                            <tr>
                                <td>{{ $medicine->generic_name }}</td>
                                <td>{{ $medicine->brand_name ?? '-' }}</td>
                                <td>
                                    <span class="badge badge-danger">{{ $medicine->getCurrentStock() ?? 0 }}</span>
                                </td>
                                <td>{{ $medicine->min_stock ?? 0 }}</td>
                                <td>{{ $medicine->reorder_level ?? 0 }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p>لا توجد أدوية منخفضة المخزون حالياً</p>
            @endif
        </div>

        <!-- الأدوية قريبة الانتهاء -->
        <div class="section">
            <h2>⏰ الأدوية قريبة الانتهاء (خلال 90 يوم)</h2>
            @if(!empty($expiringMedicines) && $expiringMedicines->count() > 0)
                <table>
                    <thead>
                        <tr>
                            <th>الاسم العلمي</th>
                            <th>الاسم التجاري</th>
                            <th>تاريخ الانتهاء</th>
                            <th>الكمية المتبقية</th>
                            <th>أيام متبقية</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($expiringMedicines as $medicine)
                            @php
                                $daysLeft = $medicine->inventory ? \Carbon\Carbon::parse($medicine->inventory->min('expiry_date'))->diffInDays(now()) : 0;
                            @endphp
                            <tr>
                                <td>{{ $medicine->generic_name }}</td>
                                <td>{{ $medicine->brand_name ?? '-' }}</td>
                                <td>{{ $medicine->inventory ? $medicine->inventory->min('expiry_date') : '-' }}</td>
                                <td>{{ $medicine->inventory ? $medicine->inventory->sum('quantity') : 0 }}</td>
                                <td>
                                    @if($daysLeft <= 7)
                                        <span class="badge badge-danger">{{ $daysLeft }} يوم</span>
                                    @elseif($daysLeft <= 30)
                                        <span class="badge badge-warning">{{ $daysLeft }} يوم</span>
                                    @else
                                        <span class="badge badge-info">{{ $daysLeft }} يوم</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p>لا توجد أدوية قريبة الانتهاء</p>
            @endif
        </div>

        <!-- الأدوية حسب الفئات -->
        <div class="section">
            <h2>📁 الأدوية حسب الفئات</h2>
            @if(!empty($medicinesByCategory) && $medicinesByCategory->count() > 0)
                <table>
                    <thead>
                        <tr>
                            <th>الفئة</th>
                            <th>عدد الأدوية</th>
                            <th>الحد الأدنى للمخزون</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($medicinesByCategory as $category)
                            <tr>
                                <td>{{ $category->categoryName ?? 'غير مصنف' }}</td>
                                <td>{{ $category->products_count ?? 0 }}</td>
                                <td>{{ $category->products_sum_min_stock ?? 0 }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p>لا توجد بيانات فئات</p>
            @endif
        </div>

        <!-- الأدوية حسب الشركات المصنعة -->
        <div class="section">
            <h2>🏭 الأدوية حسب الشركات المصنعة</h2>
            @if(!empty($medicinesByManufacturer) && $medicinesByManufacturer->count() > 0)
                <table>
                    <thead>
                        <tr>
                            <th>الشركة المصنعة</th>
                            <th>عدد الأدوية</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($medicinesByManufacturer as $manufacturer)
                            <tr>
                                <td>{{ $manufacturer->name ?? 'غير محدد' }}</td>
                                <td>{{ $manufacturer->products_count ?? 0 }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p>لا توجد بيانات شركات مصنعة</p>
            @endif
        </div>

        <!-- أدوية محكمة التحكم -->
        <div class="section">
            <h2>🔒 مواد محكمة التحكم</h2>
            @if(!empty($controlledMedicinesList) && $controlledMedicinesList->count() > 0)
                <table>
                    <thead>
                        <tr>
                            <th>الاسم العلمي</th>
                            <th>الاسم التجاري</th>
                            <th>الجدول</th>
                            <th>سعر الشراء</th>
                            <th>سعر البيع</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($controlledMedicinesList as $medicine)
                            <tr>
                                <td>{{ $medicine->generic_name }}</td>
                                <td>{{ $medicine->brand_name ?? '-' }}</td>
                                <td>
                                    <span class="badge badge-danger">جدول {{ $medicine->controlled_substance_schedule }}</span>
                                </td>
                                <td>{{ $medicine->purchase_price ?? 0 }}</td>
                                <td>{{ $medicine->sales_price ?? 0 }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p>لا توجد أدوية محكمة التحكم مسجلة</p>
            @endif
        </div>

        <div style="margin-top: 24px;">
            <a href="/pharmacy-dashboard" class="btn btn-secondary">العودة للوحة التحكم</a>
            <a href="/pharmacy/medicines" class="btn">قائمة الأدوية</a>
        </div>
    </div>
</body>
</html>