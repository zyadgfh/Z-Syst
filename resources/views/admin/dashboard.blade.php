<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Pharmacy Management</title>
    <link rel="stylesheet" href="{{ asset('css/pharmacy-pos.css') }}">
    <style>
        :root { 
            --primary: #2563eb; 
            --accent: #0f766e; 
            --danger: #dc2626; 
            --warning: #f59e0b;
            --success: #22c55e;
            --info: #0ea5e9;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            border: 1px solid var(--border);
            box-shadow: 0 4px 16px rgba(15, 23, 42, 0.08);
            transition: all 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.12);
        }
        
        .stat-card .icon {
            font-size: 32px;
            margin-bottom: 12px;
        }
        
        .stat-card .value {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 4px;
        }
        
        .stat-card .label {
            color: var(--muted);
            font-size: 14px;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-top: 24px;
        }
        
        .quick-action {
            background: var(--primary);
            color: white;
            text-decoration: none;
            padding: 16px;
            border-radius: 12px;
            text-align: center;
            transition: all 0.2s;
        }
        
        .quick-action:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
        }
        
        .quick-action.danger {
            background: var(--danger);
        }
        
        .quick-action.danger:hover {
            background: #b91c1c;
        }
        
        .quick-action.accent {
            background: var(--accent);
        }
        
        .quick-action.accent:hover {
            background: #0d5f56;
        }
        
        .recent-table {
            width: 100%;
            margin-top: 16px;
        }
        
        .recent-table th,
        .recent-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }
        
        .badge {
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="app-shell" style="max-width: 1400px; margin: 0 auto; padding: 20px;">
        <div class="header">
            <div>
                <h1>Pharmacy Management Dashboard</h1>
                <p>Welcome back! Here's your pharmacy at a glance.</p>
            </div>
            <div class="hero-badge">Admin Panel</div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon">💊</div>
                <div class="value" id="totalProducts">0</div>
                <div class="label">Total Products</div>
            </div>
            <div class="stat-card">
                <div class="icon">📋</div>
                <div class="value" id="totalPrescriptions">0</div>
                <div class="label">Prescriptions Today</div>
            </div>
            <div class="stat-card">
                <div class="icon">💰</div>
                <div class="value" id="totalSales">$0</div>
                <div class="label">Sales Today</div>
            </div>
            <div class="stat-card">
                <div class="icon">⚠️</div>
                <div class="value" id="lowStockCount">0</div>
                <div class="label">Low Stock Alerts</div>
            </div>
        </div>

        <div class="quick-actions">
            <a href="{{ route('pharmacy-pos') }}" class="quick-action">🏪 Point of Sale</a>
            <a href="{{ url('/admin/products') }}" class="quick-action">📦 Manage Products</a>
            <a href="{{ url('/admin/patients') }}" class="quick-action">👥 Manage Patients</a>
            <a href="{{ url('/admin/doctors') }}" class="quick-action">👨‍⚕️ Manage Doctors</a>
            <a href="{{ url('/admin/purchase-orders') }}" class="quick-action">📥 Purchase Orders</a>
            <a href="{{ url('/admin/stock-transfers') }}" class="quick-action">🔄 Stock Transfers</a>
            <a href="{{ url('/admin/insurance-claims') }}" class="quick-action">🏥 Insurance Claims</a>
            <a href="{{ url('/admin/reports') }}" class="quick-action accent">📊 View Reports</a>
        </div>

        <div class="card" style="margin-top: 24px;">
            <div class="card-header">
                <h3 class="card-title">Recent Prescriptions</h3>
                <a href="{{ url('/admin/prescriptions') }}" class="text-sm">View All →</a>
            </div>
            <div class="table-container">
                <table class="recent-table">
                    <thead>
                        <tr>
                            <th>Number</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody id="recentPrescriptions">
                        <tr>
                            <td colspan="5" class="text-center text-muted">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card" style="margin-top: 20px;">
            <div class="card-header">
                <h3 class="card-title">Low Stock Products</h3>
                <a href="{{ url('/admin/products/low-stock') }}" class="text-sm">View All →</a>
            </div>
            <div class="table-container">
                <table class="recent-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Stock</th>
                            <th>Alert Qty</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="lowStockProducts">
                        <tr>
                            <td colspan="4" class="text-center text-muted">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Load dashboard data
        async function loadDashboardStats() {
            try {
                const response = await fetch('/api/v1/dashboard', {
                    headers: { 
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    
                    if (data.total_products !== undefined) {
                        document.getElementById('totalProducts').textContent = data.total_products;
                    }
                    if (data.total_prescriptions !== undefined) {
                        document.getElementById('totalPrescriptions').textContent = data.total_prescriptions;
                    }
                    if (data.total_sales !== undefined) {
                        document.getElementById('totalSales').textContent = '$' + Number(data.total_sales).toFixed(2);
                    }
                    if (data.low_stock_count !== undefined) {
                        document.getElementById('lowStockCount').textContent = data.low_stock_count;
                    }
                    
                    if (data.recent_prescriptions) {
                        renderPrescriptions(data.recent_prescriptions);
                    }
                    
                    if (data.low_stock_products) {
                        renderLowStockProducts(data.low_stock_products);
                    }
                }
            } catch (error) {
                console.error('Failed to load dashboard:', error);
                // Show demo data
                document.getElementById('totalProducts').textContent = '156';
                document.getElementById('totalPrescriptions').textContent = '24';
                document.getElementById('totalSales').textContent = '$1,250.00';
                document.getElementById('lowStockCount').textContent = '3';
            }
        }

        function renderPrescriptions(prescriptions) {
            const tbody = document.getElementById('recentPrescriptions');
            if (!prescriptions || prescriptions.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted">No prescriptions found</td></tr>';
                return;
            }
            
            tbody.innerHTML = prescriptions.map(p => `
                <tr>
                    <td>${p.prescription_number || '#' + p.id}</td>
                    <td>${p.patient?.name || 'Unknown'}</td>
                    <td>${p.doctor?.name || 'Unknown'}</td>
                    <td><span class="badge" style="background: ${getStatusColor(p.status)}; color: white;">${p.status}</span></td>
                    <td>${new Date(p.created_at).toLocaleDateString()}</td>
                </tr>
            `).join('');
        }

        function renderLowStockProducts(products) {
            const tbody = document.getElementById('lowStockProducts');
            if (!products || products.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center text-muted">No low stock alerts</td></tr>';
                return;
            }
            
            tbody.innerHTML = products.map(p => `
                <tr>
                    <td>${p.product?.productName || p.name}</td>
                    <td>${p.productStock ?? 0}</td>
                    <td>${p.product?.alert_qty ?? 0}</td>
                    <td><span class="badge" style="background: var(--danger); color: white;">Low Stock</span></td>
                </tr>
            `).join('');
        }

        function getStatusColor(status) {
            const colors = {
                pending: '#fef9c3',
                partially_dispensed: '#bfdbfe',
                dispensed: '#dcfce7',
                cancelled: '#fee2e2',
                expired: '#e5e7eb'
            };
            return colors[status] || '#e5e7eb';
        }

        // Load on page ready
        document.addEventListener('DOMContentLoaded', loadDashboardStats);
    </script>
</body>
</html>