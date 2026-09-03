@extends('layouts.admin')

@section('title', __('warehouse.stock_transfers'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('warehouse.stock_transfers') }}</h1>
        <a href="{{ route('admin.stock-transfers.create') }}" class="btn btn-primary-blue">
            <i class="fas fa-plus me-1"></i> {{ __('common.New Stock Transfer') }}
        </a>
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.stock-transfers.index') }}" class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control" placeholder="{{ __('common.Search') }}..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="">{{ __('common.All Statuses') }}</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>{{ __('common.Pending') }}</option>
                        <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>{{ __('common.Completed') }}</option>
                        <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>{{ __('common.Cancelled') }}</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary-blue"><i class="fas fa-filter me-1"></i> {{ __('common.Filter') }}</button>
                    <a href="{{ route('admin.stock-transfers.index') }}" class="btn btn-secondary">{{ __('common.Reset') }}</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Transfers Table --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{{ __('warehouse.From Warehouse') }}</th>
                            <th>{{ __('warehouse.To Warehouse') }}</th>
                            <th>{{ __('products.Product') }}</th>
                            <th>{{ __('common.Quantity') }}</th>
                            <th>{{ __('common.Status') }}</th>
                            <th>{{ __('common.Date') }}</th>
                            <th>{{ __('common.Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transfers as $transfer)
                            <tr>
                                <td>{{ $transfer->id }}</td>
                                <td>{{ $transfer->fromWarehouse->name ?? '—' }}</td>
                                <td>{{ $transfer->toWarehouse->name ?? '—' }}</td>
                                <td>{{ $transfer->product->name ?? '—' }}</td>
                                <td><strong>{{ $transfer->quantity }}</strong></td>
                                <td>
                                    @if($transfer->status === 'pending')
                                        <span class="badge bg-warning text-dark">{{ __('common.Pending') }}</span>
                                    @elseif($transfer->status === 'completed')
                                        <span class="badge bg-success">{{ __('common.Completed') }}</span>
                                    @else
                                        <span class="badge bg-secondary">{{ __('common.Cancelled') }}</span>
                                    @endif
                                </td>
                                <td>{{ $transfer->created_at?->format('d M Y H:i') ?? '—' }}</td>
                                <td>
                                    <a href="{{ route('admin.stock-transfers.show', $transfer) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($transfer->status === 'pending')
                                        <button class="btn btn-sm btn-success" onclick="completeTransfer({{ $transfer->id }})">
                                            <i class="fas fa-check"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="cancelTransfer({{ $transfer->id }})">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-exchange-alt fa-2x mb-2 d-block"></i>
                                    {{ __('common.No stock transfers found') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($transfers->hasPages())
            <div class="card-footer">
                {{ $transfers->links() }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function completeTransfer(id) {
    if (!confirm('Are you sure you want to complete this transfer?')) return;
    fetch(`/admin/stock-transfers/${id}/complete`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
    }).then(r => r.json()).then(data => { window.location.reload(); });
}
function cancelTransfer(id) {
    if (!confirm('Are you sure you want to cancel this transfer?')) return;
    fetch(`/admin/stock-transfers/${id}/cancel`, {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
    }).then(r => r.json()).then(data => { window.location.reload(); });
}
</script>
@endpush
@endsection
