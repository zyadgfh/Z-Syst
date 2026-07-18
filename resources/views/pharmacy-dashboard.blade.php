<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة الصيدلية</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f7fb; margin: 0; padding: 20px; color: #1f2937; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; }
        .card { background: white; border-radius: 12px; padding: 18px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .title { font-size: 14px; color: #6b7280; margin-bottom: 8px; }
        .value { font-size: 24px; font-weight: bold; }
        .nav { display: flex; gap: 12px; margin-bottom: 20px; }
        .nav a { text-decoration: none; color: #2563eb; font-weight: bold; }
    </style>
</head>
<body>
    <h2>لوحة تحكم الصيدلية</h2>
    @include('components.pharmacy-nav')
    <div class="nav">
        <a href="/api/v1/reports/stock">تقارير المخزون</a>
        <a href="/api/v1/reports/sales">تقارير المبيعات</a>
    </div>
    <div class="grid">
        <div class="card">
            <div class="title">الأدوية</div>
            <div class="value">{{ $medicineCount ?? 0 }}</div>
        </div>
        <div class="card">
            <div class="title">الموردين</div>
            <div class="value">{{ $supplierCount ?? 0 }}</div>
        </div>
        <div class="card">
            <div class="title">العملاء</div>
            <div class="value">{{ $customerCount ?? 0 }}</div>
        </div>
        <div class="card">
            <div class="title">المشتريات</div>
            <div class="value">{{ $purchaseCount ?? 0 }}</div>
        </div>
    </div>
</body>
</html>
