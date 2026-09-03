{{-- Empty State Component --}}
{{-- Usage: @include('components.empty-state', ['icon' => 'fa-box-open', 'title' => 'No products yet', 'description' => 'Add your first product to get started.', 'actionUrl' => route('admin.products.create'), 'actionText' => 'Add Product']) --}}

@props(['icon' => 'fa-inbox', 'title' => __('No data found'), 'description' => __('There are no records to display yet.'), 'actionUrl' => null, 'actionText' => __('Create New')])

<div class="empty-state-container" style="text-align:center; padding:60px 20px;">
    <div class="empty-state-icon" style="width:80px; height:80px; border-radius:50%; background:linear-gradient(135deg,#f0f4ff,#e0e7ff); display:inline-flex; align-items:center; justify-content:center; margin-bottom:20px;">
        <i class="fas {{ $icon }}" style="font-size:32px; color:#6366f1;"></i>
    </div>
    <h3 style="font-size:18px; font-weight:600; color:#1f2937; margin-bottom:8px;">{{ $title }}</h3>
    <p style="font-size:14px; color:#6b7280; max-width:400px; margin:0 auto 24px; line-height:1.5;">{{ $description }}</p>
    @if($actionUrl)
    <a href="{{ $actionUrl }}" class="btn btn-primary" style="display:inline-flex; align-items:center; gap:8px; padding:10px 24px; border-radius:8px; font-size:14px; font-weight:500; text-decoration:none; background:#6366f1; color:#fff; border:none; cursor:pointer; transition:background 0.2s;">
        <i class="fas fa-plus"></i>
        {{ $actionText }}
    </a>
    @endif
</div>
