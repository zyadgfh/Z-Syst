@extends('layouts.master')

@section('title')
    {{ __('Purchase Return') }} #{{ $data->invoice_no }}
@endsection

@section('main_content')
<div class="container-fluid">
    <div class="erp-table-section">
        <div class="card">
            <div class="card-bodys">
                <div class="table-header p-16 d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <h4 class="mb-0">{{ __('Purchase Return') }} #{{ $data->invoice_no }}</h4>
                    <a href="{{ route('admin.purchases.returns.index') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-arrow-left me-1"></i>{{ __('Back') }}
                    </a>
                </div>

                <div class="p-16">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header"><strong>{{ __('Return Information') }}</strong></div>
                                <div class="card-body">
                                    <table class="table table-borderless mb-0">
                                        <tr><td class="text-muted" style="width:150px">{{ __('Return Number') }}</td><td><strong>{{ $data->invoice_no }}</strong></td></tr>
                                        <tr><td class="text-muted">{{ __('Purchase Invoice') }}</td><td><a href="{{ route('admin.purchases.show', $data->purchase_id) }}">{{ $data->purchase->invoiceNumber ?? '—' }}</a></td></tr>
                                        <tr><td class="text-muted">{{ __('Supplier') }}</td><td>{{ $data->purchase->party->name ?? '—' }}</td></tr>
                                        <tr><td class="text-muted">{{ __('Date') }}</td><td>{{ $data->return_date ? \Carbon\Carbon::parse($data->return_date)->format('d/m/Y H:i') : '—' }}</td></tr>
                                        <tr><td class="text-muted">{{ __('Processed By') }}</td><td>{{ $data->user->name ?? '—' }}</td></tr>
                                        <tr><td class="text-muted">{{ __('Status') }}</td><td><span class="badge bg-{{ ($data->status ?? 'completed') === 'completed' ? 'success' : 'warning' }}">{{ ucfirst($data->status ?? 'completed') }}</span></td></tr>
                                        @if($data->reason)
                                        <tr><td class="text-muted">{{ __('Reason') }}</td><td>{{ $data->reason }}</td></tr>
                                        @endif
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header"><strong>{{ __('Financial Summary') }}</strong></div>
                                <div class="card-body">
                                    <table class="table table-borderless mb-0">
                                        <tr><td class="text-muted" style="width:150px">{{ __('Total Credit') }}</td><td class="text-end fw-bold text-danger fs-5">{{ number_format($data->credit_amount ?? $data->total_amount ?? 0, 2) }}</td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-header"><strong>{{ __('Returned Items') }}</strong></div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>#</th>
                                            <th>{{ __('Product') }}</th>
                                            <th>{{ __('Batch') }}</th>
                                            <th class="text-end">{{ __('Return Qty') }}</th>
                                            <th class="text-end">{{ __('Unit Price') }}</th>
                                            <th class="text-end">{{ __('Credit') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($data->details as $index => $detail)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $detail->product->productName ?? '—' }}</td>
                                            <td>{{ $detail->batch_no ?? '—' }}</td>
                                            <td class="text-end">{{ $detail->return_qty }}</td>
                                            <td class="text-end">{{ number_format($detail->unit_price ?? 0, 2) }}</td>
                                            <td class="text-end fw-bold">{{ number_format($detail->credit_amount ?? $detail->return_amount ?? 0, 2) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    @if($data->notes)
                    <div class="card mt-4">
                        <div class="card-header"><strong>{{ __('Notes') }}</strong></div>
                        <div class="card-body">{{ $data->notes }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
