@extends('layouts.admin')

@section('title', 'تقرير المبيعات')

@section('main_content')
<div class="container-fluid" class="card-body-lg">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="heading-bold">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#007aff" stroke-width="2" class="icon-align-lg">
                <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
            </svg>
            تقرير المبيعات
        </h4>
    </div>

    {{-- Date Range Filter --}}
    <div class="card mb-4" class="card-clean-bordered">
        <div class="card-body" class="p-16">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label-xs">من تاريخ</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control" class="input-clean-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label-xs">إلى تاريخ</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control" class="input-clean-sm">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn w-100" class="btn-primary-blue">بحث</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.sales-report.index') }}" class="btn w-100" style="background: #f5f5f7; color: #1d1d1f; border-radius: 10px; padding: 10px; font-weight: 600; text-decoration: none;">هذا الشهر</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="kpi-grid">
        <div class="card-gradient-green">
            <div class="badge-value-green">{{ number_format($summary->total_revenue, 2) }}</div>
            <div class="fs-12-color-green">إجمالي الإيرادات</div>
        </div>
        <div class="card-gradient-blue">
            <div class="stat-xl-blue">{{ number_format($summary->total_orders) }}</div>
            <div class="stat-label-green">إجمالي الطلبات</div>
        </div>
        <div class="card-gradient-amber">
            <div class="stat-xl-amber">{{ number_format($summary->avg_order_value, 2) }}</div>
            <div class="stat-label-amber">متوسط قيمة الطلب</div>
        </div>
        <div class="card-gradient-mint">
            <div class="badge-value-green">{{ number_format($summary->total_paid, 2) }}</div>
            <div class="fs-12-color-green">المدفوع</div>
        </div>
        <div class="card-gradient-red">
            <div class="stat-xl-red">{{ number_format($summary->total_due, 2) }}</div>
            <div class="stat-label-red">المستحق</div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Revenue by Day Chart --}}
        <div class="col-lg-8">
            <div class="card" class="card-clean">
                <div class="table-section-header">
                    <h5 class="section-title">📈 الإيرادات اليومية</h5>
                </div>
                <div class="card-body">
                    @if ($dailyRevenue->isEmpty())
                        <div class="empty-state-center">لا توجد مبيعات في الفترة المحددة</div>
                    @else
                        @php
                            $maxRevenue = max($dailyRevenue->pluck('revenue')->max(), 1);
                        @endphp
                        <div style="display: flex; align-items: flex-end; gap: 4px; height: 200px; padding-bottom: 30px; position: relative;">
                            @foreach ($dailyRevenue as $day)
                                @php $height = ($day->revenue / $maxRevenue) * 170; @endphp
                                <div class="bar-col" title="{{ $day->date }}: {{ number_format($day->revenue) }} ({{ $day->orders }} طلب)">
                                    <span class="bar-value">{{ number_format($day->revenue, 0) }}</span>
                                    <div data-bar-height data-bar-style="--bar-h: {{ $height }}"></div>
                                    @if ($loop->iteration % 5 === 0 || $loop->last || $loop->count <= 15)
                                        <span class="bar-date">{{ \Carbon\Carbon::parse($day->date)->format('d/m') }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Payment Type Breakdown --}}
        <div class="col-lg-4">
            <div class="card" class="card-clean">
                <div class="table-section-header">
                    <h5 class="section-title">💳 حسب نوع الدفع</h5>
                </div>
                <div class="card-body">
                    @php
                        $totalByType = $byPaymentType->sum('total');
                        $typeColors = ['cash' => '#34c759', 'card' => '#007aff', 'online' => '#5856d6', 'other' => '#86868b'];
                    @endphp
                    @if ($byPaymentType->isEmpty())
                        <div class="empty-state-center-sm">لا توجد بيانات</div>
                    @else
                        @foreach ($byPaymentType as $pt)
                            @php $pct = $totalByType > 0 ? ($pt->total / $totalByType) * 100 : 0; @endphp
                            <div class="progress-row">
                                <div class="progress-row-header">
                                    <span class="fs-13-color-heading">{{ ucfirst($pt->payment_type) }}</span>
                                    <span class="text-13" style="color: #86868b;">{{ number_format($pt->total) }} ({{ number_format($pct, 1) }}%)</span>
                                </div>
                                <div class="progress-bar-container-sm">
                                    <div class="progress-bar-fill-sm" style="width: {{ $pct }}%; background: {{ $typeColors[$pt->payment_type] ?? '#86868b' }};"></div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Top Products --}}
    <div class="card mt-4" class="card-clean">
        <div class="table-section-header">
            <h5 class="section-title">🏆 أكثر المنتجات مبيعاً</h5>
        </div>
        @if ($topProducts->isEmpty())
            <div class="empty-state-placeholder">لا توجد بيانات مبيعات</div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0" class="fs-14">
                    <thead class="bg-subtle">
                        <tr>
                            <th class="tab-btn">#</th>
                            <th class="tab-btn">المنتج</th>
                            <th class="tab-btn">الكمية المباعة</th>
                            <th class="tab-btn">الإيراد</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($topProducts as $product)
                            <tr class="border-bottom-light">
                                <td style="padding: 12px 16px; font-weight: 700; color: {{ $loop->index < 3 ? '#ff9500' : '#6e6e73' }}">{{ $loop->index + 1 }}</td>
                                <td class="card-body-sm">
                                    <div class="row-cell">
                                        <div class="icon-container-36 icon-container-subtle">
                                            @if ($product->images && is_array($product->images) && count($product->images) > 0)
                                                <img src="{{ asset($product->images[0]) }}" class="img-cover">
                                            @else
                                                <span class="text-16">💊</span>
                                            @endif
                                        </div>
                                        <span class="fw-600">{{ $product->productName }}</span>
                                    </div>
                                </td>
                                <td class="p-12-16-600">{{ number_format($product->total_qty) }}</td>
                                <td class="card-body-sm">
                                    <span class="badge-green">{{ number_format($product->total_revenue, 2) }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Recent Sales Table --}}
    <div class="card mt-4" class="card-clean">
        <div class="table-section-header">
            <h5 class="section-title">🧾 آخر المبيعات</h5>
        </div>
        @if ($recentSales->isEmpty())
            <div class="empty-state-placeholder">لا توجد مبيعات</div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0" class="fs-14">
                    <thead class="bg-subtle">
                        <tr>
                            <th class="tab-btn">رقم الفاتورة</th>
                            <th class="tab-btn">التاريخ</th>
                            <th class="tab-btn">العميل</th>
                            <th class="tab-btn">المندوب</th>
                            <th class="tab-btn">الإجمالي</th>
                            <th class="tab-btn">الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentSales as $sale)
                            <tr class="border-bottom-light">
                                <td class="card-body-sm"><code class="code-badge-sm">{{ $sale->invoiceNumber }}</code></td>
                                <td style="padding: 12px 16px; font-size: 13px; color: #6e6e73;">{{ $sale->saleDate ? \Carbon\Carbon::parse($sale->saleDate)->format('Y-m-d H:i') : '—' }}</td>
                                <td class="card-body-sm">{{ $sale->party->name ?? '—' }}</td>
                                <td class="card-body-sm">{{ $sale->user->name ?? '—' }}</td>
                                <td class="row-amount">{{ number_format($sale->totalAmount, 2) }}</td>
                                <td class="card-body-sm">
                                    @if ($sale->isPaid)
                                        <span class="badge-green-sm">مدفوع</span>
                                    @elseif ($sale->dueAmount > 0 && $sale->paidAmount > 0)
                                        <span class="badge-amber-sm">جزئي</span>
                                    @else
                                        <span class="badge-red-sm">غير مدفوع</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection
