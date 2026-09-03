@extends('layouts.admin')

@section('title')
    {{ __('purchases.Purchase Reports') }}
@endsection

@section('main_content')
<div class="container-fluid">
    <div class="erp-table-section">
        <div class="card">
            <div class="card-bodys">
                <div class="table-header p-16 d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <h4 class="mb-0"><i class="fas fa-chart-bar me-2"></i>{{ __('purchases.Purchase Reports') }}</h4>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.purchases.supplier-balance') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-balance-scale me-1"></i>{{ __('purchases.Supplier Balances') }}
                        </a>
                        <a href="{{ route('admin.purchases.stock-movements') }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-exchange-alt me-1"></i>{{ __('purchases.Stock Movements') }}
                        </a>
                    </div>
                </div>

                {{-- Filters --}}
                <div class="p-16 border-top">
                    <form method="GET" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">{{ __('purchases.From Date') }}</label>
                            <input type="date" name="from_date" class="form-control" value="{{ $fromDate }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('purchases.To Date') }}</label>
                            <input type="date" name="to_date" class="form-control" value="{{ $toDate }}">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i>{{ __('common.Filter') }}
                            </button>
                        </div>
                    </form>
                </div>

                <div class="p-16">
                    {{-- Summary Cards --}}
                    <div class="row g-4 mb-4">
                        <div class="col-md-3">
                            <div class="card border-start border-primary border-4">
                                <div class="card-body">
                                    <div class="text-muted">{{ __('purchases.Total Purchases') }}</div>
                                    <h3 class="fw-bold">{{ $totalPurchases }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-start border-success border-4">
                                <div class="card-body">
                                    <div class="text-muted">{{ __('purchases.Total Amount') }}</div>
                                    <h3 class="fw-bold text-success">{{ number_format($totalAmount, 2) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-start border-info border-4">
                                <div class="card-body">
                                    <div class="text-muted">{{ __('purchases.Total Paid') }}</div>
                                    <h3 class="fw-bold text-info">{{ number_format($totalPaid, 2) }}</h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border-start border-warning border-4">
                                <div class="card-body">
                                    <div class="text-muted">{{ __('purchases.Total Returns') }}</div>
                                    <h3 class="fw-bold text-warning">{{ number_format($totalReturnAmount, 2) }}</h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4">
                        {{-- By Supplier --}}
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header"><strong>{{ __('purchases.Purchases by Supplier') }}</strong></div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead><tr><th>{{ __('purchases.Supplier') }}</th><th class="text-center">{{ __('purchases.Invoices') }}</th><th class="text-end">{{ __('common.Total') }}</th></tr></thead>
                                            <tbody>
                                                @forelse($bySupplier as $item)
                                                <tr>
                                                    <td>{{ $item->party->name ?? '—' }}</td>
                                                    <td class="text-center">{{ $item->count }}</td>
                                                    <td class="text-end fw-bold">{{ number_format($item->total, 2) }}</td>
                                                </tr>
                                                @empty
                                                <tr><td colspan="3" class="text-center text-muted py-3">{{ __('common.No data.') }}</td></tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- By Branch --}}
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header"><strong>{{ __('purchases.Purchases by Branch') }}</strong></div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead><tr><th>{{ __('common.Branch') }}</th><th class="text-center">{{ __('purchases.Invoices') }}</th><th class="text-end">{{ __('common.Total') }}</th></tr></thead>
                                            <tbody>
                                                @forelse($byBranch as $item)
                                                <tr>
                                                    <td>{{ $item->branch->branch_name ?? __('purchases.Unassigned') }}</td>
                                                    <td class="text-center">{{ $item->count }}</td>
                                                    <td class="text-end fw-bold">{{ number_format($item->total, 2) }}</td>
                                                </tr>
                                                @empty
                                                <tr><td colspan="3" class="text-center text-muted py-3">{{ __('common.No data.') }}</td></tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
