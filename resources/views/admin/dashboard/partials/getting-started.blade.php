{{-- Getting Started Checklist Widget --}}
{{-- Shows only for new users who haven't completed setup --}}
@php
    $businessId = auth()->user()->business_id;
    $steps = [
        [
            'key' => 'business_setup',
            'icon' => 'fa-store',
            'title' => __('Business Setup'),
            'description' => __('Configure your store name, logo, and basic settings'),
            'url' => route('admin.business.edit', $businessId ?? 1),
            'done' => $businessId && auth()->user()->business && auth()->user()->business->companyName,
        ],
        [
            'key' => 'add_categories',
            'icon' => 'fa-th-large',
            'title' => __('Add Categories'),
            'description' => __('Create product categories to organize your inventory'),
            'url' => route('admin.business-categories.index'),
            'done' => \App\Models\Category::where('business_id', $businessId)->count() > 0,
        ],
        [
            'key' => 'add_products',
            'icon' => 'fa-pills',
            'title' => __('Add Products'),
            'description' => __('Add your first products with pricing and stock info'),
            'url' => route('admin.products.index'),
            'done' => \App\Models\Product::where('business_id', $businessId)->count() > 0,
        ],
        [
            'key' => 'add_suppliers',
            'icon' => 'fa-truck',
            'title' => __('Add Suppliers'),
            'description' => __('Register your suppliers for purchase management'),
            'url' => route('admin.suppliers.index'),
            'done' => \App\Models\Party::where('business_id', $businessId)->where('type', 'supplier')->count() > 0,
        ],
        [
            'key' => 'first_sale',
            'icon' => 'fa-shopping-cart',
            'title' => __('First Sale'),
            'description' => __('Process your first sale to see the system in action'),
            'url' => '#',
            'done' => \App\Models\Sale::where('business_id', $businessId)->count() > 0,
        ],
    ];

    $completedCount = collect($steps)->filter(fn($s) => $s['done'])->count();
    $totalCount = count($steps);
    $allDone = $completedCount === $totalCount;

    // Only show if not all steps are completed
    if ($allDone) return;
@endphp

<div class="card getting-started-card card-bordered">
    <div class="card-gradient-indigo">
        <div class="flex-between">
            <div>
                <h3 class="text-title">
                    <i class="fas fa-rocket me-2"></i>{{ __('Getting Started') }}
                </h3>
                <p class="text-subtle">
                    {{ __('Complete these steps to set up your store') }}
                </p>
            </div>
            <div class="text-right">
                <div class="fz24-bold">{{ $completedCount }}/{{ $totalCount }}</div>
                <div class="fz11-opacity">{{ __('steps done') }}</div>
            </div>
        </div>
        <!-- Progress bar -->
        <div class="progress-track">
            <div style="background: #fff; border-radius: 8px; height: 100%; width: {{ ($completedCount / $totalCount) * 100 }}%; transition: width 0.5s ease;"></div>
        </div>
    </div>

    <div class="pad-lg">
        @foreach($steps as $step)
        <div class="getting-started-step" style="display: flex; align-items: center; gap: 14px; padding: 12px 0; {{ !$loop->last ? 'border-bottom: 1px solid #f3f4f6;' : '' }}">
            <div style="width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
                {{ $step['done'] ? 'background: #dcfce7; color: #16a34a;' : 'background: #f3f4f6; color: #9ca3af;' }}">
                @if($step['done'])
                    <i class="fas fa-check" class="fs-14"></i>
                @else
                    <i class="fas {{ $step['icon'] }}" class="fs-14"></i>
                @endif
            </div>
            <div class="flex-1" style="{{ $step['done'] ? 'opacity: 0.6;' : '' }}">
                <div class="fw-600 text-14" style="color: #1f2937; {{ $step['done'] ? 'text-decoration: line-through;' : '' }}">
                    {{ $step['title'] }}
                </div>
                <div class="fz12-gray-mt2">{{ $step['description'] }}</div>
            </div>
            @if(!$step['done'])
            <a href="{{ $step['url'] }}" class="badge-indigo-link">
                {{ __('Start') }} <i class="fas fa-arrow-right fz10 ml-4"></i>
            </a>
            @else
            <span class="fz12-green-semibold">
                <i class="fas fa-check-circle"></i> {{ __('Done') }}
            </span>
            @endif
        </div>
        @endforeach
    </div>
</div>
