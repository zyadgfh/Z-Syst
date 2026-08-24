@extends('layouts.master')

@section('title', 'إدارة الكوبونات')

@section('main_content')
    <div class="container-fluid" style="padding: 24px;">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h4 style="font-weight: 700;">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px;">
                    <path d="M20 12v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-6"/>
                    <path d="M2 8h20v4H2z"/>
                    <circle cx="12" cy="8" r="2"/>
                </svg>
                الكوبونات والخصومات
            <div class="d-flex gap-2">
                <a href="{{ route('admin.coupons.import') }}" class="btn" style="background: #f5f5f7; color: #1d1d1f; border-radius: 10px; padding: 10px 16px; font-weight: 600; text-decoration: none; transition: transform 150ms ease; font-size: 14px;" onmousedown="this.style.transform='scale(0.97)'" onmouseup="this.style.transform='scale(1)'">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 4px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    استيراد CSV
                </a>
                <a href="{{ route('admin.coupons.analytics') }}" class="btn" style="background: #f5f5f7; color: #1d1d1f; border-radius: 10px; padding: 10px 16px; font-weight: 600; text-decoration: none; transition: transform 150ms ease; font-size: 14px;" onmousedown="this.style.transform='scale(0.97)'" onmouseup="this.style.transform='scale(1)'">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 4px;"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    التحليلات
                </a>
                <a href="{{ route('admin.coupons.bulk-generate') }}" class="btn" style="background: #5856d6; color: #fff; border-radius: 10px; padding: 10px 16px; font-weight: 600; text-decoration: none; transition: transform 150ms ease; font-size: 14px;" onmousedown="this.style.transform='scale(0.97)'" onmouseup="this.style.transform='scale(1)'>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 4px;"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></svg>
                    إنشاء بالجملة
                </a>
                <a href="{{ route('admin.coupons.create') }}" class="btn" style="background: #007aff; color: #fff; border-radius: 10px; padding: 10px 20px; font-weight: 600; text-decoration: none; transition: transform 150ms ease;"
                   onmousedown="this.style.transform='scale(0.97)'" onmouseup="this.style.transform='scale(1)'">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="vertical-align: middle; margin-right: 4px;"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    كوبون جديد
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert" style="background: #d4edda; color: #155724; border-radius: 12px; padding: 12px 16px; border: none; font-size: 14px;">
                {{ session('success') }}
            </div>
        @endif

        {{-- Filters --}}
        <div class="card mb-4" style="border-radius: 14px; border: 1px solid #e5e5ea;">
            <div class="card-body" style="padding: 16px;">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label style="font-size: 12px; font-weight: 600; color: #6e6e73; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; display: block;">بحث</label>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="كود الكوبون أو الوصف..." style="border-radius: 10px; border: 1px solid #d2d2d7; padding: 10px 14px;">
                    </div>
                    <div class="col-md-3">
                        <label style="font-size: 12px; font-weight: 600; color: #6e6e73; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; display: block;">النوع</label>
                        <select name="type" class="form-select" style="border-radius: 10px; border: 1px solid #d2d2d7; padding: 10px 14px;">
                            <option value="">الكل</option>
                            <option value="percentage" {{ request('type') === 'percentage' ? 'selected' : '' }}>نسبة مئوية</option>
                            <option value="fixed" {{ request('type') === 'fixed' ? 'selected' : '' }}>مبلغ ثابت</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label style="font-size: 12px; font-weight: 600; color: #6e6e73; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; display: block;">الحالة</label>
                        <select name="status" class="form-select" style="border-radius: 10px; border: 1px solid #d2d2d7; padding: 10px 14px;">
                            <option value="">الكل</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>نشط</option>
                            <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>منتهي</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>معطّل</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn w-100" style="background: #1d1d1f; color: #fff; border-radius: 10px; padding: 10px; font-weight: 600;">بحث</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Bulk Actions Bar --}}
        <div id="bulkActionsBar" style="display: none; background: #f0f7ff; border-radius: 12px; padding: 12px 20px; margin-bottom: 16px; display: none; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span id="selectedCount" style="font-size: 14px; font-weight: 600; color: #007aff;">0 محدد</span>
                <button onclick="bulkToggleStatus(true)" style="background: #34c759; color: #fff; border: none; border-radius: 8px; padding: 6px 14px; font-size: 13px; font-weight: 600; cursor: pointer;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 2px;"><polyline points="20 6 9 17 4 12"/></svg>
                    تفعيل المحدد
                </button>
                <button onclick="bulkToggleStatus(false)" style="background: #ff9500; color: #fff; border: none; border-radius: 8px; padding: 6px 14px; font-size: 13px; font-weight: 600; cursor: pointer;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 2px;"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    تعطيل المحدد
                </button>
                <button onclick="bulkDelete()" style="background: #ff3b30; color: #fff; border: none; border-radius: 8px; padding: 6px 14px; font-size: 13px; font-weight: 600; cursor: pointer;">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 2px;"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    حذف المحدد
                </button>
            </div>
            <button onclick="clearSelection()" style="background: none; border: none; color: #86868b; font-size: 13px; cursor: pointer; font-weight: 600;">إلغاء التحديد</button>
        </div>

        {{-- Coupons Table --}}
        <div class="card" style="border-radius: 14px; border: 1px solid #e5e5ea; overflow: hidden;">
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="font-size: 14px;">
                    <thead style="background: #f5f5f7;">
                        <tr>
                            <th style="border: none; padding: 12px 16px; width: 40px;">
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)" style="width: 18px; height: 18px; border-radius: 4px; cursor: pointer;">
                            </th>
                            <th style="border: none; padding: 12px 16px; font-weight: 600; color: #6e6e73; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;">الكود</th>
                            <th style="border: none; padding: 12px 16px; font-weight: 600; color: #6e6e73; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;">النوع والقيمة</th>
                            <th style="border: none; padding: 12px 16px; font-weight: 600; color: #6e6e73; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;">الحد الأدنى</th>
                            <th style="border: none; padding: 12px 16px; font-weight: 600; color: #6e6e73; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;">الاستخدامات</th>
                            <th style="border: none; padding: 12px 16px; font-weight: 600; color: #6e6e73; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;">الصلاحية</th>
                            <th style="border: none; padding: 12px 16px; font-weight: 600; color: #6e6e73; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;">الحالة</th>
                            <th style="border: none; padding: 12px 16px; font-weight: 600; color: #6e6e73; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em;">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($coupons as $coupon)
                            <tr style="border-bottom: 1px solid #f0f0f2;" data-coupon-id="{{ $coupon->id }}">
                                <td style="padding: 14px 16px; vertical-align: middle;">
                                    <input type="checkbox" class="coupon-checkbox" value="{{ $coupon->id }}" onchange="updateBulkActions()" style="width: 18px; height: 18px; border-radius: 4px; cursor: pointer;">
                                </td>
                                <td style="padding: 14px 16px; vertical-align: middle;">
                                    <code style="background: #f5f5f7; padding: 4px 10px; border-radius: 6px; font-weight: 600; font-size: 14px; letter-spacing: 0.04em;">{{ $coupon->code }}</code>
                                </td>
                                <td style="padding: 14px 16px; vertical-align: middle;">
                                    @if ($coupon->type === 'percentage')
                                        <span style="background: #e8f5e9; color: #2e7d32; padding: 4px 10px; border-radius: 6px; font-size: 13px; font-weight: 600;">{{ $coupon->value }}%</span>
                                    @else
                                        <span style="background: #e3f2fd; color: #1565c0; padding: 4px 10px; border-radius: 6px; font-size: 13px; font-weight: 600;">{{ number_format($coupon->value, 2) }}</span>
                                    @endif
                                </td>
                                <td style="padding: 14px 16px; vertical-align: middle; color: #6e6e73;">
                                    {{ $coupon->minimum_order_amount > 0 ? number_format($coupon->minimum_order_amount, 2) : '—' }}
                                </td>
                                <td style="padding: 14px 16px; vertical-align: middle;">
                                    <span style="font-weight: 600;">{{ $coupon->times_used }}</span>
                                    @if ($coupon->usage_limit)
                                        <span style="color: #86868b;">/ {{ $coupon->usage_limit }}</span>
                                    @endif
                                </td>
                                <td style="padding: 14px 16px; vertical-align: middle; font-size: 13px; color: #6e6e73;">
                                    @if ($coupon->expires_at)
                                        <span class="{{ $coupon->expires_at->isPast() ? 'text-danger' : '' }}">
                                            {{ $coupon->expires_at->format('Y-m-d') }}
                                        </span>
                                    @else
                                        <span>بلا انتهاء</span>
                                    @endif
                                </td>
                                <td style="padding: 14px 16px; vertical-align: middle;">
                                    <button onclick="toggleCouponStatus({{ $coupon->id }}, this)"
                                        class="badge" style="border: none; padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; transition: background 150ms ease; {{ $coupon->active ? 'background: #d4edda; color: #155724;' : 'background: #f8d7da; color: #721c24;' }}">
                                        {{ $coupon->active ? 'نشط' : 'معطّل' }}
                                    </button>
                                </td>
                                <td style="padding: 14px 16px; vertical-align: middle;">
                                    <div class="d-flex gap-2">
                                        <a href="{{ route('admin.coupons.edit', $coupon) }}" class="btn btn-sm" style="background: #f5f5f7; border: none; border-radius: 8px; padding: 6px 12px; font-size: 13px; text-decoration: none; color: #1d1d1f;">
                                            تعديل
                                        </a>
                                        <form action="{{ route('admin.coupons.destroy', $coupon) }}" method="POST" style="display: inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذا الكوبون؟')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm" style="background: #fff0f0; border: none; border-radius: 8px; padding: 6px 12px; font-size: 13px; color: #ff3b30;">حذف</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4" style="color: #86868b;">لا توجد كوبونات بعد</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-center mt-3">
            {{ $coupons->withQueryString()->links() }}
        </div>
    </div>

    @push('scripts')
    <script>
        function toggleCouponStatus(couponId, btn) {
            fetch(`/admin/coupons/${couponId}/toggle-status`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta["csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
            })
            .then(r => r.json())
            .then(data => {
                if (data.active) {
                    btn.style.background = '#d4edda';
                    btn.style.color = '#155724';
                    btn.textContent = 'نشط';
                } else {
                    btn.style.background = '#f8d7da';
                    btn.style.color = '#721c24';
                    btn.textContent = 'معطّل';
                }
            });
        }

        // ── Bulk Actions ──
        function getSelectedIds() {
            var ids = [];
            document.querySelectorAll('.coupon-checkbox:checked').forEach(function(cb) {
                ids.push(parseInt(cb.value));
            });
            return ids;
        }

        function toggleSelectAll(checkbox) {
            document.querySelectorAll('.coupon-checkbox').forEach(function(cb) {
                cb.checked = checkbox.checked;
            });
            updateBulkActions();
        }

        function updateBulkActions() {
            var ids = getSelectedIds();
            var bar = document.getElementById('bulkActionsBar');
            var countEl = document.getElementById('selectedCount');
            if (ids.length > 0) {
                bar.style.display = 'flex';
                countEl.textContent = ids.length + ' محدد';
            } else {
                bar.style.display = 'none';
            }
            // Update select all checkbox
            var allCbs = document.querySelectorAll('.coupon-checkbox');
            document.getElementById('selectAll').checked = allCbs.length > 0 && allCbs.length === ids.length;
        }

        function clearSelection() {
            document.querySelectorAll('.coupon-checkbox').forEach(function(cb) { cb.checked = false; });
            document.getElementById('selectAll').checked = false;
            updateBulkActions();
        }

        function bulkToggleStatus(active) {
            var ids = getSelectedIds();
            if (ids.length === 0) return;

            var action = active ? 'تفعيل' : 'تعطيل';
            if (!confirm('هل تريد ' + action + ' ' + ids.length + ' كوبون؟')) return;

            fetch('{{ route("admin.coupons.bulk-toggle-status") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta["csrf-token"]').content,
                },
                body: JSON.stringify({ ids: ids, active: active }),
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                }
            });
        }

        function bulkDelete() {
            var ids = getSelectedIds();
            if (ids.length === 0) return;

            if (!confirm('هل تريد حذف ' + ids.length + ' كوبون نهائياً؟ لا يمكن التراجع عن هذا الإجراء.')) return;

            fetch('{{ route("admin.coupons.bulk-delete") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta["csrf-token"]').content,
                },
                body: JSON.stringify({ ids: ids }),
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    // Remove deleted rows
                    ids.forEach(function(id) {
                        var row = document.querySelector('tr[data-coupon-id="' + id + '"]');
                        if (row) {
                            row.style.transition = 'opacity 0.3s';
                            row.style.opacity = '0';
                            setTimeout(function() { row.remove(); }, 300);
                        }
                    });
                    clearSelection();
                    alert(data.message);
                }
            });
        }
    </script>
    @endpush
@endsection
