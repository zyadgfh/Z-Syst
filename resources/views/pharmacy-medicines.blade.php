<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الأدوية</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 24px; background: #f8fafc; color: #0f172a; }
        .panel { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 10px; border-bottom: 1px solid #e2e8f0; text-align: right; }
        .btn { display: inline-block; background: #2563eb; color: white; padding: 8px 12px; border-radius: 8px; text-decoration: none; margin-bottom: 12px; }
    </style>
</head>
<body>
    <div class="panel">
        <h2>إدارة الأدوية</h2>
        @include('components.pharmacy-nav')
        <a class="btn" href="/pharmacy-dashboard">العودة للوحة التحكم</a>
        <table>
            <thead>
                <tr>
                    <th>الاسم</th>
                    <th>الكمية</th>
                    <th>الحد الأدنى</th>
                </tr>
            </thead>
            <tbody>
                @foreach(($medicines ?? []) as $medicine)
                    <tr>
                        <td>{{ $medicine['name'] ?? '-' }}</td>
                        <td>{{ $medicine['stock'] ?? 0 }}</td>
                        <td>{{ $medicine['minimum_stock'] ?? 0 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
