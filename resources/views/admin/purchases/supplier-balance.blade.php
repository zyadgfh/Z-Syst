@extends('layouts.master')

@section('title')
    {{ __('Supplier Balance Report') }}
@endsection

@section('main_content')
<div class="container-fluid">
    <div class="erp-table-section">
        <div class="card">
            <div class="card-bodys">
                <div class="table-header p-16 d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <h4 class="mb-0"><i class="fas fa-balance-scale me-2"></i>{{ __('Supplier Balance Report') }}</h4>
                    <div class="d-flex align-items-center gap-3">
                        <div class="text-end">
                            <div class="text-muted">{{ __('Total Outstanding Balance') }}</div>
                            <h4 class="fw-bold text-danger mb-0">{{ number_format($totalBalance, 2) }}</h4>
                        </div>
                        <a href="{{ route('admin.purchases.reports') }}" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>{{ __('Back') }}
                        </a>
                    </div>
                </div>

                <div class="table-responsive p-16">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('Supplier') }}</th>
                                <th>{{ __('Phone') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="text-end">{{ __('Balance (Due)') }}</th>
                                <th>{{ __('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($suppliers as $index => $supplier)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td><strong>{{ $supplier->name }}</strong></td>
                                <td>{{ $supplier->phone ?? '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ $supplier->status === 'active' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($supplier->status ?? 'active') }}
                                    </span>
                                </td>
                                <td class="text-end fw-bold {{ $supplier->due > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($supplier->due, 2) }}
                                </td>
                                <td>
                                    <a href="{{ route('admin.suppliers.dashboard', $supplier->id) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye me-1"></i>{{ __('Dashboard') }}
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    {{ __('No suppliers found.') }}
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="table-active fw-bold">
                                <td colspan="4" class="text-end">{{ __('Total') }}</td>
                                <td class="text-end text-danger">{{ number_format($totalBalance, 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
