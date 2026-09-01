@extends('landing::layouts.web.master')

@section('title', __('Checkout'))

@section('main_content')
<main class="min-h-screen py-8" style="background: #f9fafb;">
    <div class="max-w-5xl mx-auto px-4 sm:px-6">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold" style="color: #111827;">{{ __('Checkout') }}</h1>
        </div>

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl text-sm" style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('checkout.place') }}" method="POST" id="checkout-form">
            @csrf
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Shipping Form -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Contact Info -->
                    <div class="bg-white rounded-xl p-6 shadow-sm" style="border: 1px solid #f3f4f6;">
                        <h2 class="font-semibold mb-4" style="color: #111827;">{{ __('Contact Information') }}</h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-1" style="color: #374151;">{{ __('Full Name') }} *</label>
                                <input type="text" name="customer_name" value="{{ old('customer_name', $user->name) }}" required
                                    class="w-full px-4 py-3 rounded-xl border text-sm focus:outline-none focus:ring-2"
                                    style="border-color: #e5e7eb;">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1" style="color: #374151;">{{ __('Phone') }} *</label>
                                <input type="tel" name="customer_phone" value="{{ old('customer_phone', $user->phone) }}" required
                                    class="w-full px-4 py-3 rounded-xl border text-sm focus:outline-none focus:ring-2"
                                    style="border-color: #e5e7eb;">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-sm font-medium mb-1" style="color: #374151;">{{ __('Email') }} *</label>
                                <input type="email" name="customer_email" value="{{ old('customer_email', $user->email) }}" required
                                    class="w-full px-4 py-3 rounded-xl border text-sm focus:outline-none focus:ring-2"
                                    style="border-color: #e5e7eb;">
                            </div>
                        </div>
                    </div>

                    <!-- Shipping Address -->
                    <div class="bg-white rounded-xl p-6 shadow-sm" style="border: 1px solid #f3f4f6;">
                        <h2 class="font-semibold mb-4" style="color: #111827;">{{ __('Shipping Address') }}</h2>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium mb-1" style="color: #374151;">{{ __('Address') }} *</label>
                                <textarea name="shipping_address" required rows="2"
                                    class="w-full px-4 py-3 rounded-xl border text-sm focus:outline-none focus:ring-2"
                                    style="border-color: #e5e7eb;"
                                    placeholder="{{ __('Street address, apartment, building...') }}">{{ old('shipping_address') }}</textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-1" style="color: #374151;">{{ __('City') }}</label>
                                <input type="text" name="city" value="{{ old('city') }}"
                                    class="w-full px-4 py-3 rounded-xl border text-sm focus:outline-none focus:ring-2"
                                    style="border-color: #e5e7eb;">
                            </div>
                        </div>
                    </div>

                    <!-- Payment Method -->
                    <div class="bg-white rounded-xl p-6 shadow-sm" style="border: 1px solid #f3f4f6;">
                        <h2 class="font-semibold mb-4" style="color: #111827;">{{ __('Payment Method') }}</h2>
                        <div class="space-y-3">
                            <label class="flex items-center gap-3 p-4 rounded-xl cursor-pointer transition-all"
                                   style="border: 2px solid {{ old('payment_method') === 'cash_on_delivery' ? '#15803d' : '#e5e7eb' }};">
                                <input type="radio" name="payment_method" value="cash_on_delivery"
                                       {{ old('payment_method', 'cash_on_delivery') === 'cash_on_delivery' ? 'checked' : '' }}
                                       class="accent-green-700" style="accent-color: #15803d;">
                                <div>
                                    <div class="font-medium text-sm" style="color: #111827;">{{ __('Cash on Delivery') }}</div>
                                    <div class="text-xs" style="color: #6b7280;">{{ __('Pay when your order arrives') }}</div>
                                </div>
                            </label>
                            <label class="flex items-center gap-3 p-4 rounded-xl cursor-pointer transition-all"
                                   style="border: 2px solid {{ old('payment_method') === 'bank_transfer' ? '#15803d' : '#e5e7eb' }};">
                                <input type="radio" name="payment_method" value="bank_transfer"
                                       {{ old('payment_method') === 'bank_transfer' ? 'checked' : '' }}
                                       class="accent-green-700" style="accent-color: #15803d;">
                                <div>
                                    <div class="font-medium text-sm" style="color: #111827;">{{ __('Bank Transfer') }}</div>
                                    <div class="text-xs" style="color: #6b7280;">{{ __('Transfer to our bank account') }}</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="bg-white rounded-xl p-6 shadow-sm" style="border: 1px solid #f3f4f6;">
                        <h2 class="font-semibold mb-4" style="color: #111827;">{{ __('Order Notes') }}</h2>
                        <textarea name="notes" rows="3"
                            class="w-full px-4 py-3 rounded-xl border text-sm focus:outline-none focus:ring-2"
                            style="border-color: #e5e7eb;"
                            placeholder="{{ __('Any special instructions? (optional)') }}">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl p-6 shadow-sm sticky" style="border: 1px solid #f3f4f6; top: 2rem;">
                        <h2 class="font-semibold mb-4" style="color: #111827;">{{ __('Order Summary') }}</h2>

                        <!-- Items -->
                        <div class="space-y-3 mb-4 max-h-60 overflow-y-auto">
                            @foreach ($cartItems as $item)
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg flex items-center justify-center flex-shrink-0" style="background: #f3f4f6;">
                                        @if ($item->product->images && is_array($item->product->images) && count($item->product->images) > 0)
                                            <img src="{{ asset('storage/' . $item->product->images[0]) }}" alt="{{ $item->product->productName }}" class="w-10 h-10 rounded-lg object-cover">
                                        @else
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#9ca3af" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/></svg>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-sm font-medium truncate" style="color: #111827;">{{ Str::limit($item->product->productName, 25) }}</div>
                                        <div class="text-xs" style="color: #6b7280;">×{{ $item->quantity }}</div>
                                    </div>
                                    <div class="text-sm font-medium flex-shrink-0" style="color: #111827;">
                                        ${{ number_format($item->quantity * ($item->product->getCurrentSellingPriceAttribute() ?? 0), 2) }}
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <!-- Coupon Code -->
                        <div style="margin-bottom: 16px;">
                            <div class="flex gap-2">
                                <input type="text" id="coupon-code-input" placeholder="" style="flex: 1; padding: 10px 14px; border-radius: 10px; border: 1px solid #e5e7eb; font-size: 14px; text-transform: uppercase; letter-spacing: 0.04em; outline: none;">
                                <button type="button" id="coupon-apply-btn" onclick="applyCoupon()" style="padding: 10px 16px; border-radius: 10px; border: none; background: #1d1d1f; color: #fff; font-size: 13px; font-weight: 600; cursor: pointer; transition: transform 150ms ease; white-space: nowrap;" onmousedown="this.style.transform='scale(0.95)'" onmouseup="this.style.transform='scale(1)'">{{ __('Apply') }}</button>
                            </div>
                            <div id="coupon-message" style="display: none; font-size: 13px; margin-top: 6px; font-weight: 500;"></div>
                            <input type="hidden" name="coupon_code" id="coupon_code_hidden" value="{{ old('coupon_code') }}">
                            <input type="hidden" name="coupon_discount" id="coupon_discount_hidden" value="0">
                        </div>

                        <!-- Totals -->
                        <div class="border-t pt-4 space-y-2" style="border-color: #f3f4f6;">
                            <div class="flex justify-between text-sm">
                                <span style="color: #6b7280;">{{ __('Subtotal') }}</span>
                                <span style="color: #111827;">${{ number_format($subtotal, 2) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span style="color: #6b7280;">{{ __('Shipping') }}</span>
                                <span style="color: #111827;">${{ number_format($shipping, 2) }}</span>
                            </div>
                            @if ($tax > 0)
                                <div class="flex justify-between text-sm">
                                    <span style="color: #6b7280;">{{ __('Tax') }}</span>
                                    <span style="color: #111827;">${{ number_format($tax, 2) }}</span>
                                </div>
                            @endif
                            <div id="coupon-discount-row" class="flex justify-between text-sm" style="display: none;">
                                <span style="color: #15803d;">{{ __('Coupon Discount') }}</span>
                                <span id="coupon-discount-display" style="color: #15803d; font-weight: 600;">-$0.00</span>
                            </div>
                            <div class="border-t pt-2 flex justify-between font-bold text-lg" style="border-color: #f3f4f6;">
                                <span style="color: #111827;">{{ __('Total') }}</span>
                                <span style="color: #15803d;">${{ number_format($total, 2) }}</span>
                            </div>
                        </div>

                        <!-- Place Order -->
                        <button type="submit"
                            class="w-full mt-6 py-3.5 rounded-xl text-white font-semibold text-sm transition-all"
                            style="background: #15803d;"
                            onmousedown="this.style.transform='scale(0.98)'" onmouseup="this.style.transform='scale(1)'">
                            {{ __('Place Order') }}
                        </button>

                        <p class="text-xs text-center mt-3" style="color: #9ca3af;">
                            {{ __('By placing this order, you agree to our terms.') }}
                        </p>
                    </div>
                </div>
            </div>
        </form>
    </div>
</main>
@endsection

@push('scripts')
<script>
    function applyCoupon() {
        const code = document.getElementById('coupon-code-input').value.trim();
        if (!code) return;

        const btn = document.getElementById('coupon-apply-btn');
        const msgEl = document.getElementById('coupon-message');
        btn.disabled = true;
        btn.textContent = '...';

        fetch('/checkout/validate-coupon', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ code: code, subtotal: {{ $subtotal }} })
        })
        .then(r => r.json())
        .then(data => {
            btn.disabled = false;
            btn.textContent = '{{ __('Apply') }}';
            msgEl.style.display = 'block';

            if (data.valid) {
                msgEl.style.color = '#15803d';
                msgEl.textContent = data.message;
                document.getElementById('coupon_code_hidden').value = code;
                document.getElementById('coupon_discount_hidden').value = data.discount;
                document.getElementById('coupon-discount-row').style.display = 'flex';
                document.getElementById('coupon-discount-display').textContent = '-$' + data.discount.toFixed(2);
                // Update total
                const total = {{ $subtotal }} + {{ $shipping }} + {{ $tax }} - data.discount;
                document.querySelector('.font-bold.text-lg span:last-child').textContent = '$' + Math.max(0, total).toFixed(2);
            } else {
                msgEl.style.color = '#dc2626';
                msgEl.textContent = data.message;
                document.getElementById('coupon_code_hidden').value = '';
                document.getElementById('coupon_discount_hidden').value = 0;
                document.getElementById('coupon-discount-row').style.display = 'none';
            }
        })
        .catch(() => {
            btn.disabled = false;
            btn.textContent = '{{ __('Apply') }}';
        });
    }
</script>
@endpush
