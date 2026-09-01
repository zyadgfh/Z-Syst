@extends('landing::layouts.web.master')

@section('title', __('My Orders'))

@section('main_content')
<main class="min-h-screen py-8" style="background: #f9fafb;">
    <div class="max-w-4xl mx-auto px-4 sm:px-6">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold" style="color: #111827;">{{ __('My Orders') }}</h1>
        </div>

        <!-- Status Filters -->
        <div class="flex gap-2 mb-6 overflow-x-auto pb-2">
            <a href="{{ route('customer.orders') }}"
               class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-all"
               style="{{ !$status ? 'background: #15803d; color: white;' : 'background: white; color: #374151; border: 1px solid #e5e7eb;' }} text-decoration: none;">
                {{ __('All') }}
            </a>
            @foreach (['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'] as $s)
                <a href="{{ route('customer.orders', ['status' => $s]) }}"
                   class="px-4 py-2 rounded-full text-sm font-medium whitespace-nowrap transition-all"
                   style="{{ $status === $s ? 'background: #15803d; color: white;' : 'background: white; color: #374151; border: 1px solid #e5e7eb;' }} text-decoration: none;">
                    {{ ucfirst($s) }}
                </a>
            @endforeach
        </div>

        <!-- Orders List -->
        @if ($orders->isEmpty())
            <div class="bg-white rounded-xl p-12 text-center shadow-sm" style="border: 1px solid #f3f4f6;">
                <svg class="mx-auto mb-4" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#d1d5db" stroke-width="1.5">
                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="text-lg font-medium mb-1" style="color: #374151;">{{ __('No orders found') }}</p>
                <p class="text-sm mb-4" style="color: #9ca3af;">{{ __('Start shopping to see your orders here.') }}</p>
                <a href="{{ route('catalog.index') }}" class="inline-block px-5 py-2.5 rounded-xl text-sm font-medium text-white" style="background: #15803d; text-decoration: none;">
                    {{ __('Browse Catalog') }}
                </a>
            </div>
        @else
            <div class="space-y-4">
                @foreach ($orders as $order)
                    <a href="{{ route('customer.orders.show', $order->order_number) }}"
                       class="block bg-white rounded-xl p-5 shadow-sm hover:shadow-md transition-all"
                       style="border: 1px solid #f3f4f6; text-decoration: none; color: inherit;">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
                                     style="background: {{ match($order->status) {
                                         'pending' => '#fef3c7',
                                         'confirmed' => '#dbeafe',
                                         'processing' => '#ede9fe',
                                         'shipped' => '#e0e7ff',
                                         'delivered' => '#f0fdf4',
                                         'cancelled' => '#fef2f2',
                                         default => '#f3f4f6',
                                     } }};">
                                    <span class="text-xl">{{ match($order->status) {
                                        'pending' => '⏳',
                                        'confirmed' => '✓',
                                        'processing' => '⚙️',
                                        'shipped' => '🚚',
                                        'delivered' => '✅',
                                        'cancelled' => '✕',
                                        default => '📋',
                                    } }}</span>
                                </div>
                                <div>
                                    <div class="font-semibold" style="color: #111827;">{{ $order->order_number }}</div>
                                    <div class="text-sm" style="color: #6b7280;">{{ $order->created_at->format('M d, Y · g:i A') }}</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <span class="inline-block px-3 py-1 rounded-full text-xs font-medium"
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
                                <div class="text-right">
                                    <div class="font-bold" style="color: #111827;">${{ number_format($order->total_amount, 2) }}</div>
                                    <div class="text-xs" style="color: #6b7280;">{{ $order->items->count() }} {{ __('items') }}</div>
                                </div>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-6">
                {{ $orders->withQueryString()->links() }}
            </div>
        @endif
    </div>
</main>
@endsection
