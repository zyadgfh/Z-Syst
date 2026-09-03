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
            <i class="fas fa-shopping-cart c-green mr-6"></i>{{ __('Today\'s Sales') }}
        </h3>
        @php
            $todaySales = \App\Models\Sale::where('business_id', $businessId)
                ->whereDate('created_at', today())
                ->selectRaw('COUNT(*) as count, SUM(totalAmount) as total')
                ->first();
        @endphp
        <div class="js-stat-value-lg fz28-dark">{{ $todaySales->count ?? 0 }}</div>
        <div class="section-subtitle-xs c-gray-500">{{ __('transactions') }} &middot; {{ number_format($todaySales->total ?? 0, 2) }}</div>
    </div>

    {{-- Low Stock Alert --}}
    <div class="card card-body">
        <h3 class="section-subtitle">
            <i class="fas fa-exclamation-triangle c-amber mr-6"></i>{{ __('Low Stock Items') }}
        </h3>
        @php
            $lowStock = \App\Models\Stock::where('business_id', $businessId)
                ->where('productStock', '<=', 10)
                ->with('product:id,productName')
                ->limit(5)
                ->get();
        @endphp
        <div class="js-stat-value-lg" class="stat-xl" style="font-size:28px; color:{{ $lowStock->count() > 0 ? '#f59e0b' : '#22c55e' }};">
            {{ $lowStock->count() }}
        </div>
        @if($lowStock->count() > 0)
        <div class="mt-8">
            @foreach($lowStock as $stock)
            <div class="fz12-gray-py2">
                {{ $stock->product->productName ?? '—' }}: <strong class="c-red">{{ $stock->productStock }}</strong>
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
<div class="card alert-warning-custom">
    <div class="flex-center-gap12">
        <i class="fas fa-prescription text-amber" class="text-20"></i>
        <div>
            <strong class="c-amber-800">{{ __(':count pending prescription(s)', ['count' => $pendingRx]) }}</strong>
            <div class="fz12-amber">{{ __('Review and dispense pending prescriptions') }}</div>
        </div>
        <a href="{{ route('admin.prescriptions.index') }}" class="badge-warning-link">
            {{ __('View') }}
        </a>
    </div>
</div>
@endif

{{-- Quick Actions for Staff --}}
<div class="card card-body mb-24">
    <h3 class="section-subtitle mb-16">
        <i class="fas fa-bolt c-indigo mr-6"></i>{{ __('Quick Actions') }}
    </h3>
    <div class="js-grid-4col">
        <a href="{{ route('admin.prescriptions.index') }}" class="feature-card">
            <i class="fas fa-prescription fz20-indigo"></i>
            <span class="fs-12-500">{{ __('Prescriptions') }}</span>
        </a>
        <a href="{{ route('admin.products.index') }}" class="feature-card">
            <i class="fas fa-pills fz20-green"></i>
            <span class="fs-12-500">{{ __('Products') }}</span>
        </a>
        <a href="{{ route('admin.inventory-alerts.index') }}" class="feature-card">
            <i class="fas fa-boxes text-amber" class="text-20"></i>
            <span class="fs-12-500">{{ __('Inventory') }}</span>
        </a>
        <a href="{{ route('admin.receipts.index') }}" class="feature-card">
            <i class="fas fa-receipt fz20-purple"></i>
            <span class="fs-12-500">{{ __('Receipts') }}</span>
        </a>
    </div>
</div>

@else
{{-- ═══ Owner/Admin Dashboard — default view ═══ --}}
{{-- (existing dashboard content remains unchanged) --}}
@endif
