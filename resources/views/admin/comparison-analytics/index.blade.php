@extends('layouts.admin')

@section('title', 'تحليلات المقارنات')

@section('main_content')
    <div class="container-fluid" class="card-body-lg">
        <div class="d-flex align-items-center gap-3 mb-4">
            <a href="{{ route('admin.dashboard.index') }}" class="link-blue">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
            </a>
            <h4 class="heading-bold">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#007aff" stroke-width="2" class="icon-align-lg">
                    <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                </svg>
                تحليلات المقارنات
            </h4>
        </div>

        {{-- Stats Cards --}}
        <div class="js-kpi-grid">
            <div class="js-card-gradient-info text-center-pad">
                <div class="js-stat-value-lg" class="stat-xl" style="font-size: 32px; color: #007aff;">{{ $totalComparisons }}</div>
                <div class="section-subtitle-desc">إجمالي المقارنات</div>
            </div>
            <div class="js-card-gradient-success text-center-pad">
                <div class="js-stat-value-lg" class="stat-xl" style="font-size: 32px; color: #2e7d32;">{{ $totalViews }}</div>
                <div class="section-subtitle-desc">إجمالي المشاهدات</div>
            </div>
            <div class="js-card-gradient-amber text-center-pad">
                <div class="js-stat-value-lg" class="stat-xl" style="font-size: 32px; color: #e65100;">{{ $uniqueProductsCompared }}</div>
                <div class="section-subtitle-desc">منتجات فريدة مقارنة</div>
            </div>
            <div class="js-card-gradient-purple text-center-pad">
                <div class="js-stat-value-lg" class="stat-xl" style="font-size: 32px; color: #7b1fa2;">{{ number_format($avgProductsPerComparison, 1) }}</div>
                <div class="section-subtitle-desc">متوسط المنتجات/مقارنة</div>
            </div>
        </div>

        <div class="row g-4">
            {{-- Most Compared Products --}}
            <div class="col-lg-7">
                <div class="card" class="card-clean">
                    <div class="table-section-header">
                        <h5 class="section-title">🏆 أكثر المنتجات المقارنة</h5>
                    </div>
                    @if ($mostCompared->isEmpty())
                        <div class="empty-state-placeholder">لا توجد بيانات مقارنات بعد</div>
                    @else
                        <div class="p-8-0">
                            @foreach ($mostCompared as $index => $item)
                                @php $product = $item['product']; @endphp
                                <div class="row-cell-gap12 pad-md" class="row-cell-gap12 pad-md" data-row-border>
                                    <span class="step-circle" style="background: {{ $index < 3 ? '#ff9500' : '#f0f0f2' }}; color: {{ $index < 3 ? '#fff' : '#6e6e73' }};">
                                        {{ $index + 1 }}
                                    </span>
                                    <div class="icon-container-40 icon-container-subtle" style="border-radius: 8px; flex-shrink: 0;">
                                        @if ($product->images && is_array($product->images) && count($product->images) > 0)
                                            <img src="{{ asset($product->images[0]) }}" class="img-cover">
                                        @else
                                            <span class="text-14" style="font-size: 18px;">💊</span>
                                        @endif
                                    </div>
                                    <div class="flex-1-min">
                                        <div class="fw-600 text-14 text-heading" style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $product->productName }}</div>
                                        @if ($product->category)
                                            <div class="fs-11-color-muted">{{ $product->category->name }}</div>
                                        @endif
                                    </div>
                                    <span class="type-badge-blue" style="font-weight: 700; white-space: nowrap;">
                                        {{ $item['count'] }} مقارنة
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Daily Trend Chart --}}
            <div class="col-lg-5">
                <div class="card" class="card-clean">
                    <div class="table-section-header">
                        <h5 class="section-title">📊 الاتجاه اليومي (30 يوم)</h5>
                    </div>
                    <div class="card-body">
                        @if ($dailyTrend->isEmpty())
                            <div class="empty-state-center-sm">لا توجد بيانات كافية</div>
                        @else
                            @php
                                $maxComparisons = max($dailyTrend->pluck('comparisons')->max(), 1);
                            @endphp
                            <div style="display: flex; align-items: flex-end; gap: 4px; height: 160px; padding-bottom: 24px; position: relative;">
                                @foreach ($dailyTrend as $day)
                                    @php $height = ($day->comparisons / $maxComparisons) * 130; @endphp
                                    <div class="bar-col" title="{{ $day->date }}: {{ $day->comparisons }} مقارنة">
                                        <span class="bar-value">{{ $day->comparisons }}</span>
                                        <div class="bar-fill-animated" style="width: 100%; max-width: 20px; height: {{ $height }}px; background: linear-gradient(180deg, #007aff, #5ac8fa);"></div>
                                        @if ($loop->iteration % 5 === 0 || $loop->last)
                                            <span style="position: absolute; bottom: -20px; font-size: 9px; color: #86868b;">{{ \Carbon\Carbon::parse($day->date)->format('d/m') }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Top Shared Links --}}
                <div class="card mt-4" class="card-clean">
                    <div class="table-section-header">
                        <h5 class="section-title">🔗 أكثر روابط المشاركة مشاهدة</h5>
                    </div>
                    @if ($topShared->isEmpty())
                        <div class="empty-state-center-sm" style="padding: 24px;">لم تُ-share مقارنات بعد</div>
                    @else
                        <div class="p-8-0">
                            @foreach ($topShared as $record)
                                <div class="row-cell pad-md" class="row-cell-gap12" data-row-border>
                                    <span class="text-20">👁️</span>
                                    <div class="flex-1-min">
                                        <div class="fs-13-color-heading">
                                            {{ count($record->product_ids ?? []) }} منتجات
                                        </div>
                                        <div class="fs-11-color-muted">
                                            {{ $record->last_viewed_at?->diffForHumans() }}
                                        </div>
                                    </div>
                                    <span class="badge-green" style="padding: 3px 10px; font-size: 12px;">
                                        {{ $record->view_count }} مشاهدة
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
