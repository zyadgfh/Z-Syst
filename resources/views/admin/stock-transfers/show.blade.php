@extends('layouts.master')

@section('title')
    {{ __('Stock Transfer') }}
@endsection

@section('main_content')

<div class="container-fluid">
    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">{{ __('Transfer') }} #{{ $transfer->id }}</h4>
            <span class="badge bg-secondary">{{ $transfer->status }}</span>
        </div>
        <div class="row g-3">
            <div class="col-md-4"><strong>{{ __('Product') }}:</strong> {{ $transfer->product?->product_name ?? $transfer->product_id }}</div>
            <div class="col-md-4"><strong>{{ __('Quantity') }}:</strong> {{ $transfer->quantity }}</div>
            <div class="col-md-4"><strong>{{ __('Notes') }}:</strong> {{ $transfer->notes }}</div>
        </div>
        <div class="d-flex gap-2 mt-4">
            @if($transfer->status === 'pending')
                <form action="{{ route('admin.stock-transfers.complete', $transfer) }}" method="POST">@csrf<button class="btn btn-success">{{ __('Complete') }}</button></form>
                <form action="{{ route('admin.stock-transfers.cancel', $transfer) }}" method="POST">@csrf<button class="btn btn-outline-danger">{{ __('Cancel') }}</button></form>
            @endif
            <a href="{{ route('admin.stock-transfers.index') }}" class="btn btn-light">{{ __('Back') }}</a>
        </div>
    </div></div>
</div>

@endsection
