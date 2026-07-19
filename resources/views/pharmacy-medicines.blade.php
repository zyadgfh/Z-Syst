<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة الأدوية</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 24px; background: #f8fafc; color: #0f172a; }
        .panel { background: white; border-radius: 12px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        h1 { margin-top: 0; color: #2563eb; }
        .btn { display: inline-block; background: #2563eb; color: white; padding: 8px 12px; border-radius: 8px; text-decoration: none; margin: 2px; font-size: 14px; }
        .btn-secondary { background: #64748b; }
        .btn-danger { background: #dc2626; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: right; }
        th { background: #f8fafc; font-weight: bold; }
        .actions { margin-bottom: 16px; }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        .badge-success { background: #dcfce7; color: #166534; }
        .badge-danger { background: #fee2e2; color: #991b1b; }
        .pagination { margin-top: 16px; }
        .pagination a { padding: 8px 12px; background: #f1f5f9; border-radius: 4px; text-decoration: none; margin: 0 2px; }
    </style>
</head>
<body>
    <div class="panel">
        <h1>💊 إدارة الأدوية</h1>
        @include('components.pharmacy-nav')

        <div class="actions">
            <a href="{{ route('pharmacy.medicines.create') }}" class="btn">➕ إدخال دواء جديد</a>
            <a href="{{ route('pharmacy.medicines.reports') }}" class="btn btn-secondary">📊 التقارير</a>
            <a href="/pharmacy-dashboard" class="btn btn-secondary">العودة للوحة التحكم</a>
        </div>

        @if(session('success'))
            <div style="background: #dcfce7; color: #166534; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px;">
                {{ session('success') }}
            </div>
        @endif

        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>الاسم العلمي</th>
                    <th>الاسم التجاري</th>
                    <th>الشكل الصيدلي</th>
                    <th>القوة</th>
                    <th>سعر البيع</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                @forelse($medicines as $medicine)
                    <tr>
                        <td>{{ $medicine->id }}</td>
                        <td>{{ $medicine->generic_name ?? '-' }}</td>
                        <td>{{ $medicine->brand_name ?? '-' }}</td>
                        <td>{{ $medicine->dosage_form ?? '-' }}</td>
                        <td>{{ $medicine->strength ?? '-' }}</td>
                        <td>{{ $medicine->sales_price ?? $medicine->purchase_price ?? 0 }} ج.م</td>
                        <td>
                            @if($medicine->is_active)
                                <span class="badge badge-success">نشط</span>
                            @else
                                <span class="badge badge-danger">غير نشط</span>
                            @endif
                            @if($medicine->prescription_required)
                                <span class="badge badge-danger" style="margin-right: 4px;">وصفة طبية</span>
                            @endif
                        </td>
                        <td>
                            <a href="{{ route('pharmacy.medicines.show', $medicine->id) }}" class="btn btn-secondary">عرض</a>
                            <a href="{{ route('pharmacy.medicines.edit', $medicine->id) }}" class="btn">تعديل</a>
                            <form method="POST" action="{{ route('pharmacy.medicines.destroy', $medicine->id) }}" style="display: inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذا الدواء؟')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger">حذف</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 20px;">لا توجد أدوية مسجلة</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($medicines->hasPages())
            <div class="pagination">
                {{ $medicines->links() }}
            </div>
        @endif
    </div>
</body>
</html>