<div class="header-bg sticky-top">
    <header class="main-header-section">
        <div class="header-wrapper">
            <div class="header-left">
                <div class="sidebar-opener"><i class="fal fa-bars" aria-hidden="true"></i></div>
                <!-- Z-Syst Logo -->
                <a href="{{ route('admin.dashboard.index') }}" class="logo-container">
                    <img src="{{ asset('logo.png') }}" alt="Z-Syst Pharmacy Management" class="logo logo-small">
                </a>
                <a target="_blank" class="view-website" href="{{ route('home') }}">
                    {{ __('View Website') }}
                    <i class="fas fa-chevron-double-right"></i>
                </a>
            </div>

            <div class="header-middle"></div>
            <div class="header-right">
                <div class="language-change">
                    <div class="dropdown">
                        <button class="btn language-dropdown dropdown-toggle language-btn border-0" type="button" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            <img src="{{ asset('flags/' . languages()[app()->getLocale()]['flag'] . '.svg') }}" alt="" class="flag-icon">
                        </button>
                        <ul class="dropdown-menu dropdown-menu-scroll">
                            @foreach (languages() as $key => $language)
                                <li class="language-li">
                                    <a class="dropdown-item" href="{{ request()->fullUrlWithQuery(['lang' => $key]) }}">
                                        <div class="language-img-container">
                                            <img src="{{ asset('flags/' . $language['flag'] . '.svg') }}" alt=""
                                                class="flag-icon me-2">
                                            {{ $language['name'] }}
                                        </div>
                                    </a>
                                    @if (app()->getLocale() == $key)
                                        <i class="fas fa-check language-check"></i>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-center h-100">
                    {{-- Inventory Alerts Bell --}}
                    <div class="inventory-alerts-bell dropdown me-3" style="position: relative;">
                        <a href="#" class="drop-inventory-alerts-controller mt-1" data-bs-toggle="dropdown" style="text-decoration: none; position: relative; display: inline-block;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#e65100" stroke-width="2">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                <line x1="12" y1="9" x2="12" y2="13"/>
                                <line x1="12" y1="17" x2="12.01" y2="17"/>
                            </svg>
                            <span id="inventoryAlertCount" class="badge-soft-danger position-absolute d-flex align-items-center justify-content-center" style="display: none;"></span>
                        </a>
                        <div class="dropdown-menu" style="min-width: 340px; max-height: 420px; overflow-y: auto;">
                            <div class="notification-header" style="display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; border-bottom: 1px solid #f0f0f2;">
                                <p style="margin: 0; font-size: 14px;">
                                    {{ __('Inventory Alerts') }}
                                    <strong id="inventoryAlertTotalBadge"></strong>
                                </p>
                                <a href="{{ route('admin.inventory-alerts.index') }}" style="font-size: 12px; color: #007aff; text-decoration: none; font-weight: 600;">
                                    {{ __('View all') }}
                                </a>
                            </div>
                            <ul id="inventoryAlertList" style="list-style: none; padding: 0; margin: 0;">
                                <li style="padding: 16px; text-align: center; color: #86868b; font-size: 13px;">{{ __('Loading...') }}</li>
                            </ul>
                            <div style="padding: 10px 16px; border-top: 1px solid #f0f0f2; text-align: center;">
                                <a href="{{ route('admin.inventory-alerts.index') }}" style="color: #007aff; text-decoration: none; font-weight: 600; font-size: 13px;">
                                    {{ __('View all alerts') }}
                                </a>
                            </div>
                        </div>
                    </div>

                    @if (auth()->user()->role == 'superadmin')
                        <div class="notifications dropdown">
                            <a href="#" class="drop-notification-controller mt-1 me-3" data-bs-toggle="dropdown">
                                <i>
                                    <svg width="24" height="24" viewBox="0 0 21 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path fill-rule="evenodd" clip-rule="evenodd"
                                            d="M9.52246 3.03984V0.75C9.52246 0.335999 9.86834 0 10.2945 0C10.7207 0 11.0666 0.335999 11.0666 0.75V3.03994C14.6782 3.41551 17.5001 6.39355 17.5001 9.99994V12.7881C17.5001 14.7671 18.3926 16.6349 19.948 17.913C20.3587 18.2531 20.5883 18.7379 20.5883 19.2501C20.5883 20.215 19.7802 21 18.7867 21H14.0768C13.7183 22.7098 12.1586 24 10.2941 24C8.42959 24 6.8699 22.7098 6.51132 21H1.80141C0.80806 21 0 20.215 0 19.2501C0 18.7379 0.229582 18.2531 0.629937 17.92C2.19573 16.6349 3.08823 14.7671 3.08823 12.7881V9.99994C3.08823 6.39325 5.91053 3.41502 9.52246 3.03984ZM10.2941 22.5C11.3011 22.5 12.1596 21.8732 12.4781 21H8.11011C8.42866 21.8732 9.28724 22.5 10.2941 22.5ZM1.80141 19.5H7.20584H13.3823H18.7867C18.9267 19.5 19.0442 19.3859 19.0442 19.2501C19.0442 19.1501 18.9874 19.088 18.9535 19.06C17.0481 17.495 15.9559 15.2089 15.9559 12.7881V9.99994C15.9559 6.96698 13.4164 4.5 10.2941 4.5C7.17189 4.5 4.63235 6.96698 4.63235 9.99994V12.7881C4.63235 15.2089 3.54024 17.495 1.63686 19.058C1.60066 19.088 1.54412 19.1501 1.54412 19.2501C1.54412 19.3859 1.66155 19.5 1.80141 19.5Z"
                                            fill="#15803D" />
                                    </svg>
                                </i>
                                <span class="badge-soft-danger position-absolute d-flex align-items-center justify-content-center">
                                    {{ auth()->user()->unreadNotifications->count() }}
                                </span>
                            </a>
                            <div class="dropdown-menu">
                                <div class="notification-header">
                                    <p>{{ __('You Have') }}
                                        <strong>{{ auth()->user()->unreadNotifications->count() }}</strong>
                                        {{ __('new Notifications') }}
                                    </p>
                                    <a href="{{ route('admin.notifications.mtReadAll') }}" class="text-danger">
                                        {{ __('Mark all Read') }}
                                    </a>
                                </div>
                                <ul>
                                    @foreach (auth()->user()->unreadNotifications as $notification)
                                        <li>
                                            <a href="{{ route('admin.notifications.mtView', $notification->id) }}">
                                                <strong>{{ __($notification->data['message'] ?? '') }}</strong>
                                                <span>{{ $notification->created_at->diffForHumans() }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                                <div class="notification-footer">
                                    <a class="text-danger" href="{{ route('admin.notifications.index') }}">
                                        {{ __('View all notifications') }}
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
                @if(env('VITE_CLERK_PUBLISHABLE_KEY'))
                <div id="clerk-user-button" class="me-3"></div>
                @endif
                {{-- Dark Mode Toggle --}}
                <div class="me-3" style="display:flex;align-items:center;">
                    <button id="darkModeToggle" onclick="toggleDarkMode()" title="{{ __('Toggle Dark Mode') }}"
                        style="width:36px;height:36px;border-radius:50%;border:1px solid #e5e7eb;background:var(--color-background,#fff);cursor:pointer;display:flex;align-items:center;justify-content:center;transition:all 0.2s;">
                        <span id="darkModeIcon">🌙</span>
                    </button>
                </div>

                {{-- Activity Feed --}}
                <div class="dropdown me-3" style="position:relative;">
                    <a href="#" data-bs-toggle="dropdown" style="text-decoration:none;display:flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:50%;border:1px solid #e5e7eb;background:var(--color-background,#fff);font-size:16px;" title="{{ __('Recent Activity') }}">
                        📊
                    </a>
                    <div class="dropdown-menu" style="min-width:300px;max-height:400px;overflow-y:auto;">
                        <div style="padding:12px 16px;border-bottom:1px solid #f0f0f2;">
                            <strong style="font-size:14px;">{{ __('Recent Activity') }}</strong>
                        </div>
                        <ul style="list-style:none;padding:0;margin:0;">
                            @php
                                $businessId = auth()->user()->business_id;
                                $recentSales = \App\Models\Sale::where('business_id', $businessId)
                                    ->latest()->limit(5)->get();
                                $lowStock = \App\Models\Stock::where('productStock', '<=', 10)
                                    ->where('business_id', $businessId)->count();
                            @endphp
                            @forelse($recentSales as $sale)
                            <li style="padding:10px 16px;border-bottom:1px solid #f3f4f6;display:flex;gap:10px;align-items:center;">
                                <span style="width:8px;height:8px;border-radius:50%;background:#22c55e;flex-shrink:0;"></span>
                                <div style="flex:1;min-width:0;">
                                    <div style="font-size:13px;font-weight:500;color:#1f2937;">
                                        {{ __('Sale') }} #{{ $sale->invoiceNumber }}
                                    </div>
                                    <div style="font-size:11px;color:#6b7280;">
                                        {{ number_format($sale->totalAmount, 2) }} &middot; {{ $sale->created_at->diffForHumans() }}
                                    </div>
                                </div>
                            </li>
                            @empty
                            <li style="padding:16px;text-align:center;color:#9ca3af;font-size:13px;">
                                {{ __('No recent activity') }}
                            </li>
                            @endforelse
                            @if($lowStock > 0)
                            <li style="padding:10px 16px;background:#fef3c7;display:flex;gap:10px;align-items:center;">
                                <span style="width:8px;height:8px;border-radius:50%;background:#f59e0b;flex-shrink:0;"></span>
                                <div style="font-size:13px;color:#92400e;">
                                    {{ __(':count low stock item(s)', ['count' => $lowStock]) }}
                                </div>
                            </li>
                            @endif
                        </ul>
                    </div>
                </div>

                <div class="profile-info dropdown">
                    <a href="#" data-bs-toggle="dropdown" class="d-flex align-items-center gap-2">
                        <img src="{{ asset(Auth::user()->image ?? 'assets/images/icons/default-user.png') }}" alt="Profile">
                        <div class="d-flex align-items-center justify-content-center gap-2">
                            <p class="text-foreground">{{ auth()->user()->role == 'superadmin' ? __('Super Admin') : auth()->user()->name }}</p>
                            <i class="fas fa-chevron-down text-secondary"></i>
                        </div>
                    </a>
                    <ul class="dropdown-menu">
                        <li>
                            <a href="{{ url('cache-clear') }}">
                                <i class="far fa-undo"></i> {{ __('Clear cache') }}
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('admin.profiles.index') }}">
                                <i class="fal fa-user"></i> {{ __('My Profile') }}
                            </a>
                        </li>
                        <li>
                            <a href="javascript:void(0)" class="logoutButton">
                                <i class="far fa-sign-out"></i> {{ __('Logout') }}
                                <form action="{{ route('logout') }}" method="post" id="logoutForm">
                                    @csrf
                                </form>
                            </a>
                        </li>
                    </ul>
                </div></div>
