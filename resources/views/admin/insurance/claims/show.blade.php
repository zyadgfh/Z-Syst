@extends('layouts.master')

@section('title')
    {{ __('Insurance Claim') }}
@endsection

@section('main_content')

<div class="container-fluid">
    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">{{ __('Claim') }}: {{ $claim->claim_number }}</h4>
            <span class="badge bg-secondary">{{ $claim->status }}</span>
        </div>
        <div class="row g-3">
            <div class="col-md-4"><strong>{{ __('Service date') }}:</strong> {{ $claim->service_date }}</div>
            <div class="col-md-4"><strong>{{ __('Total') }}:</strong> {{ $claim->total_amount }}</div>
            <div class="col-md-4"><strong>{{ __('Covered') }}:</strong> {{ $claim->covered_amount }}</div>
            <div class="col-md-4"><strong>{{ __('Approved') }}:</strong> {{ $claim->approved_amount }}</div>
            <div class="col-md-4"><strong>{{ __('Paid') }}:</strong> {{ $claim->paid_amount }}</div>
            <div class="col-md-4"><strong>{{ __('Patient responsibility') }}:</strong> {{ $claim->patient_responsibility }}</div>
            @if($claim->rejection_reason)<div class="col-12"><strong>{{ __('Rejection reason') }}:</strong> {{ $claim->rejection_reason }}</div>@endif
        </div>
        <div class="d-flex gap-2 mt-4 flex-wrap">
            @if($claim->status === 'draft')
                <form action="{{ route('admin.insurance.claims.submit', $claim) }}" method="POST">@csrf<button class="btn btn-primary">{{ __('Submit') }}</button></form>
            @endif
            @if($claim->status === 'submitted')
                <form action="{{ route('admin.insurance.claims.process', $claim) }}" method="POST">@csrf<button class="btn btn-outline-primary">{{ __('Process') }}</button></form>
            @endif
            <a href="{{ route('admin.insurance.claims.edit', $claim) }}" class="btn btn-outline-secondary">{{ __('Edit') }}</a>
            <a href="javascript:history.back()" class="btn btn-light">{{ __('Back') }}</a>
        </div>
    </div></div>
</div>

@endsection
