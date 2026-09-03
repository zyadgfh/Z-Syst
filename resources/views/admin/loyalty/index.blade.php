@extends('layouts.admin')

@section('title')
    {{ __('loyalty.Loyalty & CRM') }}
@endsection

@section('main_content')
    <div class="container-fluid m-h-100">
        <!-- Loyalty Stats -->
        <div class="gpt-dashboard-card counter-grid-4 mt-30 mb-30">
            <div class="couter-box">
                <div class="content-side">
                    <h5 id="total_members">0</h5>
                    <p>{{ __('loyalty.Total Members') }}</p>
                </div>
                <div class="icons">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17 21V19C17 17.9391 16.5786 16.9217 15.8284 16.1716C15.0783 15.4214 14.0609 15 13 15H5C3.93913 15 2.92172 15.4214 2.17157 16.1716C1.42143 16.9217 1 17.9391 1 19V21" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M9 7C11.2091 7 13 5.20914 13 3C13 0.790861 11.2091 -1 9 -1C6.79086 -1 5 0.790861 5 3C5 5.20914 6.79086 7 9 7Z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M23 21V19C22.9993 18.1137 22.7044 17.2528 22.1614 16.5523C21.6184 15.8519 20.8581 15.3516 20 15.13" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M16 3.13C16.8604 3.35031 17.623 3.85071 18.1676 4.55232C18.7122 5.25392 19.0078 6.11683 19.0078 7.005C19.0078 7.89318 18.7122 8.75608 18.1676 9.45769C17.623 10.1593 16.8604 10.6597 16 10.88" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <div class="couter-box">
                <div class="content-side">
                    <h5 id="active_programs">0</h5>
                    <p>{{ __('loyalty.Active Programs') }}</p>
                </div>
                <div class="icons">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20 12V8H6C4.89543 8 4 7.10457 4 6C4 4.89543 4.89543 4 6 4H20" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M4 6V20C4 21.1046 4.89543 22 6 22H20" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M12 11L12 17" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M9 14L12 17L15 14" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <div class="couter-box">
                <div class="content-side">
                    <h5 id="points_issued">0</h5>
                    <p>{{ __('loyalty.Points Issued') }}</p>
                </div>
                <div class="icons">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2V20M12 2L8 6M12 2L16 6M4 22H20" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
            <div class="couter-box">
                <div class="content-side">
                    <h5 id="points_redeemed">0</h5>
                    <p>{{ __('loyalty.Points Redeemed') }}</p>
                </div>
                <div class="icons">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M6 9L12 15L18 9" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body text-center p-4">
                        <div class="display-4 mb-2" style="color: var(--color-primary);">
                            <i class="fas fa-gift"></i>
                        </div>
                        <h5>{{ __('loyalty.Loyalty Programs') }}</h5>
                        <p class="text-muted small">{{ __('loyalty.Manage programs') }}</p>
                        <a href="{{ route('admin.loyalty.programs') }}" class="btn btn-primary btn-sm">{{ __('loyalty.View Programs') }}</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body text-center p-4">
                        <div class="display-4 mb-2" style="color: var(--color-accent);">
                            <i class="fas fa-history"></i>
                        </div>
                        <h5>{{ __('loyalty.Transactions') }}</h5>
                        <p class="text-muted small">{{ __('loyalty.Point redemption history') }}</p>
                        <a href="{{ route('admin.loyalty.transactions') }}" class="btn btn-primary btn-sm">{{ __('loyalty.View Transactions') }}</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100">
                    <div class="card-body text-center p-4">
                        <div class="display-4 mb-2" style="color: var(--color-secondary);">
                            <i class="fas fa-comments"></i>
                        </div>
                        <h5>{{ __('loyalty.Customer Interactions') }}</h5>
                        <p class="text-muted small">{{ __('loyalty.Track interactions') }}</p>
                        <a href="{{ route('admin.loyalty.interactions') }}" class="btn btn-primary btn-sm">{{ __('loyalty.View Interactions') }}</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expiring Points Widget -->
        <div class="row g-3 mb-4">
            <div class="col-12">
                <div class="card" class="card-clean">
                    <div class="card-body" style="padding: 0;">
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 16px 20px; border-bottom: 1px solid #f0f0f2; background: linear-gradient(135deg, #fff3e0 0%, #fff8e1 100%);">
                            <div class="row-cell">
                                <span style="font-size: 24px;">⏰</span>
                                <div>
                                    <h5 style="margin: 0; font-size: 16px; font-weight: 700; color: #1d1d1f;">نقاط على وشك الانتهاء</h5>
                                    <p style="margin: 0; font-size: 12px; color: #86868b;">النقاط التي ستنتهي خلال 30 يوماً</p>
                                </div>
                            </div>
                            <button onclick="loadExpiringPoints()" style="background: #ff9500; color: #fff; border: none; border-radius: 8px; padding: 6px 14px; font-size: 13px; font-weight: 600; cursor: pointer; transition: transform 150ms ease;">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-align"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg>
                                تحديث
                            </button>
                        </div>
                        <div id="expiringPointsList" class="p-8-0">
                            <div style="padding: 20px; text-align: center; color: #86868b; font-size: 13px;">جاري التحميل...</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Analytics Link -->
        <div class="row g-3 mb-4">
            <div class="col-12">
                <a href="{{ route('admin.loyalty.analytics') }}" style="display: flex; align-items: center; gap: 12px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border-radius: 14px; padding: 16px 20px; text-decoration: none; transition: transform 150ms ease;" onmouseenter="this.style.transform='scale(1.01)'" onmouseleave="this.style.transform='scale(1)'">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                    <div>
                        <div style="font-weight: 700; font-size: 15px;">تحليلات نقاط الولاء</div>
                        <div style="font-size: 12px; opacity: 0.8;">عرض الرسوم البيانية والاتجاهات الشهرية</div>
                    </div>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: auto;"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>

        <!-- Top Customers -->
        <div class="erp-table-section">
            <div class="card">
                <div class="card-bodys">
                    <div class="chart-header p-16 border-0">
                        <h4>{{ __('loyalty.Top Loyalty Customers') }}</h4>
                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ route('admin.loyalty.customers') }}" class="view-btn">
                                {{ __('common.View All') }} <i class="fas fa-arrow-right view-arrow"></i>
                            </a>
                        </div>
                    </div>
                    <div class="erp-box-content">
                        <div class="table-container">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th class="table-header-content">{{ __('common.SL') }}.</th>
                                        <th class="table-header-content">{{ __('common.Customer') }}</th>
                                        <th class="table-header-content">{{ __('loyalty.Points Balance') }}</th>
                                        <th class="table-header-content">{{ __('loyalty.Tier') }}</th>
                                        <th class="table-header-content">{{ __('loyalty.Total Spent') }}</th>
                                        <th class="table-header-content">{{ __('loyalty.Joined') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($topCustomers ?? [] as $customer)
                                        <tr class="table-content">
                                            <td class="table-single-content">{{ $loop->index + 1 }}</td>
                                            <td class="table-single-content">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="customer-avatar">
                                                        @if($customer->image)
                                                            <img src="{{ asset($customer->image) }}" alt="{{ $customer->name }}">
                                                        @else
                                                            <div class="avatar-placeholder">{{ substr($customer->name, 0, 1) }}</div>
                                                        @endif
                                                    </div>
                                                    <div>
                                                        <strong>{{ $customer->name }}</strong>
                                                        <small class="d-block text-muted">{{ $customer->email }}</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="table-single-content">
                                                <span class="badge-soft-success">{{ $customer->points_balance }}</span>
                                            </td>
                                            <td class="table-single-content">
                                                <span class="badge @if($customer->tier == 'gold') bg-warning @elseif($customer->tier == 'silver') bg-secondary @else badge-soft-info @endif">
                                                    {{ ucfirst($customer->tier) }}
                                                </span>
                                            </td>
                                            <td class="table-single-content">{{ format_currency($customer->total_spent) }}</td>
                                            <td class="table-single-content">{{ formatted_date($customer->created_at) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Initialize counters
                animateCounter('total_members', {{ $totalMembers ?? 0 }});
                animateCounter('active_programs', {{ $activePrograms ?? 0 }});
                animateCounter('points_issued', {{ $pointsIssued ?? 0 }});
                animateCounter('points_redeemed', {{ $pointsRedeemed ?? 0 }});
            });

            function animateCounter(elementId, targetValue) {
                const element = document.getElementById(elementId);
                if (!element) return;
                
                let currentValue = 0;
                const increment = targetValue / 50;
                const timer = setInterval(() => {
                    currentValue += increment;
                    if (currentValue >= targetValue) {
                        element.textContent = targetValue;
                        clearInterval(timer);
                    } else {
                        element.textContent = Math.floor(currentValue);
                    }
                }, 30);
            }

            // ── Expiring Points Widget ──
            function loadExpiringPoints() {
                var listEl = document.getElementById('expiringPointsList');
                if (!listEl) return;
                listEl.innerHTML = '<div class="js-loading-state">جاري التحميل...</div>';

                fetch('{{ route("admin.loyalty.expiring-soonest") }}?days=90', {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (!data.success || !data.data || data.data.length === 0) {
                        listEl.innerHTML = '<div class="js-empty-state">✅ لا توجد نقاط على وشك الانتهاء حالياً</div>';
                        return;
                    }

                    var html = '<div class="js-overflow-x">';
                    html += '<table class="table table-hover js-table-compact">';
                    html += '<thead class="js-table-header-bg">';
                    html += '<tr>';
                    html += '<th class="tab-btn-xs">العميل</th>';
                    html += '<th class="tab-btn-xs">النقاط المهددة</th>';
                    html += '<th class="tab-btn-xs">تنتهي خلال</th>';
                    html += '<th class="tab-btn-xs">إجراء</th>';
                    html += '</tr></thead><tbody>';

                    data.data.forEach(function(item) {
                        var user = item.user;
                        var daysColor = item.days_left <= 7 ? '#ff3b30' : (item.days_left <= 14 ? '#ff9500' : '#ffcc00');
                        html += '<tr class="js-table-row">';
                        html += '<td class="js-table-cell">';
                        html += '<div class="js-flex-row">';
                        if (user && user.image) {
                            html += '<img src="/storage/' + user.image + '" class="js-avatar-img">';
                        } else {
                            html += '<div class="js-avatar-sm">' + (user ? user.name.charAt(0) : '?') + '</div>';
                        }
                        html += '<div><div style="font-weight:600;color:#1d1d1f;">' + (user ? user.name : 'غير معروف') + '</div>';
                        html += '<div style="font-size:11px;color:#86868b;">' + (user ? user.email : '') + '</div></div>';
                        html += '</div></td>';
                        html += '<td class="js-table-cell-center">';
                        html += '<span class="js-badge-orange">' + item.total_points + ' نقطة</span>';
                        html += '</td>';
                        html += '<td class="js-table-cell-center">';
                        html += '<span style="color:' + daysColor + ';font-weight:700;">' + item.days_left + ' يوم</span>';
                        html += '</td>';
                        html += '<td class="js-table-cell-center">';
                        html += '<button onclick="sendExpiryReminder(' + item.user_id + ', this)" class="js-btn-primary-sm">';
                        html += '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="js-spinner-svg"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>';
                        html += 'إرسال تذكير</button>';
                        html += '</td></tr>';
                    });

                    html += '</tbody></table></div>';
                    listEl.innerHTML = html;
                })
                .catch(function() {
                    listEl.innerHTML = '<div class="js-error-state">فشل تحميل البيانات</div>';
                });
            }

            function sendExpiryReminder(userId, btn) {
                var originalText = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="js-spinning"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg> جاري الإرسال...';
                btn.style.opacity = '0.7';

                fetch('{{ route("admin.loyalty.send-expiry-reminder") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ user_id: userId }),
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        btn.style.background = '#34c759';
                        btn.innerHTML = '✓ تم الإرسال';
                        setTimeout(function() {
                            btn.innerHTML = originalText;
                            btn.style.background = '#007aff';
                            btn.disabled = false;
                            btn.style.opacity = '1';
                        }, 2500);
                    } else {
                        alert(data.message || 'فشل الإرسال');
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                        btn.style.opacity = '1';
                    }
                })
                .catch(function() {
                    alert('حدث خطأ في الاتصال');
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                    btn.style.opacity = '1';
                });
            }

            // Load expiring points on page load
            setTimeout(loadExpiringPoints, 500);
        </script>
    @endpush
@endsection

