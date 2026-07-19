<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة التحكم - نظام الصيدلية</title>
    <meta name="description" content="نظام إدارة الصيدليات المتكامل - Z-Syst PharmaSync">
    <style>
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #0f766e;
            --success: #22c55e;
            --warning: #f59e0b;
            --danger: #dc2626;
            --muted: #64748b;
            --light: #f8fafc;
            --dark: #0f172a;
            --card: #ffffff;
            --border: #dbeafe;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Cairo', 'Tajawal', system-ui, sans-serif;
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            color: var(--dark);
            min-height: 100vh;
        }

        .dashboard { max-width: 1400px; margin: 0 auto; padding: 20px; }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding: 20px;
            background: var(--card);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .header h1 { margin: 0; font-size: 28px; color: var(--primary); }
        .header .badge {
            background: var(--secondary);
            color: white;
            padding: 8px 16px;
            border-radius: 999px;
            font-size: 14px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--card);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid var(--border);
            transition: transform 0.2s;
        }

        .stat-card:hover { transform: translateY(-4px); }
        .stat-card .icon { font-size: 36px; margin-bottom: 12px; }
        .stat-card .value { font-size: 32px; font-weight: 700; color: var(--primary); }
        .stat-card .label { font-size: 14px; color: var(--muted); margin-top: 8px; }

        .stat-card.warning .value { color: var(--warning); }
        .stat-card.danger .value { color: var(--danger); }
        .stat-card.success .value { color: var(--success); }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 30px;
        }

        .action-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 20px;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .action-btn:hover { background: var(--primary-dark); transform: translateY(-2px); }
        .action-btn .icon { font-size: 28px; }

        .action-btn.secondary { background: var(--secondary); }
        .action-btn.secondary:hover { background: #0d5f56; }

        .forecast-section {
            background: var(--card);
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid var(--border);
        }

        .forecast-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .forecast-title { font-size: 20px; font-weight: 700; margin: 0; }
        .refresh-btn {
            background: var(--light);
            border: 1px solid var(--border);
            padding: 8px 16px;
            border-radius: 8px;
            cursor: pointer;
        }

        .forecast-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .forecast-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px;
            border-bottom: 1px solid var(--border);
        }

        .forecast-item:last-child { border-bottom: none; }
        .forecast-item-name { font-weight: 600; }
        .forecast-item-trend {
            padding: 4px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }

        .forecast-item-trend.up { background: #dcfce7; color: #166534; }
        .forecast-item-trend.down { background: #fee2e2; color: #991b1b; }
        .forecast-item-trend.flat { background: #fef3c7; color: #92400e; }

        .confidence-badge {
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 12px;
            margin-right: 8px;
        }

        .confidence-badge.high { background: #dcfce7; color: #166534; }
        .confidence-badge.medium { background: #fef3c7; color: #92400e; }
        .confidence-badge.low { background: #fee2e2; color: #991b1b; }

        @media (max-width: 768px) {
            .header { flex-direction: column; gap: 12px; text-align: center; }
            .stats-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="header">
            <h1>🏥 لوحة التحكم - Z-Syst PharmaSync</h1>
            <span class="badge">متصل</span>
        </div>

        <div class="stats-grid">
            <div class="stat-card" id="pendingPrescriptionsCard">
                <div class="icon">📋</div>
                <div class="value" id="pendingPrescriptionsCount">0</div>
                <div class="label">الوصفات المعلقة</div>
            </div>

            <div class="stat-card warning" id="lowStockCard">
                <div class="icon">⚠️</div>
                <div class="value" id="lowStockCount">0</div>
                <div class="label">تنبيهات المخزون المنخفض</div>
            </div>

            <div class="stat-card success" id="dispensedTodayCard">
                <div class="icon">✅</div>
                <div class="value" id="dispensedTodayCount">0</div>
                <div class="label">الصرف اليومي</div>
            </div>

            <div class="stat-card" id="forecastCard">
                <div class="icon">📊</div>
                <div class="value" id="forecastCount">0</div>
                <div class="label">العناصر المتوقعة</div>
            </div>
        </div>

        <h2 style="margin-bottom: 16px; font-size: 22px;">الإجراءات السريعة</h2>
        <div class="actions-grid">
            <a href="/pharmacy/pos" class="action-btn">
                <span class="icon">💰</span>
                <span>نقاط بيع (POS)</span>
            </a>

            <a href="/admin/patients" class="action-btn secondary">
                <span class="icon">👥</span>
                <span>إدارة المرضى</span>
            </a>

            <a href="/admin/doctors" class="action-btn secondary">
                <span class="icon">👨‍⚕️</span>
                <span>إدارة الأطباء</span>
            </a>

            <a href="/admin/products" class="action-btn secondary">
                <span class="icon">💊</span>
                <span>إدارة المنتجات</span>
            </a>

            <a href="/admin/purchase-orders" class="action-btn secondary">
                <span class="icon">📦</span>
                <span>أوامر الشراء</span>
            </a>

            <a href="/pharmacy/medicines" class="action-btn secondary">
                <span class="icon">🔍</span>
                <span>البحث عن الأدوية</span>
            </a>
        </div>

        <div class="forecast-section">
            <div class="forecast-header">
                <h3 class="forecast-title">توقعات الطلب</h3>
                <button class="refresh-btn" onclick="loadDashboardData()">🔄 تحديث</button>
            </div>

            <div class="forecast-list" id="forecastList">
                <div class="muted" style="text-align: center; padding: 40px;">جاري تحميل التوقعات...</div>
            </div>
        </div>
    </div>

    <script>
        // Load dashboard data
        async function loadDashboardData() {
            try {
                const response = await fetch('/api/v1/pharmacy/pos-summary', {
                    headers: { 'Accept': 'application/json' }
                });

                if (response.ok) {
                    const data = await response.json();
                    document.getElementById('pendingPrescriptionsCount').textContent = data.pending_prescriptions_count || 0;
                    document.getElementById('lowStockCount').textContent = data.low_stock_products?.length || 0;
                    document.getElementById('forecastCount').textContent = data.forecast?.length || 0;

                    // Render forecast
                    const forecastList = document.getElementById('forecastList');
                    if (data.forecast && data.forecast.length > 0) {
                        forecastList.innerHTML = data.forecast.map(item => `
                            <div class="forecast-item">
                                <div>
                                    <span class="forecast-item-name">${item.product_name}</span>
                                    <span class="confidence-badge ${item.confidence}">${getConfidenceText(item.confidence)}</span>
                                </div>
                                <div>
                                    <span>المتوسط اليومي: ${item.average_daily_demand}</span>
                                    <span class="forecast-item-trend ${item.trend}">${getTrendText(item.trend)}</span>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        forecastList.innerHTML = '<div class="muted" style="text-align: center; padding: 20px;">لا توجد بيانات توقعات متاحة</div>';
                    }
                }
            } catch (error) {
                console.error('Failed to load dashboard data:', error);
            }
        }

        function getTrendText(trend) {
            return { up: '↑ صعود', down: '↓ هبوط', flat: '→ ثابت' }[trend] || 'غير معروف';
        }

        function getConfidenceText(confidence) {
            return { high: 'عالي', medium: 'متوسط', low: 'منخفض' }[confidence] || 'غير معروف';
        }

        // Initialize on load
        document.addEventListener('DOMContentLoaded', loadDashboardData);
    </script>
</body>
</html>