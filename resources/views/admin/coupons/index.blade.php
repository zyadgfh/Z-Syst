@extends('layouts.admin')

@section('title', 'إدارة الكوبونات')

@section('main_content')
    <div class="container-fluid" class="card-body-lg">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <h4 class="fw-700">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-align-lg">
                    <path d="M20 12v6a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-6"/>
                    <path d="M2 8h20v4H2z"/>
                    <circle cx="12" cy="8" r="2"/>
                </svg>
                الكوبونات والخصومات
            <div class="d-flex gap-2">
                <a href="{{ route('admin.coupons.import') }}" class="btn pad-input" class="btn-secondary" onmousedown="this.style.transform='scale(0.97)'" onmouseup="this.style.transform='scale(1)'">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-align"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                    استيراد CSV
                </a>
                <a href="{{ route('admin.coupons.analytics') }}" class="btn pad-input" class="btn-secondary" onmousedown="this.style.transform='scale(0.97)'" onmouseup="this.style.transform='scale(1)'">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-align"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                    التحليلات
                </a>
                <a href="{{ route('admin.coupons.bulk-generate') }}" class="btn" class="btn-purple" onmousedown="this.style.transform='scale(0.97)'" onmouseup="this.style.transform='scale(1)'>
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="icon-align"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/></svg>
                    إنشاء بالجملة
                </a>
                <a href="{{ route('admin.coupons.create') }}" class="btn btn-apple-blue"
                   onmousedown="this.style.transform='scale(0.97)'" onmouseup="this.style.transform='scale(1)'">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="icon-align"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    كوبون جديد
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert" class="alert-success-custom">
                {{ session('success') }}
            </div>
        @endif

        {{-- Filters --}}
        <div class="card mb-4" class="card-clean-bordered">
            <div class="card-body" class="p-16">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label-xs">بحث</label>
                        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="كود الكوبون أو الوصف..." class="input-clean-sm">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label-xs">النوع</label>
                        <select name="type" class="form-select" class="input-clean-sm">
                            <option value="">الكل</option>
                            <option value="percentage" {{ request('type') === 'percentage' ? 'selected' : '' }}>نسبة مئوية</option>
                            <option value="fixed" {{ request('type') === 'fixed' ? 'selected' : '' }}>مبلغ ثابت</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label-xs">الحالة</label>
                        <select name="status" class="form-select" class="input-clean-sm">
                            <option value="">الكل</option>
                            <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>نشط</option>
                            <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>منتهي</option>
                            <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>معطّل</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn w-100 pad-sm" class="btn-dark">بحث</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Bulk Actions Bar --}}
        <div id="bulkActionsBar" class="bulk-action-bar">
            <div class="row-cell-gap12">
                <span id="selectedCount" class="selected-count">0 محدد</span>
                <button onclick="bulkToggleStatus(true)" class="btn-bulk-green">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="va-middle-r2"><polyline points="20 6 9 17 4 12"/></svg>
                    تفعيل المحدد
                </button>
                <button onclick="bulkToggleStatus(false)" class="btn-bulk-amber">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="va-middle-r2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    تعطيل المحدد
                </button>
                <button onclick="bulkDelete()" class="btn-bulk-red">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="va-middle-r2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    حذف المحدد
                </button>
            </div>
            <button onclick="clearSelection()" class="btn-link-gray">إلغاء التحديد</button>
        </div>

        {{-- Coupons Table --}}
        <div class="card" class="card-clean">
            <div class="table-responsive">
                <table class="table table-hover mb-0" class="fs-14">
                    <thead class="bg-light">
                        <tr>
                            <th class="th-clean">
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)" class="cursor-pointer" class="checkbox-sm">
                            </th>
                            <th class="tab-btn-upper">الكود</th>
                            <th class="tab-btn-upper">النوع والقيمة</th>
                            <th class="tab-btn-upper">الحد الأدنى</th>
                            <th class="tab-btn-upper">الاستخدامات</th>
                            <th class="tab-btn-upper">الصلاحية</th>
                            <th class="tab-btn-upper">الحالة</th>
                            <th class="tab-btn-upper">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($coupons as $coupon)
                            <tr class="border-bottom-light" data-coupon-id="{{ $coupon->id }}">
                                <td class="td-align">
                                    <input type="checkbox" class="coupon-checkbox" value="{{ $coupon->id }}" onchange="updateBulkActions()" class="cursor-pointer" class="checkbox-sm">
                                </td>
                                <td class="td-align">
                                    <code class="code-badge">{{ $coupon->code }}</code>
                                </td>
                                <td class="td-align">
                                    @if ($coupon->type === 'percentage')
                                        <span class="type-badge-green">{{ $coupon->value }}%</span>
                                    @else
                                        <span class="type-badge-blue">{{ number_format($coupon->value, 2) }}</span>
                                    @endif
                                </td>
                                <td class="td-middle">
                                    {{ $coupon->minimum_order_amount > 0 ? number_format($coupon->minimum_order_amount, 2) : '—' }}
                                </td>
                                <td class="td-align">
                                    <span class="fw-600">{{ $coupon->times_used }}</span>
                                    @if ($coupon->usage_limit)
                                        <span class="text-muted-custom">/ {{ $coupon->usage_limit }}</span>
                                    @endif
                                </td>
                                <td class="td-middle-muted">
                                    @if ($coupon->expires_at)
                                        <span class="{{ $coupon->expires_at->isPast() ? 'text-danger' : '' }}">
                                            {{ $coupon->expires_at->format('Y-m-d') }}
                                        </span>
                                    @else
                                        <span>بلا انتهاء</span>
                                    @endif
                                </td>
                                <td class="td-align">
                                    <button onclick="toggleCouponStatus({{ $coupon->id }}, this)"
                                        class="badge {{ $coupon->active ? 'btn-toggle-active' : 'btn-toggle-inactive' }}" class="border-none cursor-pointer">
                                        {{ $coupon->active ? 'نشط' : 'معطّل' }}
                                    </button>
                                </td>
                                <td class="td-align">
                                    <div class="d-flex gap-2">
                                        @can('coupons-update')
                                        <a href="{{ route('admin.coupons.edit', $coupon) }}" class="btn btn-sm" class="btn-edit">
                                            تعديل
                                        </a>
                                        @endcan
                                        @can('coupons-delete')
                                        <form action="{{ route('admin.coupons.destroy', $coupon) }}" method="POST" class="d-inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا الكوبون؟')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm" class="btn-delete">حذف</button>
                                        </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4" class="text-muted-custom">لا توجد كوبونات بعد</td>
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
