@extends('layouts.master')

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
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px;">
            <div style="background: linear-gradient(135deg, #e8f5e9, #c8e6c9); border-radius: 14px; padding: 20px; text-align: center;">
                <div style="font-size: 28px; font-weight: 800; color: #2e7d32;">{{ number_format($totalIssued) }}</div>
                <div style="font-size: 12px; color: #4a6e4a; margin-top: 4px;">نقاط صدرت</div>
            </div>
            <div style="background: linear-gradient(135deg, #e3f2fd, #bbdefb); border-radius: 14px; padding: 20px; text-align: center;">
                <div style="font-size: 28px; font-weight: 800; color: #1565c0;">{{ number_format($totalRedeemed) }}</div>
                <div style="font-size: 12px; color: #4a6e73; margin-top: 4px;">نقاط مستبدلة</div>
            </div>
            <div style="background: linear-gradient(135deg, #fff3e0, #ffe0b2); border-radius: 14px; padding: 20px; text-align: center;">
                <div style="font-size: 28px; font-weight: 800; color: #e65100;">{{ number_format($activePoints) }}</div>
                <div style="font-size: 12px; color: #8a6e4a; margin-top: 4px;">نقاط نشطة</div>
            </div>
            <div style="background: linear-gradient(135deg, #fce4ec, #f8bbd0); border-radius: 14px; padding: 20px; text-align: center;">
                <div style="font-size: 28px; font-weight: 800; color: #c62828;">{{ number_format($expiredPoints) }}</div>
                <div style="font-size: 12px; color: #8a4a4a; margin-top: 4px;">نقاط منتهية</div>
            </div>
            <div style="background: linear-gradient(135deg, #f3e5f5, #e1bee7); border-radius: 14px; padding: 20px; text-align: center;">
                <div style="font-size: 28px; font-weight: 800; color: #7b1fa2;">{{ $uniqueMembers }}</div>
                <div style="font-size: 12px; color: #6e4a6e; margin-top: 4px;">أعضاء مسجلين</div>
            </div>
            <div style="background: linear-gradient(135deg, #e0f7fa, #b2ebf2); border-radius: 14px; padding: 20px; text-align: center;">
                <div style="font-size: 28px; font-weight: 800; color: #00695c;">{{ number_format($expiringSoonPoints) }}</div>
                <div style="font-size: 12px; color: #4a6e6e; margin-top: 4px;">تنتهي خلال 30 يوم</div>
            </div>
        </div>

        <div class="row g-4">
            {{-- Monthly Trend Chart --}}
            <div class="col-lg-8">
                <div class="card" class="card-clean">
                    <div class="table-section-header">
                        <h5 class="section-title">📈 الاتجاه الشهري (12 شهر)</h5>
                        <div style="display: flex; gap: 16px; margin-top: 8px; font-size: 12px;">
                            <span><span style="display: inline-block; width: 12px; height: 12px; border-radius: 3px; background: #34c759; margin-right: 4px;"></span>صدرت</span>
                            <span><span style="display: inline-block; width: 12px; height: 12px; border-radius: 3px; background: #007aff; margin-right: 4px;"></span>مستبدلة</span>
                            <span><span style="display: inline-block; width: 12px; height: 12px; border-radius: 3px; background: #ff9500; margin-right: 4px;"></span>منتهية</span>
                        </div>
                    </div>
                    <div class="card-body">
                        @if ($monthlyTrend->isEmpty())
                            <div style="text-align: center; color: #86868b; padding: 40px;">لا توجد بيانات كافية — سيظهر الرسم بعد استخدام نقاط الولاء</div>
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
                                        <div style="flex: 1; height: {{ $hEarned }}px; background: #34c759; border-radius: 3px 3px 0 0; min-height: 2px;"></div>
                                        <div style="flex: 1; height: {{ $hRedeemed }}px; background: #007aff; border-radius: 3px 3px 0 0; min-height: 2px;"></div>
                                        <div style="flex: 1; height: {{ $hExpired }}px; background: #ff9500; border-radius: 3px 3px 0 0; min-height: 2px;"></div>
                                        @if ($loop->iteration % 2 === 0 || $loop->last)
                                            <span style="position: absolute; bottom: -22px; left: 50%; transform: translateX(-50%); font-size: 10px; color: #86868b; white-space: nowrap;">{{ \Carbon\Carbon::parse($month->month . '-01')->format('M') }}</span>
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
                            <div style="margin-bottom: 16px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                                    <span style="font-size: 13px; font-weight: 600; color: #1d1d1f;">{{ $typeLabels[$type] ?? $type }}</span>
                                    <span style="font-size: 13px; color: #86868b;">{{ number_format($count) }} ({{ number_format($pct, 1) }}%)</span>
                                </div>
                                <div style="height: 10px; background: #f0f0f2; border-radius: 5px; overflow: hidden;">
                                    <div style="height: 100%; width: {{ $pct }}%; background: {{ $typeColors[$type] ?? '#86868b' }}; border-radius: 5px; transition: width 0.5s;"></div>
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
                        <thead style="background: #f8f9fa;">
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
                                    <td style="padding: 12px 16px; font-weight: 700; color: {{ $loop->index < 3 ? '#ff9500' : '#6e6e73' }};">{{ $loop->index + 1 }}</td>
                                    <td class="card-body-sm">
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            @if ($user?->image)
                                                <img src="{{ asset($user->image) }}" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">
                                            @else
                                                <div style="width: 32px; height: 32px; border-radius: 50%; background: #e8f0fe; color: #007aff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px;">{{ substr($user->name ?? '?', 0, 1) }}</div>
                                            @endif
                                            <div>
                                                <div style="font-weight: 600;">{{ $user->name ?? 'غير معروف' }}</div>
                                                <div style="font-size: 11px; color: #86868b;">{{ $user->email ?? '' }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="card-body-sm">
                                        <span style="background: #e8f5e9; color: #2e7d32; padding: 4px 10px; border-radius: 6px; font-weight: 700;">{{ number_format($item['total_earned']) }} نقطة</span>
                                    </td>
                                    <td style="padding: 12px 16px; font-weight: 600;">{{ $item['transactions'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
