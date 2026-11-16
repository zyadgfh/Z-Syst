@extends('stripe-connect.layout')

@section('title', 'Payment Complete')

@section('content')
    <div class="card" style="max-width: 640px; margin: 20px auto;">
        <div class="badge badge-green" style="margin-bottom: 12px;">Payment Successful</div>
        <h1 style="margin: 0 0 8px; font-size: 1.65rem;">Thank you for your purchase!</h1>
        <p class="muted" style="margin-top: 0;">
            Your Direct Charge checkout with <strong>{{ $merchantName }}</strong> has completed.
        </p>

        @if ($stripeError)
            <div class="alert alert-warning">
                Could not retrieve Checkout Session details: {{ $stripeError }}
            </div>
        @endif

        <div class="stat-grid">
            @if ($sessionId !== '')
                <div class="stat-box">
                    <div class="stat-label">Checkout Session</div>
                    <div class="stat-value"><span class="mono">{{ $sessionId }}</span></div>
                </div>
            @endif

            @if ($session)
                <div class="stat-box">
                    <div class="stat-label">Payment Status</div>
                    <div class="stat-value">{{ ucfirst((string) ($session->payment_status ?? 'paid')) }}</div>
                </div>

                @if (is_int($session->amount_total ?? null))
                    <div class="stat-box">
                        <div class="stat-label">Total Paid</div>
                        <div class="stat-value">
                            {{ number_format($session->amount_total / 100, 2) }}
                            {{ strtoupper((string) ($session->currency ?? 'usd')) }}
                        </div>
                    </div>
                @endif
            @endif
        </div>

        <div class="btn-row" style="margin-top: 20px;">
            <a class="btn btn-primary" href="{{ route('stripe-connect.storefront.show', ['accountId' => $accountId]) }}">
                Continue Shopping
            </a>
            <a class="btn btn-secondary" href="{{ route('stripe-connect.dashboard', ['accountId' => $accountId]) }}">
                Connect Dashboard
            </a>
        </div>
    </div>
@endsection
