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

<div class="card getting-started-card" style="border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; margin-bottom: 24px;">
    <div style="padding: 20px 24px; background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: #fff;">
        <div style="display: flex; align-items: center; justify-content: space-between;">
            <div>
                <h3 style="margin: 0 0 4px; font-size: 16px; font-weight: 600;">
                    <i class="fas fa-rocket me-2"></i>{{ __('Getting Started') }}
                </h3>
                <p style="margin: 0; font-size: 13px; opacity: 0.85;">
                    {{ __('Complete these steps to set up your store') }}
                </p>
            </div>
            <div style="text-align: right;">
                <div style="font-size: 24px; font-weight: 700;">{{ $completedCount }}/{{ $totalCount }}</div>
                <div style="font-size: 11px; opacity: 0.8;">{{ __('steps done') }}</div>
            </div>
        </div>
        <!-- Progress bar -->
        <div style="margin-top: 16px; background: rgba(255,255,255,0.2); border-radius: 8px; height: 6px;">
            <div style="background: #fff; border-radius: 8px; height: 100%; width: {{ ($completedCount / $totalCount) * 100 }}%; transition: width 0.5s ease;"></div>
        </div>
    </div>

    <div style="padding: 16px 24px;">
        @foreach($steps as $step)
        <div class="getting-started-step" style="display: flex; align-items: center; gap: 14px; padding: 12px 0; {{ !$loop->last ? 'border-bottom: 1px solid #f3f4f6;' : '' }}">
            <div style="width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0;
                {{ $step['done'] ? 'background: #dcfce7; color: #16a34a;' : 'background: #f3f4f6; color: #9ca3af;' }}">
                @if($step['done'])
                    <i class="fas fa-check" style="font-size: 14px;"></i>
                @else
                    <i class="fas {{ $step['icon'] }}" style="font-size: 14px;"></i>
                @endif
            </div>
            <div style="flex: 1; {{ $step['done'] ? 'opacity: 0.6;' : '' }}">
                <div style="font-size: 14px; font-weight: 500; color: #1f2937; {{ $step['done'] ? 'text-decoration: line-through;' : '' }}">
                    {{ $step['title'] }}
                </div>
                <div style="font-size: 12px; color: #6b7280; margin-top: 2px;">{{ $step['description'] }}</div>
            </div>
            @if(!$step['done'])
            <a href="{{ $step['url'] }}" style="font-size: 12px; font-weight: 500; color: #6366f1; text-decoration: none; padding: 6px 14px; border: 1px solid #e0e7ff; border-radius: 6px; white-space: nowrap; transition: all 0.2s;">
                {{ __('Start') }} <i class="fas fa-arrow-right" style="font-size: 10px; margin-left: 4px;"></i>
            </a>
            @else
            <span style="font-size: 12px; color: #16a34a; font-weight: 500;">
                <i class="fas fa-check-circle"></i> {{ __('Done') }}
            </span>
            @endif
        </div>
        @endforeach
    </div>
</div>