</div>
</header>
</div>

{{-- Inventory Alerts Bell Script --}}
<script>
(function() {
    var bell     = document.querySelector('.inventory-alerts-bell');
    var countEl  = document.getElementById('inventoryAlertCount');
    var totalEl  = document.getElementById('inventoryAlertTotalBadge');
    var listEl   = document.getElementById('inventoryAlertList');
    var bellUrl  = '{{ route('admin.inventory-alerts.bell-data') }}';
    var ackUrl   = '{{ url("admin/inventory-alerts") }}/';

    var severityColors = { critical: '#ff3b30', warning: '#ff9500', info: '#007aff' };
    var typeLabels = { low_stock: 'منخفض', out_of_stock: 'نفذ', expiring_soon: 'قريب الانتهاء', expired: 'منتهي' };

    function fetchBell() {
        fetch(bellUrl, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.count > 0) {
                countEl.textContent = data.count > 99 ? '99+' : data.count;
                countEl.style.display = 'flex';
            } else {
                countEl.style.display = 'none';
            }
            totalEl.textContent = '(' + data.count + ')';

            if (data.alerts.length === 0) {
                listEl.innerHTML = '<li style="padding:16px;text-align:center;color:#86868b;font-size:13px;">لا توجد تنبيهات نشطة</li>';
                return;
            }

            var html = '';
            data.alerts.forEach(function(a) {
                html += '<li style="padding:10px 16px;border-bottom:1px solid #f0f0f2;display:flex;gap:10px;align-items:flex-start;" data-alert-id="' + a.id + '">' +
                    '<span style="width:8px;height:8px;border-radius:50%;background:' + (severityColors[a.severity] || '#86868b') + ';margin-top:6px;flex-shrink:0;"></span>' +
                    '<div style="flex:1;min-width:0;">' +
                    '<div style="font-size:13px;font-weight:600;color:#1d1d1f;line-height:1.4;">' + (a.message || '') + '</div>' +
                    '<div style="font-size:11px;color:#86868b;margin-top:2px;">' + (typeLabels[a.type] || a.type) + ' &middot; ' + timeAgo(a.created_at) + '</div>' +
                    '</div>' +
                    '<button onclick="ackInventoryAlert(' + a.id + ', this)" style="background:none;border:none;color:#007aff;font-size:12px;cursor:pointer;white-space:nowrap;padding:2px 6px;border-radius:6px;flex-shrink:0;" onmouseenter="this.style.background=#f0f0f2" onmouseleave="this.style.background=none" title="تأكيد">✓</button>' +
                    '</li>';
            });
            listEl.innerHTML = html;
        })
        .catch(function() {});
    }

    function timeAgo(dateStr) {
        var diff = (Date.now() - new Date(dateStr).getTime()) / 1000;
        if (diff < 60) return 'الآن';
        if (diff < 3600) return Math.floor(diff / 60) + ' دقيقة';
        if (diff < 86400) return Math.floor(diff / 3600) + ' ساعة';
        return Math.floor(diff / 86400) + ' يوم';
    }

    window.ackInventoryAlert = function(alertId, btn) {
        fetch(ackUrl + alertId + '/acknowledge-alert', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json', 'Content-Type': 'application/json' },
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            var li = btn.closest('li');
            if (li) li.remove();
            if (data.count > 0) {
                countEl.textContent = data.count > 99 ? '99+' : data.count;
                countEl.style.display = 'flex';
            } else {
                countEl.style.display = 'none';
            }
            totalEl.textContent = '(' + data.count + ')';
        });
    };

    fetchBell();
    setInterval(fetchBell, 60000);
})();
</script>

