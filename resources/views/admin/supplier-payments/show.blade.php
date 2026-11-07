@extends('layouts.master')

@section('title')
    {{ __('Supplier Payment') }}
@endsection

@section('main_content')

<div class="container-fluid">
    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">{{ __('Payment') }}: {{ $payment->payment_number }}</h4>
            <span class="badge bg-secondary">{{ $payment->status }}</span>
        </div>
        <div class="row g-3">
            <div class="col-md-4"><strong>{{ __('Supplier') }}:</strong> {{ $payment->supplier?->company_name ?? $payment->supplier_id }}</div>
            <div class="col-md-4"><strong>{{ __('Amount') }}:</strong> {{ $payment->amount }}</div>
            <div class="col-md-4"><strong>{{ __('Date') }}:</strong> {{ $payment->payment_date }}</div>
            <div class="col-md-4"><strong>{{ __('Method') }}:</strong> {{ $payment->payment_method }}</div>
            <div class="col-md-4"><strong>{{ __('Approved at') }}:</strong> {{ $payment->approved_at ?? '—' }}</div>
        </div>
        <div class="d-flex gap-2 mt-4">
            @if($payment->status === 'pending')
                <form action="{{ route('admin.supplier-payments.approve', $payment) }}" method="POST">@csrf<button class="btn btn-success">{{ __('Approve') }}</button></form>
            @endif
            <a href="{{ route('admin.supplier-payments.edit', $payment) }}" class="btn btn-outline-secondary">{{ __('Edit') }}</a>
            <a href="javascript:history.back()" class="btn btn-light">{{ __('Back') }}</a>
        </div>
    </div></div>
</div>

@endsection
