@extends('layouts.master')

@section('title')
    {{ __('Supplier Payments') }}
@endsection

@section('main_content')

<div class="erp-table-section">
    <div class="container-fluid">
        <div class="card"><div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">{{ __('Supplier Payments') }}</h4>
                <a href="{{ route('admin.supplier-payments.create') }}" class="btn btn-primary"><i class="fas fa-plus-circle me-1"></i>{{ __('New payment') }}</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light"><tr><th>{{ __('Number') }}</th><th>{{ __('Supplier') }}</th><th>{{ __('Amount') }}</th><th>{{ __('Date') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                    <tbody>
                        @foreach($payments as $payment)
                            <tr>
                                <td>{{ $payment->payment_number }}</td>
                                <td>{{ $payment->supplier?->company_name ?? $payment->supplier_id }}</td>
                                <td>{{ $payment->amount }}</td>
                                <td>{{ $payment->payment_date }}</td>
                                <td><span class="badge bg-secondary">{{ $payment->status }}</span></td>
                                <td class="text-nowrap">
                                    <a href="{{ route('admin.supplier-payments.show', $payment) }}" class="btn btn-sm btn-light">{{ __('View') }}</a>
                                    <a href="{{ route('admin.supplier-payments.edit', $payment) }}" class="btn btn-sm btn-outline-primary">{{ __('Edit') }}</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div></div>
    </div>
</div>

@endsection
