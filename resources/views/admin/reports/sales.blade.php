@extends('layouts.master')

@section('title', 'تقرير المبيعات')

@section('main_content')
<div class="container-fluid" style="padding: 24px;">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 style="font-weight: 700; margin: 0;">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#007aff" stroke-width="2" style="vertical-align: middle; margin-right: 6px;">
                <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>
            </svg>
            تقرير المبيعات
        </h4>
    </div>

    {{-- Date Range Filter --}}
    <div class="card mb-4" style="border-radius: 14px; border: 1px solid #e5e5ea;">
        <div class="card-body" style="padding: 16px;">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label style="font-size: 12px; font-weight: 600; color: #6e6e73; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; display: block;">من تاريخ</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control" style="border-radius: 10px; border: 1px solid #d2d2d7; padding: 10px 14px;">
                </div>
                <div class="col-md-3">
                    <label style="font-size: 12px; font-weight: 600; color: #6e6e73; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 4px; display: block;">إلى تاريخ</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="form-control" style="border-radius: 10px; border: 1px solid #d2d2d7; padding: 10px 14px;">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn w-100" style="background: #007aff; color: #fff; border-radius: 10px; padding: 10px; font-weight: 600;">بحث</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.sales-report.index') }}" class="btn w-100" style="background: #f5f5f7; color: #1d1d1f; border-radius: 10px; padding: 10px; font-weight: 600; text-decoration: none;">هذا الشهر</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 24px;">
        <div style="background: linear-gradient(135deg, #e8f5e9, #c8e6c9); border-radius: 14px; padding: 20px; text-align: center;">
            <div style="font-size: 28px; font-weight: 800; color: #2e7d32;">{{ number_format($summary->total_revenue, 2) }}</div>
            <div style="font-size: 12px; color: #4a6e4a; margin-top: 4px;">إجمالي الإيرادات</div>
        </div>
        <div style="background: linear-gradient(135deg, #e3f2fd, #bbdefb); border-radius: 14px; padding: 20px; text-align: center;">
            <div style="font-size: 28px; font-weight: 800; color: #1565c0;">{{ number_format($summary->total_orders) }}</div>
            <div style="font-size: 12px; color: #4a6e73; margin-top: 4px;">إجمالي الطلبات</div>
        </div>
        <div style="background: linear-gradient(135deg, #fff3e0, #ffe0b2); border-radius: 14px; padding: 20px; text-align: center;">
            <div style="font-size: 28px; font-weight: 800; color: #e65100;">{{ number_format($summary->avg_order_value, 2) }}</div>
            <div style="font-size: 12px; color: #8a6e4a; margin-top: 4px;">متوسط قيمة الطلب</div>
        </div>
        <div style="background: linear-gradient(135deg, #d4edda, #b2dfdb); border-radius: 14px; padding: 20px; text-align: center;">
            <div style="font-size: 28px; font-weight: 800; color: #2e7d32;">{{ number_format($summary->total_paid, 2) }}</div>
            <div style="font-size: 12px; color: #4a6e4a; margin-top: 4px;">المدفوع</div>
        </div>
        <div style="background: linear-gradient(135deg, #fce4ec, #f8bbd0); border-radius: 14px; padding: 20px; text-align: center;">
            <div style="font-size: 28px; font-weight: 800; color: #c62828;">{{ number_format($summary->total_due, 2) }}</div>
            <div style="font-size: 12px; color: #8a4a4a; margin-top: 4px;">المستحق</div>
        </div>
    </div>

    <div class="row g-4">
        {{-- Revenue by Day Chart --}}
        <div class="col-lg-8">
            <div class="card" style="border-radius: 14px; border: 1px solid #e5e5ea; overflow: hidden;">
                <div style="padding: 16px 20px; border-bottom: 1px solid #f0f0f2; background: #fafafa;">
                    <h5 style="margin: 0; font-size: 16px; font-weight: 700;">📈 الإيرادات اليومية</h5>
                </div>
                <div style="padding: 20px;">
                    @if ($dailyRevenue->isEmpty())
                        <div style="text-align: center; color: #86868b; padding: 40px;">لا توجد مبيعات في الفترة المحددة</div>
                    @else
                        @php
                            $maxRevenue = max($dailyRevenue->pluck('revenue')->max(), 1);
                        @endphp
                        <div style="display: flex; align-items: flex-end; gap: 4px; height: 200px; padding-bottom: 30px; position: relative;">
                            @foreach ($dailyRevenue as $day)
                                @php $height = ($day->revenue / $maxRevenue) * 170; @endphp
                                <div style="flex: 1; display: flex; flex-direction: column; align-items: center; position: relative;" title="{{ $day->date }}: {{ number_format($day->revenue) }} ({{ $day->orders }} طلب)">
                                    <span style="position: absolute; top: -18px; font-size: 10px; color: #6e6e73; font-weight: 600;">{{ number_format($day->revenue, 0) }}</span>
                                    <div style="width: 100%; max-width: 24px; height: {{ $height }}px; background: linear-gradient(180deg, #007aff, #5ac8fa); border-radius: 4px 4px 0 0; min-height: 2px;"></div>
                                    @if ($loop->iteration % 5 === 0 || $loop->last || $loop->count <= 15)
                                        <span style="position: absolute; bottom: -20px; font-size: 9px; color: #86868b; white-space: nowrap;">{{ \Carbon\Carbon::parse($day->date)->format('d/m') }}</span>
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
            <div class="card" style="border-radius: 14px; border: 1px solid #e5e5ea; overflow: hidden;">
                <div style="padding: 16px 20px; border-bottom: 1px solid #f0f0f2; background: #fafafa;">
                    <h5 style="margin: 0; font-size: 16px; font-weight: 700;">💳 حسب نوع الدفع</h5>
                </div>
                <div style="padding: 20px;">
                    @php
                        $totalByType = $byPaymentType->sum('total');
                        $typeColors = ['cash' => '#34c759', 'card' => '#007aff', 'online' => '#5856d6', 'other' => '#86868b'];
                    @endphp
                    @if ($byPaymentType->isEmpty())
                        <div style="text-align: center; color: #86868b; padding: 20px;">لا توجد بيانات</div>
                    @else
                        @foreach ($byPaymentType as $pt)
                            @php $pct = $totalByType > 0 ? ($pt->total / $totalByType) * 100 : 0; @endphp
                            <div style="margin-bottom: 16px;">
                                <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                                    <span style="font-size: 13px; font-weight: 600; color: #1d1d1f;">{{ ucfirst($pt->payment_type) }}</span>
                                    <span style="font-size: 13px; color: #86868b;">{{ number_format($pt->total) }} ({{ number_format($pct, 1) }}%)</span>
                                </div>
                                <div style="height: 8px; background: #f0f0f2; border-radius: 4px; overflow: hidden;">
                                    <div style="height: 100%; width: {{ $pct }}%; background: {{ $typeColors[$pt->payment_type] ?? '#86868b' }}; border-radius: 4px;"></div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Top Products --}}
    <div class="card mt-4" style="border-radius: 14px; border: 1px solid #e5e5ea; overflow: hidden;">
        <div style="padding: 16px 20px; border-bottom: 1px solid #f0f0f2; background: #fafafa;">
            <h5 style="margin: 0; font-size: 16px; font-weight: 700;">🏆 أكثر المنتجات مبيعاً</h5>
        </div>
        @if ($topProducts->isEmpty())
            <div style="padding: 32px; text-align: center; color: #86868b;">لا توجد بيانات مبيعات</div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="font-size: 14px;">
                    <thead style="background: #f8f9fa;">
                        <tr>
                            <th style="border: none; padding: 12px 16px; font-weight: 600; color: #6e6e73; font-size: 12px;">#</th>
                            <th style="border: none; padding: 12px 16px; font-weight: 600; color: #6e6e73; font-size: 12px;">المنتج</th>
                            <th style="border: none; padding: 12px 16px; font-weight: 600; color: #6e6e73; font-size: 12px;">الكمية المباعة</th>
                            <th style="border: none; padding: 12px 16px; font-weight: 600; color: #6e6e73; font-size: 12px;">الإيراد</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($topProducts as $product)
                            <tr style="border-bottom: 1px solid #f0f0f2;">
                                <td style="padding: 12px 16px; font-weight: 700; color: {{ $loop->index < 3 ? '#ff9500' : '#6e6e73' }};">{{ $loop->index + 1 }}</td>
                                <td style="padding: 12px 16px;">
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div style="width: 36px; height: 36px; border-radius: 8px; background: #f5f5f7; display: flex; align-items: center; justify-content: center; overflow: hidden; flex-shrink: 0;">
                                            @if ($product->images && is_array($product->images) && count($product->images) > 0)
                                                <img src="{{ asset($product->images[0]) }}" style="width: 100%; height: 100%; object-fit: cover;">
                                            @else
                                                <span style="font-size: 16px;">💊</span>
                                            @endif
                                        </div>
                                        <span style="font-weight: 600;">{{ $product->productName }}</span>
                                    </div>
                                </td>
                                <td style="padding: 12px 16px; font-weight: 600;">{{ number_format($product->total_qty) }}</td>
                                <td style="padding: 12px 16px;">
                                    <span style="background: #e8f5e9; color: #2e7d32; padding: 4px 10px; border-radius: 6px; font-weight: 700;">{{ number_format($product->total_revenue, 2) }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Recent Sales Table --}}
    <div class="card mt-4" style="border-radius: 14px; border: 1px solid #e5e5ea; overflow: hidden;">
        <div style="padding: 16px 20px; border-bottom: 1px solid #f0f0f2; background: #fafafa;">
            <h5 style="margin: 0; font-size: 16px; font-weight: 700;">🧾 آخر المبيعات</h5>
        </div>
        @if ($recentSales->isEmpty())
            <div style="padding: 32px; text-align: center; color: #86868b;">لا توجد مبيعات</div>
        @else
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="font-size: 14px;">
                    <thead style="background: #f8f9fa;">
                        <tr>
                            <th style="border: none; padding: 12px 16px; font-weight: 600; color: #6e6e73; font-size: 12px;">رقم الفاتورة</th>
                            <th style="border: none; padding: 12px 16px; font-weight: 600; color: #6e6e73; font-size: 12px;">التاريخ</th>
                            <th style="border: none; padding: 12px 16px; font-weight: 600; color: #6e6e73; font-size: 12px;">العميل</th>
                            <th style="border: none; padding: 12px 16px; font-weight: 600; color: #6e6e73; font-size: 12px;">المندوب</th>
                            <th style="border: none; padding: 12px 16px; font-weight: 600; color: #6e6e73; font-size: 12px;">الإجمالي</th>
                            <th style="border: none; padding: 12px 16px; font-weight: 600; color: #6e6e73; font-size: 12px;">الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentSales as $sale)
                            <tr style="border-bottom: 1px solid #f0f0f2;">
                                <td style="padding: 12px 16px;"><code style="background: #f5f5f7; padding: 3px 8px; border-radius: 6px; font-size: 13px;">{{ $sale->invoiceNumber }}</code></td>
                                <td style="padding: 12px 16px; font-size: 13px; color: #6e6e73;">{{ $sale->saleDate ? \Carbon\Carbon::parse($sale->saleDate)->format('Y-m-d H:i') : '—' }}</td>
                                <td style="padding: 12px 16px;">{{ $sale->party->name ?? '—' }}</td>
                                <td style="padding: 12px 16px;">{{ $sale->user->name ?? '—' }}</td>
                                <td style="padding: 12px 16px; font-weight: 700;">{{ number_format($sale->totalAmount, 2) }}</td>
                                <td style="padding: 12px 16px;">
                                    @if ($sale->isPaid)
                                        <span style="background: #d4edda; color: #155724; padding: 3px 8px; border-radius: 6px; font-size: 12px; font-weight: 600;">مدفوع</span>
                                    @elseif ($sale->dueAmount > 0 && $sale->paidAmount > 0)
                                        <span style="background: #fff3e0; color: #e65100; padding: 3px 8px; border-radius: 6px; font-size: 12px; font-weight: 600;">جزئي</span>
                                    @else
                                        <span style="background: #f8d7da; color: #721c24; padding: 3px 8px; border-radius: 6px; font-size: 12px; font-weight: 600;">غير مدفوع</span>
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
