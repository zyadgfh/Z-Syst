@extends('layouts.master')

@section('title', __('Order') . ' ' . $order->order_number)

@section('main_content')
<div class="container-fluid m-h-100">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="{{ route('admin.customer-orders.index') }}" class="text-decoration-none mb-2 d-inline-block" style="color: #15803d; font-size: 14px;">
                ← {{ __('Back to Orders') }}
            </a>
            <h2 class="dashboard-title">{{ __('Order') }} {{ $order->order_number }}</h2>
        </div>
        <div class="d-flex gap-2">
            <!-- Status Update -->
            <div class="dropdown">
                <button class="btn btn-primary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-sync me-2"></i>{{ __('Update Status') }}
                </button>
                <ul class="dropdown-menu">
                    @foreach (['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'] as $status)
                        <li>
                            <a class="dropdown-item {{ $order->status === $status ? 'active' : '' }}"
                               href="#" onclick="updateOrderStatus('{{ $status }}'); return false;">
                                {{ ucfirst($status) }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
            <!-- Payment Update -->
            <div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="fas fa-credit-card me-2"></i>{{ __('Payment') }}
                </button>
                <ul class="dropdown-menu">
                    @foreach (['unpaid', 'paid', 'partially_refunded', 'refunded'] as $ps)
                        <li>
                            <a class="dropdown-item {{ $order->payment_status === $ps ? 'active' : '' }}"
                               href="#" onclick="updatePayment('{{ $ps }}'); return false;">
                                {{ ucfirst(str_replace('_', ' ', $ps)) }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Order Info -->
        <div class="col-lg-8">
            <!-- Status Badges + Visual Progress -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="d-flex gap-3 align-items-center mb-4">
                        <span class="badge p-2" style="font-size: 14px; background: {{ match($order->status) {
                            'pending' => '#fef3c7; color: #92400e',
                            'confirmed' => '#dbeafe; color: #1e40af',
                            'processing' => '#ede9fe; color: #5b21b6',
                            'shipped' => '#e0e7ff; color: #3730a3',
                            'delivered' => '#f0fdf4; color: #166534',
                            'cancelled' => '#fef2f2; color: #991b1b',
                            default => '#f3f4f6; color: #374151',
                        } }}">
                            {{ ucfirst($order->status) }}
                        </span>
                        <span class="badge p-2" style="font-size: 14px; background: {{ $order->payment_status === 'paid' ? '#f0fdf4; color: #166534' : '#fef2f2; color: #991b1b' }}">
                            {{ ucfirst(str_replace('_', ' ', $order->payment_status)) }}
                        </span>
                        <span class="ms-auto text-muted" style="font-size: 13px;">{{ $order->created_at->format('M d, Y g:i A') }}</span>
                    </div>

                    {{-- Visual Progress Bar --}}
                    @php
                        $statuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];
                        $statusLabels = ['pending' => 'Placed', 'confirmed' => 'Confirmed', 'processing' => 'Processing', 'shipped' => 'Shipped', 'delivered' => 'Delivered'];
                        $currentIndex = array_search($order->status, $statuses);
                        if ($currentIndex === false) $currentIndex = -1;
                    @endphp
                    <div style="display: flex; align-items: center; gap: 0;">
                        @foreach ($statuses as $index => $st)
                            <div style="flex: 1; display: flex; flex-direction: column; align-items: center;">
                                <div style="width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; background: {{ $index <= $currentIndex ? '#15803d' : '#e5e7eb' }}; color: {{ $index <= $currentIndex ? '#fff' : '#9ca3af' }};">
                                    {{ $index <= $currentIndex ? '✓' : ($index + 1) }}
                                </div>
                                <div style="font-size: 11px; font-weight: 500; margin-top: 4px; color: {{ $index <= $currentIndex ? '#111827' : '#9ca3af' }};">{{ $statusLabels[$st] }}</div>
                            </div>
                            @if ($index < count($statuses) - 1)
                                <div style="flex: 1; height: 3px; background: {{ $index < $currentIndex ? '#15803d' : '#e5e7eb' }};"></div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Status History Timeline -->
            @if ($order->statusHistory->count() > 0)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Status History') }}</h5>
                </div>
                <div class="card-body">
                    <div style="position: relative; padding-left: 24px;">
                        {{-- Vertical line --}}
                        <div style="position: absolute; left: 10px; top: 4px; bottom: 4px; width: 2px; background: #e5e7eb;"></div>

                        @foreach ($order->statusHistory->sortByDesc('changed_at') as $idx => $history)
                            @php
                                $statusColors = [
                                    'pending' => '#f59e0b', 'confirmed' => '#3b82f6', 'processing' => '#8b5cf6',
                                    'shipped' => '#6366f1', 'delivered' => '#22c55e', 'cancelled' => '#ef4444',
                                ];
                                $color = $statusColors[$history->status] ?? '#6b7280';
                            @endphp
                            <div style="position: relative; padding-bottom: 20px; {{ $loop->last ? 'padding-bottom: 0;' : '' }}">
                                {{-- Dot --}}
                                <div style="position: absolute; left: -20px; top: 2px; width: 14px; height: 14px; border-radius: 50%; background: {{ $color }}; border: 3px solid #fff; box-shadow: 0 0 0 2px {{ $color }}; z-index: 1;"></div>

                                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                    <div>
                                        <div style="font-weight: 600; font-size: 14px; color: #111827;">
                                            <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: {{ $color }}; margin-right: 6px;"></span>
                                            {{ ucfirst($history->status) }}
                                        </div>
                                        @if ($history->note)
                                            <div style="background: #f3f4f6; border-radius: 8px; padding: 8px 12px; margin-top: 6px; font-size: 13px; color: #4b5563; max-width: 400px;">
                                                💬 {{ $history->note }}
                                            </div>
                                        @endif
                                    </div>
                                    <div style="text-align: right; flex-shrink: 0;">
                                        <div style="font-size: 12px; color: #6b7280;">{{ $history->changed_at->format('M d, Y g:i A') }}</div>
                                        @if ($history->changedByUser)
                                            <div style="font-size: 11px; color: #9ca3af; margin-top: 2px;">by {{ $history->changedByUser->name }}</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            <!-- Order Items -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Order Items') }}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>{{ __('Product') }}</th>
                                    <th>{{ __('SKU') }}</th>
                                    <th class="text-center">{{ __('Qty') }}</th>
                                    <th class="text-end">{{ __('Price') }}</th>
                                    <th class="text-end">{{ __('Total') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($order->items as $item)
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="rounded" style="width: 40px; height: 40px; background: #f3f4f6; display: flex; align-items: center; justify-content: center;">
                                                    @if ($item->product && $item->product->images && is_array($item->product->images) && count($item->product->images) > 0)
                                                        <img src="{{ asset('storage/' . $item->product->images[0]) }}" style="width: 40px; height: 40px; border-radius: 6px; object-fit: cover;">
                                                    @else
                                                        <i class="fas fa-box" style="color: #9ca3af;"></i>
                                                    @endif
                                                </div>
                                                <div>
                                                    <div class="fw-medium">{{ $item->product_name }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><code>{{ $item->product_sku ?? '—' }}</code></td>
                                        <td class="text-center">{{ $item->quantity }}</td>
                                        <td class="text-end">${{ number_format($item->unit_price, 2) }}</td>
                                        <td class="text-end fw-bold">${{ number_format($item->total_price, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Order Summary -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Order Summary') }}</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">{{ __('Subtotal') }}</span>
                        <span>${{ number_format($order->subtotal, 2) }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">{{ __('Shipping') }}</span>
                        <span>${{ number_format($order->shipping_amount, 2) }}</span>
                    </div>
                    @if ($order->discount_amount > 0)
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">{{ __('Discount') }}</span>
                            <span style="color: #10b981;">-${{ number_format($order->discount_amount, 2) }}</span>
                        </div>
                    @endif
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">{{ __('Tax') }}</span>
                        <span>${{ number_format($order->tax_amount, 2) }}</span>
                    </div>
                    <div class="border-top pt-2 mt-2 d-flex justify-content-between">
                        <strong>{{ __('Total') }}</strong>
                        <strong style="color: #15803d;">${{ number_format($order->total_amount, 2) }}</strong>
                    </div>
                </div>
            </div>

            <!-- Customer Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Customer') }}</h5>
                </div>
                <div class="card-body">
                    <div class="mb-2"><strong>{{ $order->customer_name }}</strong></div>
                    <div class="text-muted mb-1" style="font-size: 13px;">
                        <i class="fas fa-envelope me-1"></i>{{ $order->customer_email }}
                    </div>
                    <div class="text-muted mb-1" style="font-size: 13px;">
                        <i class="fas fa-phone me-1"></i>{{ $order->customer_phone }}
                    </div>
                </div>
            </div>

            <!-- Shipping -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('Shipping') }}</h5>
                </div>
                <div class="card-body">
                    <div class="text-muted" style="font-size: 13px;">
                        {{ $order->shipping_address }}
                        @if ($order->city)
                            <br>{{ $order->city }}
                        @endif
                    </div>
                    <div class="mt-2 text-muted" style="font-size: 13px;">
                        <i class="fas fa-credit-card me-1"></i>
                        {{ str_replace('_', ' ', ucfirst($order->payment_method ?? 'N/A')) }}
                    </div>
                </div>
            </div>

            @if ($order->notes)
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">{{ __('Notes') }}</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-0" style="font-size: 13px;">{{ $order->notes }}</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
function updateOrderStatus(status) {
    var note = prompt('Add a note for this status change (optional):') || '';
    fetch('{{ route("admin.customer-orders.update-status", $order) }}', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ status: status, note: note })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to update status');
        }
    });
}

function updatePayment(status) {
    fetch('{{ route("admin.customer-orders.update-payment", $order) }}', {
        method: 'PUT',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({ payment_status: status })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to update payment');
        }
    });
}
</script>
@endsection
