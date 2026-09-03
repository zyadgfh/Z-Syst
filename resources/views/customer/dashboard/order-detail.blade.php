@extends('landing::layouts.web.master')

@section('title', __('Order') . ' ' . $order->order_number)

@section('main_content')
<main class="min-h-screen py-8" style="background: #f9fafb;">
    <div class="max-w-4xl mx-auto px-4 sm:px-6">
        <!-- Back Link -->
        <a href="{{ route('customer.orders') }}" class="inline-flex items-center gap-1 text-sm font-medium mb-6" style="color: #15803d; text-decoration: none;">
            ← {{ __('Back to Orders') }}
        </a>

        @if (session('success'))
            <div class="mb-6 p-4 rounded-xl text-sm" style="background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;">
                {{ session('success') }}
            </div>
        @endif

        <!-- Order Header -->
        <div class="bg-white rounded-xl p-6 shadow-sm mb-6" style="border: 1px solid #f3f4f6;">
            <div class="flex flex-col sm:flex-row justify-between gap-4">
                <div>
                    <h1 class="text-xl font-bold" style="color: #111827;">{{ __('Order') }} {{ $order->order_number }}</h1>
                    <p class="text-sm mt-1" style="color: #6b7280;">{{ __('Placed on') }} {{ $order->created_at->format('F d, Y \a\t g:i A') }}</p>
                </div>
                <div class="flex gap-2">
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium"
                          style="background: {{ match($order->status) {
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
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium"
                          style="background: {{ $order->payment_status === 'paid' ? '#f0fdf4; color: #166534' : '#fef2f2; color: #991b1b' }}">
                        {{ ucfirst(str_replace('_', ' ', $order->payment_status)) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="bg-white rounded-xl shadow-sm mb-6" style="border: 1px solid #f3f4f6;">
            <div class="p-5 border-b" style="border-color: #f3f4f6;">
                <h2 class="font-semibold" style="color: #111827;">{{ __('Order Items') }}</h2>
            </div>
            <div class="p-5">
                <div class="space-y-3">
                    @foreach ($order->items as $item)
                        <div class="flex items-center gap-4 p-3 rounded-xl" style="background: #f9fafb;">
                            <div class="w-12 h-12 rounded-lg flex items-center justify-center flex-shrink-0" style="background: #e5e7eb;">
                                @if ($item->product && $item->product->images && is_array($item->product->images) && count($item->product->images) > 0)
                                    <img src="{{ asset('storage/' . $item->product->images[0]) }}" alt="{{ $item->product_name }}" class="w-12 h-12 rounded-lg object-cover">
                                @else
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-sm truncate" style="color: #111827;">{{ $item->product_name }}</div>
                                @if ($item->product_sku)
                                    <div class="text-xs" style="color: #9ca3af;">SKU: {{ $item->product_sku }}</div>
                                @endif
                            </div>
                            <div class="text-end flex-shrink-0">
                                <div class="text-sm font-medium" style="color: #111827;">${{ number_format($item->total_price, 2) }}</div>
                                <div class="text-xs" style="color: #6b7280;">{{ $item->quantity }} × ${{ number_format($item->unit_price, 2) }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Order Summary + Shipping -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Summary -->
            <div class="bg-white rounded-xl p-5 shadow-sm" style="border: 1px solid #f3f4f6;">
                <h2 class="font-semibold mb-4" style="color: #111827;">{{ __('Order Summary') }}</h2>
                <div class="space-y-2">
                    <div class="flex justify-between text-sm">
                        <span style="color: #6b7280;">{{ __('Subtotal') }}</span>
                        <span style="color: #111827;">${{ number_format($order->subtotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span style="color: #6b7280;">{{ __('Shipping') }}</span>
                        <span style="color: #111827;">${{ number_format($order->shipping_amount, 2) }}</span>
                    </div>
                    @if ($order->discount_amount > 0)
                        <div class="flex justify-between text-sm">
                            <span style="color: #6b7280;">{{ __('Discount') }}</span>
                            <span style="color: #10b981;">-${{ number_format($order->discount_amount, 2) }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between text-sm">
                        <span style="color: #6b7280;">{{ __('Tax') }}</span>
                        <span style="color: #111827;">${{ number_format($order->tax_amount, 2) }}</span>
                    </div>
                    <div class="border-t pt-2 mt-2 flex justify-between font-bold" style="border-color: #f3f4f6;">
                        <span style="color: #111827;">{{ __('Total') }}</span>
                        <span style="color: #15803d;">${{ number_format($order->total_amount, 2) }}</span>
                    </div>
                </div>
            </div>

            <!-- Shipping Info -->
            <div class="bg-white rounded-xl p-5 shadow-sm" style="border: 1px solid #f3f4f6;">
                <h2 class="font-semibold mb-4" style="color: #111827;">{{ __('Shipping Details') }}</h2>
                <div class="space-y-3">
                    <div>
                        <div class="text-xs font-medium mb-0.5" style="color: #9ca3af;">{{ __('Name') }}</div>
                        <div class="text-sm" style="color: #111827;">{{ $order->customer_name }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-medium mb-0.5" style="color: #9ca3af;">{{ __('Phone') }}</div>
                        <div class="text-sm" style="color: #111827;">{{ $order->customer_phone }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-medium mb-0.5" style="color: #9ca3af;">{{ __('Email') }}</div>
                        <div class="text-sm" style="color: #111827;">{{ $order->customer_email }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-medium mb-0.5" style="color: #9ca3af;">{{ __('Address') }}</div>
                        <div class="text-sm" style="color: #111827;">{{ $order->shipping_address }}</div>
                    </div>
                    @if ($order->city)
                        <div>
                            <div class="text-xs font-medium mb-0.5" style="color: #9ca3af;">{{ __('City') }}</div>
                            <div class="text-sm" style="color: #111827;">{{ $order->city }}</div>
                        </div>
                    @endif
                    <div>
                        <div class="text-xs font-medium mb-0.5" style="color: #9ca3af;">{{ __('Payment Method') }}</div>
                        <div class="text-sm" style="color: #111827;">{{ str_replace('_', ' ', ucfirst($order->payment_method ?? 'N/A')) }}</div>
                    </div>
                    @if ($order->notes)
                        <div>
                            <div class="text-xs font-medium mb-0.5" style="color: #9ca3af;">{{ __('Notes') }}</div>
                            <div class="text-sm" style="color: #111827;">{{ $order->notes }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</main>
@endsection
