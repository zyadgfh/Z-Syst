@extends('layouts.master')

@section('title')
    {{ __('بطاقة الجرعة') }}
@endsection

@section('main_content')
<div class="container-fluid" style="max-width: 620px">
    <div class="card shadow-sm border-0" style="border-radius: 20px !important">
        <div class="card-body p-4">
            <div class="d-flex align-items-center gap-2 mb-3">
                <span style="font-size: 28px">💊</span>
                <div>
                    <h4 class="mb-0" style="letter-spacing: -0.022em; font-weight: 700">{{ __('تعليمات الجرعة') }}</h4>
                    <small class="text-muted">{{ $detail->sale?->invoiceNumber ?? ('#'.$detail->sale_id) }} · {{ optional($detail->sale?->saleDate)->format('Y-m-d') }}</small>
                </div>
            </div>

            <div class="p-3 mb-3" style="background: #f5f5f7; border-radius: 14px">
                <div class="fw-bold" style="font-size: 18px">{{ $detail->product?->name ?? $detail->product?->productName ?? ('#'.$detail->product_id) }}</div>
                <div class="mt-2" style="font-size: 20px; font-weight: 700; color: #0071e3; letter-spacing: -0.014em">
                    ⏰ {{ $detail->dose }}
                </div>
                @if($detail->dose_code && \App\Http\Controllers\Admin\DoseLabelController::SCHEDULES[$detail->dose_code] ?? false)
                    <div class="text-muted small mt-1">
                        {{ \App\Http\Controllers\Admin\DoseLabelController::SCHEDULES[$detail->dose_code][0] }}
                        ({{ $detail->dose_code }})
                    </div>
                @endif
                <div class="text-muted small mt-2">{{ __('الكمية') }}: {{ $detail->quantities }} · {{ __('الدفعة') }}: {{ $detail->batch_no ?? '—' }}</div>
            </div>

            <div class="text-muted small">
                🔍 {{ __('ده الباركود المدبوس من الملصق — امسحه في أي وقت لعرض التعليمات') }}
                <code class="ms-1">DOSE:{{ $detail->sale_id }}:{{ $detail->id }}</code>
            </div>
        </div>
    </div>
</div>
@endsection
