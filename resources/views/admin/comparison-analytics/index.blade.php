@extends('layouts.master')

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
            <div class="js-card-gradient-info" style="padding: 20px; text-align: center;">
                <div class="js-stat-value-lg" style="font-size: 32px; color: #007aff;">{{ $totalComparisons }}</div>
                <div class="section-subtitle-desc">إجمالي المقارنات</div>
            </div>
            <div class="js-card-gradient-success" style="padding: 20px; text-align: center;">
                <div class="js-stat-value-lg" style="font-size: 32px; color: #2e7d32;">{{ $totalViews }}</div>
                <div class="section-subtitle-desc">إجمالي المشاهدات</div>
            </div>
            <div class="js-card-gradient-amber" style="padding: 20px; text-align: center;">
                <div class="js-stat-value-lg" style="font-size: 32px; color: #e65100;">{{ $uniqueProductsCompared }}</div>
                <div class="section-subtitle-desc">منتجات فريدة مقارنة</div>
            </div>
            <div class="js-card-gradient-purple" style="padding: 20px; text-align: center;">
                <div class="js-stat-value-lg" style="font-size: 32px; color: #7b1fa2;">{{ number_format($avgProductsPerComparison, 1) }}</div>
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
                                <div style="display: flex; align-items: center; gap: 12px; padding: 10px 20px; {{ !$loop->last ? 'border-bottom: 1px solid #f0f0f2;' : '' }}">
                                    <span style="width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; {{ $index < 3 ? 'background: #ff9500; color: #fff;' : 'background: #f0f0f2; color: #6e6e73;' }}">
                                        {{ $index + 1 }}
                                    </span>
                                    <div style="width: 40px; height: 40px; border-radius: 8px; background: #f5f5f7; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;">
                                        @if ($product->images && is_array($product->images) && count($product->images) > 0)
                                            <img src="{{ asset($product->images[0]) }}" style="width: 100%; height: 100%; object-fit: cover;">
                                        @else
                                            <span style="font-size: 18px;">💊</span>
                                        @endif
                                    </div>
                                    <div class="flex-1-min">
                                        <div style="font-weight: 600; font-size: 14px; color: #1d1d1f; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $product->productName }}</div>
                                        @if ($product->category)
                                            <div class="fs-11-color-muted">{{ $product->category->name }}</div>
                                        @endif
                                    </div>
                                    <span style="background: #e3f2fd; color: #1565c0; padding: 4px 10px; border-radius: 6px; font-size: 13px; font-weight: 700; white-space: nowrap;">
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
                            <div style="text-align: center; color: #86868b; padding: 20px;">لا توجد بيانات كافية</div>
                        @else
                            @php
                                $maxComparisons = max($dailyTrend->pluck('comparisons')->max(), 1);
                            @endphp
                            <div style="display: flex; align-items: flex-end; gap: 4px; height: 160px; padding-bottom: 24px; position: relative;">
                                @foreach ($dailyTrend as $day)
                                    @php $height = ($day->comparisons / $maxComparisons) * 130; @endphp
                                    <div style="flex: 1; display: flex; flex-direction: column; align-items: center; position: relative;" title="{{ $day->date }}: {{ $day->comparisons }} مقارنة">
                                        <span style="position: absolute; top: -18px; font-size: 10px; color: #6e6e73; font-weight: 600;">{{ $day->comparisons }}</span>
                                        <div style="width: 100%; max-width: 20px; height: {{ $height }}px; background: linear-gradient(180deg, #007aff, #5ac8fa); border-radius: 4px 4px 0 0; min-height: 2px; transition: height 0.3s;"></div>
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
                        <div style="padding: 24px; text-align: center; color: #86868b; font-size: 13px;">لم تُ-share مقارنات بعد</div>
                    @else
                        <div class="p-8-0">
                            @foreach ($topShared as $record)
                                <div style="display: flex; align-items: center; gap: 10px; padding: 10px 20px; {{ !$loop->last ? 'border-bottom: 1px solid #f0f0f2;' : '' }}">
                                    <span style="font-size: 20px;">👁️</span>
                                    <div class="flex-1-min">
                                        <div class="fs-13-color-heading">
                                            {{ count($record->product_ids ?? []) }} منتجات
                                        </div>
                                        <div class="fs-11-color-muted">
                                            {{ $record->last_viewed_at?->diffForHumans() }}
                                        </div>
                                    </div>
                                    <span style="background: #e8f5e9; color: #2e7d32; padding: 3px 10px; border-radius: 6px; font-size: 12px; font-weight: 700;">
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
