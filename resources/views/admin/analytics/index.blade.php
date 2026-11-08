@extends('layouts.master')
@section('title') {{ __('التحليلات') }} @endsection
@section('main_content')
<div class="container-fluid">
    @if($anomalies->isNotEmpty())
    <div class="alert alert-warning">🚨 {{ __('تنبيهات شذوذ مالي') }}:
        <ul class="mb-0 mt-1">
            @foreach($anomalies as $a)
            <li><b>[{{ $a->severity }}]</b> {{ $a->title }} <small class="text-muted">({{ $a->type }})</small></li>
            @endforeach
        </ul>
    </div>
    @endif
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card"><div class="card-body table-responsive">
                <h5>📊 {{ __('تصنيف ABC (90 يوم)') }} <small class="text-muted">A=80% من الإيراد</small></h5>
                <table class="table table-sm">
                    <tr><th>{{ __('الصنف') }}</th><th>{{ __('الإيراد') }}</th><th>%</th><th>Σ%</th><th>ABC</th></tr>
                    @foreach($abc as $r)
                    <tr class="{{ $r['class'] === 'A' ? 'table-success' : '' }}">
                        <td>{{ $r['name'] }}</td><td>{{ number_format($r['revenue'], 0) }}</td>
                        <td>{{ $r['share'] }}</td><td>{{ $r['cumulative'] }}</td>
                        <td><span class="badge {{ $r['class'] === 'A' ? 'bg-success' : ($r['class'] === 'B' ? 'bg-warning' : 'bg-secondary') }}">{{ $r['class'] }}</span></td>
                    </tr>
                    @endforeach
                </table>
            </div></div>
        </div>
        <div class="col-lg-6">
            <div class="card mb-3"><div class="card-body table-responsive">
                <h5>🕸 {{ __('مخزون راكد (بلا حركة 60 يوم)') }}</h5>
                <table class="table table-sm">
                    <tr><th>{{ __('الصنف') }}</th><th>{{ __('الكمية') }}</th><th>{{ __('القيمة') }}</th></tr>
                    @foreach($dead as $r)
                    <tr><td>{{ $r['name'] }}</td><td>{{ $r['qty'] }}</td><td>{{ number_format($r['value'], 0) }}</td></tr>
                    @endforeach
                    @if(!$dead)<tr><td colspan="3" class="text-muted">{{ __('لا مخزون راكد 👍') }}</td></tr>@endif
                </table>
            </div></div>
            <div class="card"><div class="card-body table-responsive">
                <h5>💹 {{ __('الأعلى ربحًا (30 يوم)') }}</h5>
                <table class="table table-sm">
                    <tr><th>{{ __('الصنف') }}</th><th>{{ __('كمية') }}</th><th>{{ __('ربح') }}</th><th>{{ __('هامش') }}</th></tr>
                    @foreach($margins as $r)
                    <tr><td>{{ $r->name }}</td><td>{{ $r->qty }}</td><td>{{ number_format($r->profit, 0) }}</td><td>{{ $r->margin }}%</td></tr>
                    @endforeach
                </table>
            </div></div>
        </div>
    </div>
</div>
@endsection
