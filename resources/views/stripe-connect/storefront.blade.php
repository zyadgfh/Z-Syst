@extends('stripe-connect.layout')

@section('title', $merchantName . ' — Storefront')

@section('content')
    {{--
        DEMO URL NOTE:
        This storefront route currently uses the raw Stripe Connected Account ID
        (`/connect/storefront/{accountId}`). In production, use a non-Stripe public
        identifier (such as `businesses.store_slug` or an internal UUID) in the URL
        and look up the connected account's `stripe_account_id` server-side.
    --}}
    <div class="page-header" style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 14px;">
        <div>
            <h1>{{ $merchantName }}</h1>
            <p>
                Storefront for connected account <span class="mono">{{ $accountId }}</span>.
                Purchases are processed via a Stripe <strong>Direct Charge</strong> with a
                ${{ number_format($applicationFeeCents / 100, 2) }} sample platform application fee.
            </p>
        </div>
        <a class="btn btn-secondary btn-sm" href="{{ route('stripe-connect.dashboard', ['accountId' => $accountId]) }}">
            ← Back to Connect Dashboard
        </a>
    </div>

    @if ($stripeError)
        <div class="alert alert-error">
            <strong>Unable to load products:</strong> {{ $stripeError }}
        </div>
    @elseif (count($products) === 0)
        <div class="card">
            <h2>No products listed yet</h2>
            <p class="muted">
                This connected account has no active Stripe products yet. Return to the Connect Dashboard to create a product.
            </p>
            <a class="btn btn-primary" href="{{ route('stripe-connect.dashboard', ['accountId' => $accountId]) }}">
                Create First Product
            </a>
        </div>
    @else
        <div class="product-grid">
            @foreach ($products as $product)
                @php
                    $priceObj = is_object($product->default_price) ? $product->default_price : null;
                    $hasPrice = $priceObj && is_int($priceObj->unit_amount) && $priceObj->unit_amount > 0;
                    $formattedPrice = $hasPrice ? number_format($priceObj->unit_amount / 100, 2) : null;
                    $currency = $hasPrice && ! empty($priceObj->currency) ? strtoupper($priceObj->currency) : 'USD';
                @endphp
                <div class="product-card">
                    <div>
                        <h3 style="margin-bottom: 6px; font-size: 1.08rem;">{{ $product->name }}</h3>
                        @if (! empty($product->description))
                            <p class="muted" style="margin: 0 0 12px;">{{ $product->description }}</p>
                        @endif
                        <div class="price-tag">
                            @if ($hasPrice)
                                {{ $formattedPrice }} <span style="font-size: 0.85rem; font-weight: 600; color: var(--ap-text-2);">{{ $currency }}</span>
                            @else
                                <span class="muted">No default price</span>
                            @endif
                        </div>
                    </div>

                    @if ($hasPrice)
                        <form
                            method="POST"
                            action="{{ route('stripe-connect.checkout.create', ['accountId' => $accountId]) }}"
                            data-stripe-redirect-form
                        >
                            @csrf
                            <input type="hidden" name="product_id" value="{{ $product->id }}">
                            <div style="display: flex; gap: 10px; align-items: center;">
                                <label for="qty-{{ $product->id }}" class="muted" style="font-size: 0.82rem;">Qty</label>
                                <input
                                    id="qty-{{ $product->id }}"
                                    type="number"
                                    name="quantity"
                                    value="1"
                                    min="1"
                                    max="99"
                                    style="width: 72px; padding: 8px 10px; border: 1px solid rgba(0,0,0,0.15); border-radius: 10px;"
                                >
                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                    style="flex: 1;"
                                    data-loading-text="Starting Checkout..."
                                >
                                    Buy Now
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
@endsection
