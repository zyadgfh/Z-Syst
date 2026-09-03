@extends('layouts.admin')

@section('title', 'تحليلات نقاط الولاء')

@section('main_content')
    <div class="container-fluid" class="card-body-lg">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('admin.loyalty.index') }}" class="link-blue">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                </a>
                <h4 class="heading-bold">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#007aff" stroke-width="2" class="icon-align-lg">
                        <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
                    </svg>
                    تحليلات نقاط الولاء
                </h4>
            </div>
        </div>

        {{-- Summary Cards --}}
        <div class="kpi-grid">
            <div class="card-gradient-green">
                <div class="badge-value-green">{{ number_format($totalIssued) }}</div>
                <div class="fs-12-color-green">نقاط صدرت</div>
            </div>
            <div class="card-gradient-blue">
                <div class="stat-xl-blue">{{ number_format($totalRedeemed) }}</div>
                <div class="stat-label-green">نقاط مستبدلة</div>
            </div>
            <div class="card-gradient-amber">
                <div class="stat-xl-amber">{{ number_format($activePoints) }}</div>
                <div class="stat-label-amber">نقاط نشطة</div>
            </div>
            <div class="card-gradient-red">
                <div class="stat-xl-red">{{ number_format($expiredPoints) }}</div>
                <div class="stat-label-red">نقاط منتهية</div>
            </div>
            <div class="card-gradient-purple">
                <div class="stat-xl-purple">{{ $uniqueMembers }}</div>
                <div class="stat-label-purple">أعضاء مسجلين</div>
            </div>
            <div class="card-gradient-teal">
                <div class="stat-xl-teal">{{ number_format($expiringSoonPoints) }}</div>
                <div class="stat-label-teal">تنتهي خلال 30 يوم</div>
            </div>
        </div>

        <div class="row g-4">
            {{-- Monthly Trend Chart --}}
            <div class="col-lg-8">
                <div class="card" class="card-clean">
                    <div class="table-section-header">
                        <h5 class="section-title">📈 الاتجاه الشهري (12 شهر)</h5>
                        <div class="chart-legend">
                            <span><span class="legend-dot legend-dot-green"></span>صدرت</span>
                            <span><span class="legend-dot legend-dot-blue"></span>مستبدلة</span>
                            <span><span class="legend-dot legend-dot-amber"></span>منتهية</span>
                        </div>
                    </div>
                    <div class="card-body">
                        @if ($monthlyTrend->isEmpty())
                            <div class="empty-state-center">لا توجد بيانات كافية — سيظهر الرسم بعد استخدام نقاط الولاء</div>
                        @else
                            @php
                                $maxVal = max($monthlyTrend->pluck('earned')->max(), $monthlyTrend->pluck('redeemed')->max(), 1);
                            @endphp
                            <div style="display: flex; align-items: flex-end; gap: 8px; height: 200px; padding-bottom: 30px; position: relative;">
                                @foreach ($monthlyTrend as $month)
                                    @php
                                        $hEarned = ($month->earned / $maxVal) * 170;
                                        $hRedeemed = ($month->redeemed / $maxVal) * 170;
                                        $hExpired = ($month->expired / $maxVal) * 170;
                                    @endphp
                                    <div style="flex: 1; display: flex; gap: 2px; align-items: flex-end; position: relative;" title="{{ $month->month }}: صدرت {{ number_format($month->earned) }} / مستبدلة {{ number_format($month->redeemed) }}">
                                        <div class="flex-1" data-bar-height data-bar-green data-bar-style="--bar-h: {{ $hEarned }}"></div>
                                        <div class="flex-1" data-bar-height data-bar-blue data-bar-style="--bar-h: {{ $hRedeemed }}"></div>
                                        <div class="flex-1" data-bar-height data-bar-amber data-bar-style="--bar-h: {{ $hExpired }}"></div>
                                        @if ($loop->iteration % 2 === 0 || $loop->last)
                                            <span class="bar-date-center">{{ \Carbon\Carbon::parse($month->month . '-01')->format('M') }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Points by Type (Pie-like) --}}
            <div class="col-lg-4">
                <div class="card" class="card-clean">
                    <div class="table-section-header">
                        <h5 class="section-title">🔵 توزيع النقاط حسب النوع</h5>
                    </div>
                    <div class="card-body">
                        @php
                            $total = $byType->sum();
                            $typeColors = ['earned' => '#34c759', 'redeemed' => '#007aff', 'expired' => '#ff9500'];
                            $typeLabels = ['earned' => 'صدرت', 'redeemed' => 'مستبدلة', 'expired' => 'منتهية'];
                        @endphp
                        @foreach ($byType as $type => $count)
                            @php $pct = $total > 0 ? ($count / $total) * 100 : 0; @endphp
                            <div class="progress-row">
                                <div class="progress-row-header">
                                    <span class="fs-13-color-heading">{{ $typeLabels[$type] ?? $type }}</span>
                                    <span class="text-13 text-muted-sm">{{ number_format($count) }} ({{ number_format($pct, 1) }}%)</span>
                                </div>
                                <div class="progress-bar-container">
                                    <div class="progress-bar-fill" style="width: {{ $pct }}%; background: {{ $typeColors[$type] ?? '#86868b' }};"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Top Earners --}}
        <div class="card mt-4" class="card-clean">
            <div class="table-section-header">
                <h5 class="section-title">🏆 أكثر الأعضاء كسباً للنقاط</h5>
            </div>
            @if ($topEarners->isEmpty())
                <div class="empty-state-placeholder">لا توجد بيانات بعد</div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0" class="fs-14">
                        <thead class="bg-subtle">
                            <tr>
                                <th class="tab-btn">#</th>
                                <th class="tab-btn">العضو</th>
                                <th class="tab-btn">النقاط المكتسبة</th>
                                <th class="tab-btn">عدد المعاملات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($topEarners as $item)
                                @php $user = $item['user']; @endphp
                                <tr class="border-bottom-light">
                                    <td style="padding: 12px 16px; font-weight: 700; color: {{ $loop->index < 3 ? '#ff9500' : '#6e6e73' }}">{{ $loop->index + 1 }}</td>
                                    <td class="card-body-sm">
                                        <div class="d-flex-center">
                                            @if ($user?->image)
                                                <img src="{{ asset($user->image) }}" class="avatar-sm">
                                            @else
                                                <div class="avatar-placeholder">{{ substr($user->name ?? '?', 0, 1) }}</div>
                                            @endif
                                            <div>
                                                <div class="fw-600">{{ $user->name ?? 'غير معروف' }}</div>
                                                <div class="fs-11-color-muted">{{ $user->email ?? '' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="card-body-sm">
                                        <span class="badge-green">{{ number_format($item['total_earned']) }} نقطة</span>
                                    </td>
                                    <td class="p-12-16-600">{{ $item['transactions'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
