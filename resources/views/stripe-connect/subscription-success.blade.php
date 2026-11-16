@extends('stripe-connect.layout')

@section('title', 'Platform Subscription Confirmed')

@section('content')
    <div class="card" style="max-width: 640px; margin: 20px auto;">
        <div class="badge badge-green" style="margin-bottom: 12px;">Subscription Checkout Complete</div>
        <h1 style="margin: 0 0 8px; font-size: 1.65rem;">Platform subscription started</h1>
        <p class="muted" style="margin-top: 0;">
            Subscription checkout for connected account <strong>{{ $account->display_name }}</strong>
            (<span class="mono">{{ $account->stripe_account_id }}</span>) finished.
            Stripe will deliver <span class="mono">customer.subscription.updated</span> and
            <span class="mono">invoice.paid</span> webhook events to <span class="mono">/webhooks/stripe/billing</span>
            to synchronize your database subscription status.
        </p>

        @if ($sessionId !== '')
            <div class="stat-box" style="margin: 16px 0;">
                <div class="stat-label">Checkout Session ID</div>
                <div class="stat-value"><span class="mono">{{ $sessionId }}</span></div>
            </div>
        @endif

        <div class="btn-row" style="margin-top: 20px;">
            <a class="btn btn-primary" href="{{ route('stripe-connect.dashboard', ['accountId' => $account->stripe_account_id]) }}">
                Return to Connect Dashboard
            </a>
        </div>
    </div>
@endsection
