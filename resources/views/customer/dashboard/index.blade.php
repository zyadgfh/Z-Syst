@extends('landing::layouts.web.master')

@section('title', __('My Dashboard'))

@section('main_content')
<main class="min-h-screen py-8" style="background: #f9fafb;">
    <div class="max-w-6xl mx-auto px-4 sm:px-6">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-2xl font-bold" style="color: #111827;">{{ __('My Account') }}</h1>
            <p class="text-sm mt-1" style="color: #6b7280;">{{ __('Welcome back, :name', ['name' => auth()->user()->name]) }}</p>
        </div>

        @if (session('success'))
            <div class="mb-6 p-4 rounded-xl text-sm" style="background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0;">
                {{ session('success') }}
            </div>
        @endif

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-xl p-5 shadow-sm" style="border: 1px solid #f3f4f6;">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background: #eff6ff;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold" style="color: #111827;">{{ $stats['total_orders'] }}</div>
                        <div class="text-xs" style="color: #6b7280;">{{ __('Total Orders') }}</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl p-5 shadow-sm" style="border: 1px solid #f3f4f6;">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background: #fef3c7;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold" style="color: #111827;">{{ $stats['pending_orders'] }}</div>
                        <div class="text-xs" style="color: #6b7280;">{{ __('Pending') }}</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl p-5 shadow-sm" style="border: 1px solid #f3f4f6;">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background: #f0fdf4;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><path d="M22 4L12 14.01l-3-3"/></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold" style="color: #111827;">{{ $stats['delivered_orders'] }}</div>
                        <div class="text-xs" style="color: #6b7280;">{{ __('Delivered') }}</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl p-5 shadow-sm" style="border: 1px solid #f3f4f6;">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background: #f5f3ff;">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#8b5cf6" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                    </div>
                    <div>
                        <div class="text-2xl font-bold" style="color: #111827;">${{ number_format($stats['total_spent'], 2) }}</div>
                        <div class="text-xs" style="color: #6b7280;">{{ __('Total Spent') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Links -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
            <a href="{{ route('customer.orders') }}" class="bg-white rounded-xl p-5 shadow-sm hover:shadow-md transition-all flex items-center gap-3" style="border: 1px solid #f3f4f6; text-decoration: none; color: inherit;">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background: #eff6ff;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg>
                </div>
                <div>
                    <div class="font-semibold text-sm" style="color: #111827;">{{ __('My Orders') }}</div>
                    <div class="text-xs" style="color: #6b7280;">{{ __('View order history') }}</div>
                </div>
            </a>
            <a href="{{ route('catalog.index') }}" class="bg-white rounded-xl p-5 shadow-sm hover:shadow-md transition-all flex items-center gap-3" style="border: 1px solid #f3f4f6; text-decoration: none; color: inherit;">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background: #f0fdf4;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><path d="M3 6h18M16 10a4 4 0 01-8 0"/></svg>
                </div>
                <div>
                    <div class="font-semibold text-sm" style="color: #111827;">{{ __('Browse Catalog') }}</div>
                    <div class="text-xs" style="color: #6b7280;">{{ __('Explore pharmacy items') }}</div>
                </div>
            </a>
            <a href="{{ route('cart.index') }}" class="bg-white rounded-xl p-5 shadow-sm hover:shadow-md transition-all flex items-center gap-3" style="border: 1px solid #f3f4f6; text-decoration: none; color: inherit;">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center" style="background: #fef3c7;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>
                </div>
                <div>
                    <div class="font-semibold text-sm" style="color: #111827;">{{ __('My Cart') }}</div>
                    <div class="text-xs" style="color: #6b7280;">{{ __('View shopping cart') }}</div>
                </div>
            </a>
        </div>

        <!-- Recent Orders -->
        <div class="bg-white rounded-xl shadow-sm" style="border: 1px solid #f3f4f6;">
            <div class="p-5 border-b" style="border-color: #f3f4f6;">
                <h2 class="font-semibold" style="color: #111827;">{{ __('Recent Orders') }}</h2>
            </div>
            <div class="p-5">
                @if ($recentOrders->isEmpty())
                    <div class="text-center py-8" style="color: #9ca3af;">
                        <svg class="mx-auto mb-3" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        <p>{{ __('No orders yet') }}</p>
                        <a href="{{ route('catalog.index') }}" class="inline-block mt-3 px-4 py-2 rounded-lg text-sm font-medium text-white" style="background: #15803d; text-decoration: none;">
                            {{ __('Start Shopping') }}
                        </a>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach ($recentOrders as $order)
                            <a href="{{ route('customer.orders.show', $order->order_number) }}"
                               class="flex items-center justify-between p-4 rounded-xl hover:bg-gray-50 transition-all"
                               style="border: 1px solid #f3f4f6; text-decoration: none; color: inherit;">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg flex items-center justify-center"
                                         style="background: {{ match($order->status) {
                                             'pending' => '#fef3c7',
                                             'confirmed' => '#dbeafe',
                                             'processing' => '#ede9fe',
                                             'shipped' => '#e0e7ff',
                                             'delivered' => '#f0fdf4',
                                             'cancelled' => '#fef2f2',
                                             default => '#f3f4f6',
                                         } }};">
                                        <span class="text-lg">{{ match($order->status) {
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
                                        <div class="font-medium text-sm" style="color: #111827;">{{ $order->order_number }}</div>
                                        <div class="text-xs" style="color: #6b7280;">{{ $order->items->count() }} {{ __('items') }} · {{ $order->created_at->format('M d, Y') }}</div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="font-semibold text-sm" style="color: #111827;">${{ number_format($order->total_amount, 2) }}</div>
                                    <span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium"
                                          style="background: {{ match($order->status) {
                                              'pending' => '#fef3c7; color: #92400e',
                                              'confirmed' => '#dbeafe; color: #1e40af',
                                              'delivered' => '#f0fdf4; color: #166534',
                                              'cancelled' => '#fef2f2; color: #991b1b',
                                              default => '#f3f4f6; color: #374151',
                                          } }}">
                                        {{ ucfirst($order->status) }}
                                    </span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</main>
@endsection
