@extends('layouts.master')

@section('title', 'نقاط الولاء')

@section('main_content')
<div class="container-fluid" style="padding: 24px;">
    <h4 style="font-weight: 700; margin-bottom: 24px;">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 6px;">
            <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
        </svg>
        نقاط الولاء
    </h4>

    {{-- Stats Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card" style="border-radius: 14px; border: 1px solid #e5e5ea; background: linear-gradient(135deg, #fff8e1, #fff);">
                <div class="card-body" style="padding: 20px; text-align: center;">
                    <p style="font-size: 12px; color: #86868b; margin: 0; text-transform: uppercase; letter-spacing: 0.05em;">رصيدك الحالي</p>
                    <h2 style="font-weight: 700; color: #f57f17; margin: 8px 0 0; font-size: 32px;">{{ number_format($stats['balance']) }}</h2>
                    <p style="font-size: 13px; color: #86868b; margin: 4px 0 0;">نقطة</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card" style="border-radius: 14px; border: 1px solid #e5e5ea;">
                <div class="card-body" style="padding: 20px; text-align: center;">
                    <p style="font-size: 12px; color: #86868b; margin: 0; text-transform: uppercase;">إجمالي المكتسب</p>
                    <h3 style="font-weight: 700; color: #2e7d32; margin: 6px 0 0;">{{ number_format($stats['earned']) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card" style="border-radius: 14px; border: 1px solid #e5e5ea;">
                <div class="card-body" style="padding: 20px; text-align: center;">
                    <p style="font-size: 12px; color: #86868b; margin: 0; text-transform: uppercase;">إجمالي المستبدل</p>
                    <h3 style="font-weight: 700; color: #ff3b30; margin: 6px 0 0;">{{ number_format($stats['redeemed']) }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card" style="border-radius: 14px; border: 1px solid #e5e5ea;">
                <div class="card-body" style="padding: 20px; text-align: center;">
                    <p style="font-size: 12px; color: #86868b; margin: 0; text-transform: uppercase;">نسبة الاستبدال</p>
                    <h3 style="font-weight: 700; color: #007aff; margin: 6px 0 0;">{{ $stats['conversion'] }}:1</h3>
                    <p style="font-size: 12px; color: #86868b; margin: 2px 0 0;">نقطة = $1</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Redeem Section --}}
    @if ($stats['balance'] > 0)
    <div class="card mb-4" style="border-radius: 14px; border: 1px solid #e5e5ea;">
        <div class="card-body" style="padding: 20px;">
            <h6 style="font-weight: 600; margin-bottom: 12px;">استبدال النقاط</h6>
            <div class="d-flex align-items-center gap-3">
                <form action="{{ route('customer.loyalty.redeem') }}" method="POST" class="d-flex align-items-center gap-3" onsubmit="return confirm('تأكيد الاستبدال؟')">
                    @csrf
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <input type="number" name="points" min="1" max="{{ $stats['balance'] }}" value="{{ $stats['conversion'] }}"
                            style="width: 120px; padding: 10px 14px; border-radius: 10px; border: 1px solid #d2d2d7; font-size: 15px; font-weight: 600;" placeholder="النقاط">
                        <span style="font-size: 14px; color: #86868b;">=</span>
                        <span id="redeem-value" style="font-size: 16px; font-weight: 700; color: #2e7d32; min-width: 60px;">$1.00</span>
                    </div>
                    <button type="submit" class="btn" style="background: #f57f17; color: #fff; border-radius: 10px; padding: 10px 20px; font-weight: 600; transition: transform 150ms ease;" onmousedown="this.style.transform='scale(0.97)'" onmouseup="this.style.transform='scale(1)'">
                        استبدال
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Success/Error --}}
    @if (session('success'))
        <div style="background: #d4edda; color: #155724; border-radius: 12px; padding: 12px 16px; margin-bottom: 16px; font-size: 14px;">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div style="background: #fff0f0; color: #ff3b30; border-radius: 12px; padding: 12px 16px; margin-bottom: 16px; font-size: 14px;">{{ session('error') }}</div>
    @endif

    {{-- Transactions --}}
    <div class="card" style="border-radius: 14px; border: 1px solid #e5e5ea; overflow: hidden;">
        <div class="card-body" style="padding: 20px;">
            <h6 style="font-weight: 600; margin-bottom: 16px;">سجل المعاملات</h6>
            <div class="table-responsive">
                <table class="table table-hover mb-0" style="font-size: 14px;">
                    <thead style="background: #f5f5f7;">
                        <tr>
                            <th style="border: none; padding: 10px 14px; font-size: 12px; text-transform: uppercase; color: #6e6e73;">التاريخ</th>
                            <th style="border: none; padding: 10px 14px; font-size: 12px; text-transform: uppercase; color: #6e6e73;">النوع</th>
                            <th style="border: none; padding: 10px 14px; font-size: 12px; text-transform: uppercase; color: #6e6e73;">النقاط</th>
                            <th style="border: none; padding: 10px 14px; font-size: 12px; text-transform: uppercase; color: #6e6e73;">الوصف</th>
                            <th style="border: none; padding: 10px 14px; font-size: 12px; text-transform: uppercase; color: #6e6e73;">تاريخ الانتهاء</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions as $tx)
                            <tr style="border-bottom: 1px solid #f0f0f2;">
                                <td style="padding: 12px 14px; color: #86868b; font-size: 13px;">{{ $tx->created_at->format('Y-m-d H:i') }}</td>
                                <td style="padding: 12px 14px;">
                                    <span style="background: {{ match($tx->type) { 'earned' => '#d4edda', 'redeemed' => '#f8d7da', default => '#e2e3e5' }}; color: {{ match($tx->type) { 'earned' => '#155724', 'redeemed' => '#721c24', default => '#383d41' }}}; padding: 3px 10px; border-radius: 6px; font-size: 12px; font-weight: 600;">
                                        {{ match($tx->type) { 'earned' => 'مكتسب', 'redeemed' => 'مستبدل', 'expired' => 'منتهي', default => 'تعديل' } }}
                                    </span>
                                </td>
                                <td style="padding: 12px 14px; font-weight: 700; color: {{ $tx->points > 0 ? '#2e7d32' : '#ff3b30' }};">
                                    {{ $tx->points > 0 ? '+' : '' }}{{ number_format($tx->points) }}
                                </td>
                                <td style="padding: 12px 14px; color: #424245;">{{ $tx->description ?? '—' }}</td>
                                <td style="padding: 12px 14px; font-size: 13px;">
                                    @if ($tx->expires_at && $tx->type === 'earned')
                                        @if ($tx->expires_at->isPast())
                                            <span style="color: #ff3b30; font-weight: 600;">منتهي</span>
                                        @elseif ($tx->expires_at->diffInDays(now()) <= 30)
                                            <span style="color: #ff9500; font-weight: 600;">{{ $tx->expires_at->format('Y-m-d') }}</span>
                                            <span style="font-size: 11px; color: #86868b;"> ({{ $tx->expires_at->diffForHumans() }})</span>
                                        @else
                                            <span style="color: #6e6e73;">{{ $tx->expires_at->format('Y-m-d') }}</span>
                                        @endif
                                    @else
                                        <span style="color: #86868b;">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4" style="color: #86868b;">لا توجد معاملات بعد</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-center mt-3">
        {{ $transactions->links() }}
    </div>
</div>

@push('scripts')
<script>
    const rate = {{ $stats['conversion'] }};
    const input = document.querySelector('input[name="points"]');
    const display = document.getElementById('redeem-value');
    if (input && display) {
        input.addEventListener('input', () => {
            const pts = parseInt(input.value) || 0;
            display.textContent = '$' + (pts / rate).toFixed(2);
        });
    }
</script>
@endpush
@endsection
