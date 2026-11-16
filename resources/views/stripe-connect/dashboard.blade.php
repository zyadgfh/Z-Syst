@extends('stripe-connect.layout')

@section('title', 'Stripe Connect Dashboard')

@section('nav_extra')
    @if ($selectedAccount)
        <a class="nav-link" href="{{ route('stripe-connect.storefront.show', ['accountId' => $selectedAccount->stripe_account_id]) }}">
            Open Storefront →
        </a>
    @endif
@endsection

@section('content')
    <div class="page-header">
        <h1>Stripe Connect Sample Integration</h1>
        <p>
            Onboard connected accounts via Stripe V2 Accounts &amp; Account Links, manage products with the
            <span class="mono">Stripe-Account</span> header, charge customers via Direct Charges, and bill connected accounts
            with platform subscriptions (<span class="mono">customer_account</span>).
        </p>
    </div>

    {{-- Demo-mode context banner when visiting /connect without a Z-Syst login --}}
    @if (! $isAuthenticated)
        <div class="alert alert-warning">
            <strong>Demo Session Mode:</strong> You are not signed in to Z-Syst. Connected accounts you create here are
            stored in the database and scoped to your current browser session. Sign in to Z-Syst to bind connected accounts
            permanently to your <span class="mono">app_users</span> user record.
        </div>
    @endif

    @if ($onboardingRefreshed)
        <div class="alert alert-warning">
            Your previous onboarding link expired or was refreshed. Click <strong>Onboard to collect payments</strong>
            below to generate a fresh Account Link.
        </div>
    @elseif ($onboardingReturned && $selectedAccount)
        <div class="alert alert-success">
            Returned from Stripe onboarding for <span class="mono">{{ $selectedAccount->stripe_account_id }}</span>.
            The onboarding status below was retrieved live from Stripe's V2 Accounts API.
        </div>
    @endif

    @if ($subscriptionNotice === 'canceled')
        <div class="alert alert-warning">
            Platform subscription checkout was canceled. You can restart checkout at any time below.
        </div>
    @endif

    @if ($stripeError)
        <div class="alert alert-error">
            <strong>Stripe API / Configuration Notice:</strong> {{ $stripeError }}
        </div>
    @endif

    {{-- Environment Placeholder / Setup Checklist --}}
    @if (! $allConfigured)
        <div class="card" style="margin-bottom: 20px; border-color: rgba(154, 103, 0, 0.28);">
            <div class="card-header">
                <div>
                    <h2>Configuration Checklist</h2>
                    <p class="muted" style="margin: 0;">
                        Fill in these placeholder variables in your <span class="mono">.env</span> file to enable every flow.
                        SDK version <span class="mono">stripe/stripe-php v23.0.0</span> automatically uses API version
                        <span class="mono">2026-10-28.endive</span>.
                    </p>
                </div>
            </div>
            <table class="list-table">
                <thead>
                    <tr>
                        <th>Environment Variable</th>
                        <th>Status</th>
                        <th>How to Configure</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($checklist as $item)
                        <tr>
                            <td><span class="mono">{{ $item['env'] }}</span></td>
                            <td>
                                @if ($item['configured'])
                                    <span class="badge badge-green">Configured</span>
                                @else
                                    <span class="badge badge-amber">Placeholder / Missing</span>
                                @endif
                            </td>
                            <td class="muted">{{ $item['hint'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="grid">
        {{-- STEP 1: Create V2 Connected Account --}}
        <div class="col-6">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2>1. Create Connected Account (V2 API)</h2>
                        <p class="muted" style="margin: 0;">
                            Creates a Stripe V2 Account with <span class="mono">merchant</span> &amp; <span class="mono">customer</span>
                            configurations and saves the user-to-account mapping in <span class="mono">stripe_connected_accounts</span>.
                        </p>
                    </div>
                </div>

                <form method="POST" action="{{ route('stripe-connect.accounts.store') }}">
                    @csrf
                    <div class="form-grid">
                        <div class="form-field full">
                            <label for="display_name">Business / Display Name</label>
                            <input
                                id="display_name"
                                name="display_name"
                                type="text"
                                required
                                value="{{ old('display_name', $defaultDisplayName) }}"
                                placeholder="e.g. Acme Pharmacy"
                            >
                        </div>
                        <div class="form-field full">
                            <label for="contact_email">Contact Email</label>
                            <input
                                id="contact_email"
                                name="contact_email"
                                type="email"
                                required
                                value="{{ old('contact_email', $defaultContactEmail) }}"
                                placeholder="owner@example.com"
                            >
                        </div>
                        <div class="form-field full">
                            <button type="submit" class="btn btn-primary">Create Connected Account</button>
                        </div>
                    </div>
                </form>

                @if ($accounts->isNotEmpty())
                    <hr style="border: none; border-top: 1px solid var(--ap-hairline); margin: 18px 0;">
                    <h3>Your Connected Accounts ({{ $accounts->count() }})</h3>
                    <div class="btn-row" style="margin-top: 8px;">
                        @foreach ($accounts as $acct)
                            <a
                                href="{{ route('stripe-connect.dashboard', ['accountId' => $acct->stripe_account_id]) }}"
                                class="btn btn-sm {{ $selectedAccount && $selectedAccount->id === $acct->id ? 'btn-primary' : 'btn-secondary' }}"
                            >
                                {{ $acct->display_name }} ({{ \Illuminate\Support\Str::limit($acct->stripe_account_id, 16) }})
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- STEP 2: Onboarding & Live Status (ALWAYS from Stripe V2 Accounts API) --}}
        <div class="col-6">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2>2. Onboarding &amp; Live Account Status</h2>
                        <p class="muted" style="margin: 0;">
                            Status is retrieved directly from <span class="mono">v2.core.accounts.retrieve()</span> on every load (never cached in DB).
                        </p>
                    </div>
                </div>

                @if (! $selectedAccount)
                    <p class="muted">Create a connected account in Step 1 to begin Stripe Connect onboarding.</p>
                @else
                    <div style="margin-bottom: 12px;">
                        <strong>{{ $selectedAccount->display_name }}</strong>
                        <span class="mono">{{ $selectedAccount->stripe_account_id }}</span>
                    </div>

                    @if ($liveStatus)
                        <div class="stat-grid" id="live-status-grid">
                            <div class="stat-box">
                                <div class="stat-label">Ready to Process Payments</div>
                                <div class="stat-value" id="status-ready">
                                    @if ($liveStatus['ready_to_process_payments'])
                                        <span class="badge badge-green">Active &amp; Ready</span>
                                    @else
                                        <span class="badge badge-amber">Not Ready Yet</span>
                                    @endif
                                </div>
                            </div>

                            <div class="stat-box">
                                <div class="stat-label">Onboarding Complete</div>
                                <div class="stat-value" id="status-onboarding">
                                    @if ($liveStatus['onboarding_complete'])
                                        <span class="badge badge-green">Complete</span>
                                    @else
                                        <span class="badge badge-amber">Action Required</span>
                                    @endif
                                </div>
                            </div>

                            <div class="stat-box">
                                <div class="stat-label">Card Payments Capability</div>
                                <div class="stat-value" id="status-capability">
                                    <span class="mono">{{ $liveStatus['card_payments_status'] ?? 'pending' }}</span>
                                </div>
                            </div>

                            <div class="stat-box">
                                <div class="stat-label">Requirements Deadline</div>
                                <div class="stat-value" id="status-requirements">
                                    <span class="mono">{{ $liveStatus['requirements_status'] ?? 'none' }}</span>
                                </div>
                            </div>
                        </div>

                        @if (! empty($liveStatus['requirements_entries']))
                            <div class="alert alert-warning" style="margin-top: 10px;">
                                <strong>Pending Requirements:</strong>
                                <ul style="margin: 6px 0 0; padding-left: 18px;">
                                    @foreach ($liveStatus['requirements_entries'] as $entry)
                                        <li>
                                            {{ $entry['description'] }}
                                            @if ($entry['deadline_status'])
                                                <span class="mono">{{ $entry['deadline_status'] }}</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    @else
                        <p class="muted">Live status could not be loaded (check your Stripe API key above).</p>
                    @endif

                    <div class="btn-row" style="margin-top: 16px;">
                        <form
                            method="POST"
                            action="{{ route('stripe-connect.accounts.onboard', ['accountId' => $selectedAccount->stripe_account_id]) }}"
                            data-stripe-redirect-form
                        >
                            @csrf
                            <button type="submit" class="btn btn-primary" data-loading-text="Opening Stripe Onboarding...">
                                Onboard to collect payments
                            </button>
                        </form>

                        <button
                            type="button"
                            class="btn btn-secondary"
                            id="refresh-live-status-btn"
                            data-status-url="{{ route('stripe-connect.accounts.status', ['accountId' => $selectedAccount->stripe_account_id]) }}"
                        >
                            Refresh Live Status
                        </button>
                    </div>
                @endif
            </div>
        </div>

        @if ($selectedAccount)
            {{-- STEP 3: Create Products on Connected Account & View Storefront --}}
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h2>3. Connected Account Products</h2>
                            <p class="muted" style="margin: 0;">
                                Products are created directly on <span class="mono">{{ $selectedAccount->stripe_account_id }}</span>
                                via the <span class="mono">Stripe-Account</span> header.
                            </p>
                        </div>
                        <a
                            href="{{ route('stripe-connect.storefront.show', ['accountId' => $selectedAccount->stripe_account_id]) }}"
                            class="btn btn-sm btn-success"
                        >
                            Open Storefront →
                        </a>
                    </div>

                    <form
                        method="POST"
                        action="{{ route('stripe-connect.products.store', ['accountId' => $selectedAccount->stripe_account_id]) }}"
                    >
                        @csrf
                        <div class="form-grid">
                            <div class="form-field full">
                                <label for="product_name">Product Name</label>
                                <input id="product_name" name="name" type="text" required value="{{ old('name') }}" placeholder="e.g. Vitamin D3 Complex">
                            </div>
                            <div class="form-field full">
                                <label for="product_description">Description</label>
                                <textarea id="product_description" name="description" rows="2" placeholder="Optional product summary shown on the storefront &amp; checkout">{{ old('description') }}</textarea>
                            </div>
                            <div class="form-field">
                                <label for="product_price">Price (e.g. 19.99)</label>
                                <input id="product_price" name="price" type="number" step="0.01" min="0.50" required value="{{ old('price', '19.99') }}">
                            </div>
                            <div class="form-field">
                                <label for="product_currency">Currency</label>
                                <select id="product_currency" name="currency">
                                    @foreach (['usd' => 'USD ($)', 'eur' => 'EUR (€)', 'gbp' => 'GBP (£)'] as $code => $label)
                                        <option value="{{ $code }}" @selected(old('currency', $defaultCurrency) === $code)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-field full">
                                <button type="submit" class="btn btn-primary">Create Product on Connected Account</button>
                            </div>
                        </div>
                    </form>

                    @if (count($products) > 0)
                        <hr style="border: none; border-top: 1px solid var(--ap-hairline); margin: 18px 0;">
                        <h3>Active Products ({{ count($products) }})</h3>
                        <table class="list-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Stripe ID</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($products as $product)
                                    @php
                                        $priceObj = is_object($product->default_price) ? $product->default_price : null;
                                        $amount = $priceObj && is_int($priceObj->unit_amount) ? number_format($priceObj->unit_amount / 100, 2) : '—';
                                        $curr = $priceObj && ! empty($priceObj->currency) ? strtoupper($priceObj->currency) : '';
                                    @endphp
                                    <tr>
                                        <td>
                                            <strong>{{ $product->name }}</strong>
                                            @if (! empty($product->description))
                                                <div class="muted">{{ $product->description }}</div>
                                            @endif
                                        </td>
                                        <td>{{ $amount }} {{ $curr }}</td>
                                        <td><span class="mono">{{ $product->id }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>
            </div>

            {{-- STEP 4: Platform Subscription & Billing Portal (`customer_account`) --}}
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <div>
                            <h2>4. Platform Subscription &amp; Billing Portal</h2>
                            <p class="muted" style="margin: 0;">
                                Bills this V2 connected account at the platform level using
                                <span class="mono">customer_account: "{{ $selectedAccount->stripe_account_id }}"</span>.
                                Status below syncs from <span class="mono">/webhooks/stripe/billing</span>.
                            </p>
                        </div>
                    </div>

                    <div class="stat-grid">
                        <div class="stat-box">
                            <div class="stat-label">Subscription Status</div>
                            <div class="stat-value">
                                @if ($selectedAccount->hasActiveSubscription())
                                    <span class="badge badge-green">{{ ucfirst($selectedAccount->subscription_status) }}</span>
                                @elseif ($selectedAccount->subscription_status)
                                    <span class="badge badge-amber">{{ ucfirst($selectedAccount->subscription_status) }}</span>
                                @else
                                    <span class="badge badge-neutral">Not Subscribed</span>
                                @endif
                            </div>
                        </div>

                        <div class="stat-box">
                            <div class="stat-label">Active Price / Qty</div>
                            <div class="stat-value">
                                @if ($selectedAccount->subscription_price_id)
                                    <span class="mono">{{ $selectedAccount->subscription_price_id }}</span>
                                    × {{ $selectedAccount->subscription_quantity ?? 1 }}
                                @else
                                    <span class="muted">—</span>
                                @endif
                            </div>
                        </div>

                        <div class="stat-box">
                            <div class="stat-label">Cancellation / Pause</div>
                            <div class="stat-value">
                                @if ($selectedAccount->isSubscriptionPaused())
                                    <span class="badge badge-amber">Paused ({{ $selectedAccount->pause_collection_behavior }})</span>
                                @elseif ($selectedAccount->cancel_at_period_end)
                                    <span class="badge badge-amber">Cancels at period end</span>
                                @else
                                    <span class="muted">None</span>
                                @endif
                            </div>
                        </div>

                        <div class="stat-box">
                            <div class="stat-label">Default Payment Method</div>
                            <div class="stat-value">
                                @if ($selectedAccount->default_payment_method_id)
                                    <span class="mono">{{ $selectedAccount->default_payment_method_id }}</span>
                                @else
                                    <span class="muted">None on file</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if (! empty($selectedAccount->tax_ids))
                        <div style="margin-top: 10px;">
                            <div class="stat-label">Customer Tax IDs</div>
                            <div class="btn-row" style="margin-top: 4px;">
                                @foreach ($selectedAccount->tax_ids as $tax)
                                    <span class="badge badge-neutral">
                                        {{ strtoupper((string) ($tax['type'] ?? 'tax')) }}: {{ $tax['value'] ?? '' }}
                                        ({{ $tax['verification_status'] ?? 'pending' }})
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="btn-row" style="margin-top: 16px;">
                        <form
                            method="POST"
                            action="{{ route('stripe-connect.subscriptions.checkout', ['accountId' => $selectedAccount->stripe_account_id]) }}"
                            data-stripe-redirect-form
                        >
                            @csrf
                            <button type="submit" class="btn btn-primary" data-loading-text="Opening Subscription Checkout...">
                                Subscribe to Platform Plan
                            </button>
                        </form>

                        <form
                            method="POST"
                            action="{{ route('stripe-connect.billing-portal', ['accountId' => $selectedAccount->stripe_account_id]) }}"
                            data-stripe-redirect-form
                        >
                            @csrf
                            <button type="submit" class="btn btn-secondary" data-loading-text="Opening Billing Portal...">
                                Manage Subscription (Billing Portal)
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endif

        {{-- STEP 5: Webhook Setup Reference --}}
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2>5. Local Webhook Forwarding (Stripe CLI)</h2>
                        <p class="muted" style="margin: 0;">
                            This sample exposes two distinct webhook endpoints: a V2 <strong>Thin</strong> Event Destination
                            for connected account updates, and a V1 <strong>Snapshot</strong> Webhook for platform subscriptions.
                        </p>
                    </div>
                </div>

                <h3>A. V2 Thin Events — Connected Account Requirements &amp; Capabilities (<span class="mono">POST /webhooks/stripe/thin</span>)</h3>
                <pre class="code-block">stripe listen \
  --thin-events 'v2.core.account[requirements].updated,v2.core.account[.recipient].capability_status_updated,v2.core.account[configuration.merchant].capability_status_updated,v2.core.account[configuration.customer].capability_status_updated' \
  --forward-thin-to {{ url('/webhooks/stripe/thin') }}</pre>

                <h3 style="margin-top: 18px;">B. V1 Snapshot Events — Platform Subscriptions &amp; Billing Portal (<span class="mono">POST /webhooks/stripe/billing</span>)</h3>
                <pre class="code-block">stripe listen \
  --events customer.subscription.created,customer.subscription.updated,customer.subscription.deleted,invoice.paid,payment_method.attached,payment_method.detached,customer.updated,customer.tax_id.created,customer.tax_id.updated,customer.tax_id.deleted,billing_portal.configuration.created,billing_portal.configuration.updated,billing_portal.session.created \
  --forward-to {{ url('/webhooks/stripe/billing') }}</pre>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    (function () {
        var btn = document.getElementById('refresh-live-status-btn');
        if (!btn) return;

        btn.addEventListener('click', async function () {
            var url = btn.getAttribute('data-status-url');
            var original = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Checking Stripe API...';

            try {
                var res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                var data = await res.json();
                if (!res.ok) {
                    throw new Error(data.error || 'Failed to fetch live account status.');
                }

                var readyEl = document.getElementById('status-ready');
                var onboardingEl = document.getElementById('status-onboarding');
                var capEl = document.getElementById('status-capability');
                var reqEl = document.getElementById('status-requirements');

                if (readyEl) {
                    readyEl.innerHTML = data.ready_to_process_payments
                        ? '<span class="badge badge-green">Active &amp; Ready</span>'
                        : '<span class="badge badge-amber">Not Ready Yet</span>';
                }
                if (onboardingEl) {
                    onboardingEl.innerHTML = data.onboarding_complete
                        ? '<span class="badge badge-green">Complete</span>'
                        : '<span class="badge badge-amber">Action Required</span>';
                }
                if (capEl) {
                    capEl.innerHTML = '<span class="mono"></span>';
                    capEl.querySelector('.mono').textContent = data.card_payments_status || 'pending';
                }
                if (reqEl) {
                    reqEl.innerHTML = '<span class="mono"></span>';
                    reqEl.querySelector('.mono').textContent = data.requirements_status || 'none';
                }
            } catch (e) {
                var errorBox = document.getElementById('js-stripe-error');
                if (errorBox) {
                    errorBox.textContent = e.message;
                    errorBox.classList.add('visible');
                }
            } finally {
                btn.disabled = false;
                btn.textContent = original;
            }
        });
    })();
</script>
@endsection
