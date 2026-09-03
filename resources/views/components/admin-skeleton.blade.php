{{-- Admin Skeleton Loading Screens --}}
{{-- Usage: @include('components.admin-skeleton', ['type' => 'table']) --}}
{{-- Types: table, card, stats, dashboard --}}

@props(['type' => 'table', 'rows' => 5])

<style>
.admin-skeleton { background: linear-gradient(90deg, #f0f0f0 25%, #e8e8e8 50%, #f0f0f0 75%); background-size: 200% 100%; animation: admin-skeleton-shimmer 1.5s infinite; border-radius: 6px; }
@keyframes admin-skeleton-shimmer { 0% { background-position: 200% 0; } 100% { background-position: -200% 0; } }
.admin-skeleton-row { display: flex; align-items: center; gap: 12px; padding: 14px 0; border-bottom: 1px solid #f3f4f6; }
.admin-skeleton-card { padding: 20px; border: 1px solid #e5e7eb; border-radius: 12px; background: #fff; }
</style>

@if($type === 'table')
<div class="admin-skeleton" style="padding: 16px; border-radius: 12px; border: 1px solid #e5e7eb;">
    {{-- Header --}}
    <div style="display: flex; justify-content: space-between; margin-bottom: 16px;">
        <div class="admin-skeleton" style="width: 200px; height: 36px; border-radius: 8px;"></div>
        <div style="display: flex; gap: 8px;">
            <div class="admin-skeleton" style="width: 100px; height: 36px; border-radius: 8px;"></div>
            <div class="admin-skeleton" style="width: 120px; height: 36px; border-radius: 8px;"></div>
        </div>
    </div>
    {{-- Rows --}}
    @for($i = 0; $i < $rows; $i++)
    <div class="admin-skeleton-row">
        <div class="admin-skeleton" style="width: 32px; height: 32px; border-radius: 6px;"></div>
        <div style="flex: 1; display: flex; gap: 20px; align-items: center;">
            <div class="admin-skeleton" style="width: {{ rand(40, 70) }}%; height: 14px;"></div>
            <div class="admin-skeleton" style="width: 80px; height: 14px;"></div>
            <div class="admin-skeleton" style="width: 60px; height: 14px;"></div>
        </div>
        <div class="admin-skeleton" style="width: 70px; height: 28px; border-radius: 14px;"></div>
    </div>
    @endfor
</div>

@elseif($type === 'stats')
<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px;">
    @for($i = 0; $i < 4; $i++)
    <div class="admin-skeleton-card">
        <div style="display: flex; align-items: center; gap: 12px;">
            <div class="admin-skeleton" style="width: 48px; height: 48px; border-radius: 12px;"></div>
            <div style="flex: 1;">
                <div class="admin-skeleton" style="width: 60%; height: 20px; margin-bottom: 8px;"></div>
                <div class="admin-skeleton" style="width: 40%; height: 12px;"></div>
            </div>
        </div>
    </div>
    @endfor
</div>

@elseif($type === 'dashboard')
<div>
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px;">
        @for($i = 0; $i < 4; $i++)
        <div class="admin-skeleton-card">
            <div class="admin-skeleton" style="width: 48px; height: 48px; border-radius: 12px; margin-bottom: 12px;"></div>
            <div class="admin-skeleton" style="width: 70%; height: 20px; margin-bottom: 6px;"></div>
            <div class="admin-skeleton" style="width: 50%; height: 12px;"></div>
        </div>
        @endfor
    </div>
    <div class="admin-skeleton-card" style="height: 320px;">
        <div class="admin-skeleton" style="width: 100%; height: 100%; border-radius: 8px;"></div>
    </div>
</div>

@elseif($type === 'card')
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
    @for($i = 0; $i < ($rows ?: 3); $i++)
    <div class="admin-skeleton-card">
        <div class="admin-skeleton" style="width: 100%; height: 160px; border-radius: 8px; margin-bottom: 12px;"></div>
        <div class="admin-skeleton" style="width: 70%; height: 18px; margin-bottom: 8px;"></div>
        <div class="admin-skeleton" style="width: 50%; height: 12px;"></div>
    </div>
    @endfor
</div>
@endif