{{-- Firebase Cloud Messaging Push Notification Registration --}}
@if(env('VITE_FIREBASE_API_KEY'))
<script src="https://www.gstatic.com/firebasejs/10.12.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/10.12.0/firebase-messaging-compat.js"></script>
<script>
(function() {
    // Skip if Firebase is not configured
    var apiKey = '{{ env("VITE_FIREBASE_API_KEY") }}';
    if (!apiKey) return;

    var firebaseConfig = {
        apiKey: apiKey,
        authDomain: '{{ env("VITE_FIREBASE_AUTH_DOMAIN") }}',
        projectId: '{{ env("VITE_FIREBASE_PROJECT_ID", "z-syst") }}',
        storageBucket: '{{ env("VITE_FIREBASE_STORAGE_BUCKET") }}',
        messagingSenderId: '{{ env("VITE_FIREBASE_MESSAGING_SENDER_ID") }}',
        appId: '{{ env("VITE_FIREBASE_APP_ID") }}',
    };

    try {
        firebase.initializeApp(firebaseConfig);
        var messaging = firebase.messaging();

        // Request notification permission and register FCM token
        if ('Notification' in window && Notification.permission === 'default') {
            // Show a subtle prompt instead of auto-requesting
            var promptEl = document.createElement('div');
            promptEl.id = 'fcm-prompt';
            promptEl.style.cssText = 'position:fixed;bottom:20px;right:20px;background:#1d1d1f;color:#fff;padding:16px 20px;border-radius:12px;z-index:9999;font-size:14px;box-shadow:0 4px 20px rgba(0,0,0,0.2);max-width:340px;';
            promptEl.innerHTML = '<div style="margin-bottom:10px;">🔔 هل تريد تلقي إشعارات تنبيهات المخزون على جهازك؟</div>' +
                '<button onclick="requestFCMPermission()" style="background:#007aff;color:#fff;border:none;border-radius:8px;padding:8px 16px;font-weight:600;cursor:pointer;margin-right:8px;">تفعيل</button>' +
                '<button onclick="this.closest(\'#fcm-prompt\').remove()" style="background:transparent;color:#86868b;border:1px solid #555;border-radius:8px;padding:8px 16px;cursor:pointer;">لاحقاً</button>';
            document.body.appendChild(promptEl);
        }

        window.requestFCMPermission = function() {
            Notification.requestPermission().then(function(permission) {
                if (permission === 'granted') {
                    registerFCMToken(messaging);
                }
                var prompt = document.getElementById('fcm-prompt');
                if (prompt) prompt.remove();
            });
        };

        function registerFCMToken(messaging) {
            messaging.getToken({ vapidKey: null }).then(function(token) {
                if (token) {
                    fetch('{{ route("api.v1.push-tokens.store") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'Authorization': 'Bearer ' + (localStorage.getItem('auth_token') || ''),
                        },
                        body: JSON.stringify({ token: token, platform: 'web' }),
                    });
                }
            }).catch(function(err) {
                console.log('FCM token error:', err);
            });

            // Listen for token refresh
            messaging.onTokenRefresh(function() {
                messaging.getToken().then(function(newToken) {
                    if (newToken) {
                        registerFCMToken(messaging);
                    }
                });
            });
        }

        // Auto-register if already granted
        if (Notification.permission === 'granted') {
            registerFCMToken(messaging);
        }
    } catch(e) {
        console.log('Firebase init skipped:', e.message);
    }
})();
</script>
@endif

{{-- Dark Mode Toggle Script --}}
<script>
function toggleDarkMode() {
    const html = document.documentElement;
    const isDark = html.classList.toggle('dark');
    localStorage.setItem('darkMode', isDark);
    document.getElementById('darkModeIcon').textContent = isDark ? '☀️' : '🌙';

    // Save preference to server
    fetch('{{ route("toggle-dark-mode") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
        },
    });
}

// Set initial icon
document.addEventListener('DOMContentLoaded', function() {
    const isDark = document.documentElement.classList.contains('dark');
    const icon = document.getElementById('darkModeIcon');
    if (icon) icon.textContent = isDark ? '☀️' : '🌙';
});
</script>
