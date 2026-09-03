{{-- Role-Based Dashboard Content --}}
@php
    $user = auth()->user();
    $businessId = $user->business_id;
    $role = $user->role;
@endphp

@if(in_array($role, ['staff']))
{{-- ═══ Staff Dashboard ═══ --}}
<div class="js-grid-2col">
    {{-- Today's Sales --}}
    <div class="card card-body">
        <h3 class="section-subtitle">
            <i class="fas fa-shopping-cart" style="color:#22c55e; margin-right:6px;"></i>{{ __('Today\'s Sales') }}
        </h3>
        @php
            $todaySales = \App\Models\Sale::where('business_id', $businessId)
                ->whereDate('created_at', today())
                ->selectRaw('COUNT(*) as count, SUM(totalAmount) as total')
                ->first();
        @endphp
        <div class="js-stat-value-lg" style="font-size:28px; color:#1f2937;">{{ $todaySales->count ?? 0 }}</div>
        <div class="section-subtitle-xs" style="color:#6b7280;">{{ __('transactions') }} &middot; {{ number_format($todaySales->total ?? 0, 2) }}</div>
    </div>

    {{-- Low Stock Alert --}}
    <div class="card card-body">
        <h3 class="section-subtitle">
            <i class="fas fa-exclamation-triangle" style="color:#f59e0b; margin-right:6px;"></i>{{ __('Low Stock Items') }}
        </h3>
        @php
            $lowStock = \App\Models\Stock::where('business_id', $businessId)
                ->where('productStock', '<=', 10)
                ->with('product:id,productName')
                ->limit(5)
                ->get();
        @endphp
        <div class="js-stat-value-lg" style="font-size:28px; color:{{ $lowStock->count() > 0 ? '#f59e0b' : '#22c55e' }};">
            {{ $lowStock->count() }}
        </div>
        @if($lowStock->count() > 0)
        <div style="margin-top:8px;">
            @foreach($lowStock as $stock)
            <div style="font-size:12px; color:#6b7280; padding:2px 0;">
                {{ $stock->product->productName ?? '—' }}: <strong style="color:#ef4444;">{{ $stock->productStock }}</strong>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

{{-- Pending Prescriptions --}}
@php
    $pendingRx = \App\Models\Prescription::where('business_id', $businessId)
        ->where('status', 'pending')
        ->count();
@endphp
@if($pendingRx > 0)
<div class="card" style="padding:16px 20px; border:1px solid #fef3c7; border-radius:12px; background:#fffbeb; margin-bottom:24px;">
    <div style="display:flex; align-items:center; gap:12px;">
        <i class="fas fa-prescription" style="font-size:20px; color:#f59e0b;"></i>
        <div>
            <strong style="color:#92400e;">{{ __(':count pending prescription(s)', ['count' => $pendingRx]) }}</strong>
            <div style="font-size:12px; color:#b45309;">{{ __('Review and dispense pending prescriptions') }}</div>
        </div>
        <a href="{{ route('admin.prescriptions.index') }}" style="margin-left:auto; padding:6px 14px; background:#f59e0b; color:#fff; border-radius:6px; text-decoration:none; font-size:12px; font-weight:500;">
            {{ __('View') }}
        </a>
    </div>
</div>
@endif

{{-- Quick Actions for Staff --}}
<div class="card card-body" style="margin-bottom:24px;">
    <h3 class="section-subtitle" style="margin-bottom:16px;">
        <i class="fas fa-bolt" style="color:#6366f1; margin-right:6px;"></i>{{ __('Quick Actions') }}
    </h3>
    <div class="js-grid-4col">
        <a href="{{ route('admin.prescriptions.index') }}" class="feature-card">
            <i class="fas fa-prescription" style="font-size:20px; color:#6366f1;"></i>
            <span class="fs-12-500">{{ __('Prescriptions') }}</span>
        </a>
        <a href="{{ route('admin.products.index') }}" class="feature-card">
            <i class="fas fa-pills" style="font-size:20px; color:#22c55e;"></i>
            <span class="fs-12-500">{{ __('Products') }}</span>
        </a>
        <a href="{{ route('admin.inventory-alerts.index') }}" class="feature-card">
            <i class="fas fa-boxes" style="font-size:20px; color:#f59e0b;"></i>
            <span class="fs-12-500">{{ __('Inventory') }}</span>
        </a>
        <a href="{{ route('admin.receipts.index') }}" class="feature-card">
            <i class="fas fa-receipt" style="font-size:20px; color:#8b5cf6;"></i>
            <span class="fs-12-500">{{ __('Receipts') }}</span>
        </a>
    </div>
</div>

@else
{{-- ═══ Owner/Admin Dashboard — default view ═══ --}}
{{-- (existing dashboard content remains unchanged) --}}
@endif
