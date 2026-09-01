@extends('layouts.master')

@section('title')
    {{ __('Purchase Invoices') }}
@endsection

@section('main_content')
<div class="container-fluid">
    <div class="erp-table-section">
        <div class="card">
            <div class="card-bodys">
                <div class="table-header p-16 d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <h4 class="mb-0">{{ __('Purchase Invoices') }}</h4>
                    <div class="d-flex gap-2">
                        @can('purchases-create')
                        <a href="{{ route('admin.purchases.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>{{ __('New Purchase') }}
                        </a>
                        @endcan
                    </div>
                </div>

                {{-- Filters --}}
                <div class="p-16 border-top">
                    <form method="GET" action="{{ route('admin.purchases.index') }}" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">{{ __('Search') }}</label>
                            <input type="text" name="search" class="form-control" placeholder="{{ __('Invoice #, Supplier, Note...') }}" value="{{ $filters['search'] ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">{{ __('Supplier') }}</label>
                            <select name="party_id" class="form-select">
                                <option value="">{{ __('All Suppliers') }}</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}" @selected(($filters['party_id'] ?? '') == $supplier->id)>{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">{{ __('Branch') }}</label>
                            <select name="branch_id" class="form-select">
                                <option value="">{{ __('All Branches') }}</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected(($filters['branch_id'] ?? '') == $branch->id)>{{ $branch->branch_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">{{ __('Status') }}</label>
                            <select name="status" class="form-select">
                                <option value="">{{ __('All') }}</option>
                                <option value="received" @selected(($filters['status'] ?? '') == 'received')>{{ __('Received') }}</option>
                                <option value="canceled" @selected(($filters['status'] ?? '') == 'canceled')>{{ __('Cancelled') }}</option>
                                <option value="returned_partially" @selected(($filters['status'] ?? '') == 'returned_partially')>{{ __('Returned (Partial)') }}</option>
                                <option value="returned_fully" @selected(($filters['status'] ?? '') == 'returned_fully')>{{ __('Returned (Full)') }}</option>
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">{{ __('From') }}</label>
                            <input type="date" name="from_date" class="form-control" value="{{ $filters['from_date'] ?? '' }}">
                        </div>
                        <div class="col-md-1">
                            <label class="form-label">{{ __('To') }}</label>
                            <input type="date" name="to_date" class="form-control" value="{{ $filters['to_date'] ?? '' }}">
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter"></i>
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Purchase List --}}
                <div class="table-responsive p-16">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>{{ __('Invoice #') }}</th>
                                <th>{{ __('Supplier') }}</th>
                                <th>{{ __('Branch') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('User') }}</th>
                                <th class="text-end">{{ __('Total') }}</th>
                                <th class="text-end">{{ __('Paid') }}</th>
                                <th class="text-end">{{ __('Remaining') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($purchases as $purchase)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.purchases.show', $purchase->id) }}" class="fw-bold text-decoration-none">
                                        {{ $purchase->invoiceNumber }}
                                    </a>
                                </td>
                                <td>{{ $purchase->party->name ?? '—' }}</td>
                                <td>{{ $purchase->branch->branch_name ?? '—' }}</td>
                                <td>{{ $purchase->purchaseDate ? \Carbon\Carbon::parse($purchase->purchaseDate)->format('d/m/Y') : '—' }}</td>
                                <td>{{ $purchase->user->name ?? '—' }}</td>
                                <td class="text-end">{{ number_format($purchase->totalAmount, 2) }}</td>
                                <td class="text-end">{{ number_format($purchase->paidAmount, 2) }}</td>
                                <td class="text-end fw-bold {{ $purchase->dueAmount > 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($purchase->dueAmount, 2) }}
                                </td>
                                <td>
                                    @php
                                        $statusColors = [
                                            'received' => 'success',
                                            'canceled' => 'danger',
                                            'returned_partially' => 'warning',
                                            'returned_fully' => 'info',
                                            'pending' => 'secondary',
                                        ];
                                        $color = $statusColors[$purchase->status] ?? 'secondary';
                                    @endphp
                                    <span class="badge bg-{{ $color }}">{{ ucfirst(str_replace('_', ' ', $purchase->status)) }}</span>
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <ul class="dropdown-menu">
                                            <li><a class="dropdown-item" href="{{ route('admin.purchases.show', $purchase->id) }}"><i class="fas fa-eye me-2"></i>{{ __('View') }}</a></li>
                                            @if($purchase->status === 'received' && auth()->user()->can('purchases-edit'))
                                            <li><a class="dropdown-item" href="{{ route('admin.purchases.edit', $purchase->id) }}"><i class="fas fa-edit me-2"></i>{{ __('Edit') }}</a></li>
                                            @endif
                                            @if(in_array($purchase->status, ['received', 'returned_partially']) && auth()->user()->can('purchases-create'))
                                            <li><a class="dropdown-item" href="{{ route('admin.purchases.returns.create') }}?purchase_id={{ $purchase->id }}"><i class="fas fa-undo me-2"></i>{{ __('Return') }}</a></li>
                                            @endif
                                            @if($purchase->status === 'received' && auth()->user()->can('purchases-delete'))
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" href="#" onclick="cancelPurchase({{ $purchase->id }}, '{{ $purchase->invoiceNumber }}')"><i class="fas fa-ban me-2"></i>{{ __('Cancel') }}</a></li>
                                            @endif
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">
                                    <i class="fas fa-receipt fa-2x mb-2 opacity-25"></i>
                                    <p>{{ __('No purchase invoices found.') }}</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($purchases->hasPages())
                <div class="p-16 border-top">
                    {{ $purchases->withQueryString()->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    function cancelPurchase(id, invoiceNumber) {
        if (!confirm(`{{ __("Are you sure you want to cancel purchase") }} ${invoiceNumber}?`)) return;

        fetch(`{{ url('admin/purchases') }}/${id}/cancel`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json',
            },
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                toastr.success(data.message);
                setTimeout(() => location.reload(), 1000);
            } else {
                toastr.error(data.message);
            }
        })
        .catch(() => toastr.error('{{ __("Error cancelling purchase.") }}'));
    }
</script>
@endpush
