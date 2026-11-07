@extends('layouts.master')

@section('title')
    {{ __('Stock Transfers') }}
@endsection

@section('main_content')

<div class="erp-table-section">
    <div class="container-fluid">
        <div class="card"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">{{ __('Stock Transfers') }}</h4>
                <a href="{{ route('admin.stock-transfers.create') }}" class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i>{{ __('New transfer') }}</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light"><tr><th>{{ __('ID') }}</th><th>{{ __('Product') }}</th><th>{{ __('Qty') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                    <tbody>
                        @foreach($transfers as $transfer)
                            <tr>
                                <td>#{{ $transfer->id }}</td>
                                <td>{{ $transfer->product?->productName ?? $transfer->product_id }}</td>
                                <td>{{ $transfer->quantity }}</td>
                                <td><span class="badge bg-secondary">{{ $transfer->status }}</span></td>
                                <td><a href="{{ route('admin.stock-transfers.show', $transfer) }}" class="btn btn-sm btn-light">{{ __('View') }}</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div></div>
    </div>
</div>

@endsection
