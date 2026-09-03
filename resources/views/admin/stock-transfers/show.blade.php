@extends('layouts.admin')

@section('title', __('warehouse.Stock Transfer Details'))

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">{{ __('warehouse.Stock Transfer') }} #{{ $transfer->id }}</h1>
        <div>
            @if($transfer->status === 'pending')
                <button class="btn btn-success" onclick="completeTransfer({{ $transfer->id }})">
                    <i class="fas fa-check me-1"></i> {{ __('common.Complete') }}
                </button>
                <button class="btn btn-danger" onclick="cancelTransfer({{ $transfer->id }})">
                    <i class="fas fa-times me-1"></i> {{ __('common.Cancel') }}
                </button>
            @endif
            <a href="{{ route('admin.stock-transfers.index') }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i> {{ __('common.Back') }}
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('common.Transfer Details') }}</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th style="width: 200px">{{ __('common.Status') }}</th>
                            <td>
                                @if($transfer->status === 'pending')
                                    <span class="badge bg-warning text-dark">{{ __('common.Pending') }}</span>
                                @elseif($transfer->status === 'completed')
                                    <span class="badge bg-success">{{ __('common.Completed') }}</span>
                                @else
                                    <span class="badge bg-secondary">{{ __('common.Cancelled') }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('warehouse.From Warehouse') }}</th>
                            <td>{{ $transfer->fromWarehouse->name ?? '—' }} ({{ $transfer->fromWarehouse->code ?? '' }})</td>
                        </tr>
                        <tr>
                            <th>{{ __('warehouse.To Warehouse') }}</th>
                            <td>{{ $transfer->toWarehouse->name ?? '—' }} ({{ $transfer->toWarehouse->code ?? '' }})</td>
                        </tr>
                        <tr>
                            <th>{{ __('products.Product') }}</th>
                            <td>{{ $transfer->product->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('common.Quantity') }}</th>
                            <td><strong class="text-primary">{{ $transfer->quantity }}</strong></td>
                        </tr>
                        <tr>
                            <th>{{ __('common.Transferred By') }}</th>
                            <td>{{ $transfer->user->name ?? '—' }}</td>
                        </tr>
                        <tr>
                            <th>{{ __('common.Date') }}</th>
                            <td>{{ $transfer->created_at?->format('d M Y H:i') ?? '—' }}</td>
                        </tr>
                        @if($transfer->notes)
                        <tr>
                            <th>{{ __('common.Notes') }}</th>
                            <td>{{ $transfer->notes }}</td>
                        </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('common.Quick Actions') }}</h5>
                </div>
                <div class="card-body">
                    @if($transfer->status === 'pending')
                        <form method="POST" action="{{ route('admin.stock-transfers.complete', $transfer) }}" class="mb-2">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-success w-100">
                                <i class="fas fa-check me-1"></i> {{ __('common.Mark as Completed') }}
                            </button>
                        </form>
                        <form method="POST" action="{{ route('admin.stock-transfers.cancel', $transfer) }}">
                            @csrf @method('PATCH')
                            <button type="submit" class="btn btn-danger w-100">
                                <i class="fas fa-times me-1"></i> {{ __('common.Cancel Transfer') }}
                            </button>
                        </form>
                    @else
                        <p class="text-muted">{{ __('common.Transfer is no longer pending') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function completeTransfer(id) {
    if (!confirm('Complete this transfer? Stock will be moved.')) return;
    fetch(`/admin/stock-transfers/${id}/complete`, {
        method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
    }).then(r => r.json()).then(() => window.location.reload());
}
function cancelTransfer(id) {
    if (!confirm('Cancel this transfer?')) return;
    fetch(`/admin/stock-transfers/${id}/cancel`, {
        method: 'POST', headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
    }).then(r => r.json()).then(() => window.location.reload());
}
</script>
@endpush
@endsection
