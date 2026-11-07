@extends('layouts.master')

@section('title')
    {{ __('Goods Received Note') }}
@endsection

@section('main_content')

<div class="container-fluid">
    <div class="card"><div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0">{{ __('GRN') }}: {{ $grn->grn_number }}</h4>
            <span class="badge bg-info">{{ $grn->status }}</span>
        </div>
        <div class="row g-3">
            <div class="col-md-4"><strong>{{ __('Supplier') }}:</strong> {{ $grn->supplier?->company_name ?? $grn->supplier?->name ?? $grn->supplier_id }}</div>
            <div class="col-md-4"><strong>{{ __('Purchase order') }}:</strong> #{{ $grn->purchase_order_id }}</div>
            <div class="col-md-4"><strong>{{ __('Received at') }}:</strong> {{ $grn->received_date }}</div>
            <div class="col-md-4"><strong>{{ __('Location') }}:</strong> {{ $grn->location }}</div>
            <div class="col-md-4"><strong>{{ __('Verified at') }}:</strong> {{ $grn->verified_at ?? '—' }}</div>
            <div class="col-12"><strong>{{ __('Notes') }}:</strong><p>{{ $grn->notes }}</p></div>
        </div>
        <div class="d-flex gap-2 mt-4">
            @if($grn->status === 'pending')
                <form action="{{ route('admin.grn.verify', $grn) }}" method="POST">@csrf<button class="btn btn-outline-primary">{{ __('Verify') }}</button></form>
                <form action="{{ route('admin.grn.accept', $grn) }}" method="POST">@csrf<button class="btn btn-success">{{ __('Accept') }}</button></form>
                <form action="{{ route('admin.grn.reject', $grn) }}" method="POST">@csrf<button class="btn btn-outline-danger">{{ __('Reject') }}</button></form>
            @endif
            <a href="javascript:history.back()" class="btn btn-light">{{ __('Back') }}</a>
        </div>
    </div></div>
</div>

@endsection
