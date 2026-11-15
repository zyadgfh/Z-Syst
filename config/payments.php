<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Automatic (gateway-hosted) payments
    |--------------------------------------------------------------------------
    |
    | Master switch for every payment path that hands the customer to an
    | external provider and then trusts a callback. When false — the default —
    | the application runs on manual payment only: the customer submits a
    | payment request with a receipt, and a platform superadmin approves or
    | rejects it in the admin panel.
    |
    | One switch rather than per-gateway flags, because the two payment systems
    | in this codebase are wired differently and a flag on one leaves the other
    | open:
    |
    |   * the `gateways` table drives checkout on /payments-gateways, where a
    |     row with is_manual = 1 takes the manual branch and a row with a
    |     `namespace` calls App\Library\*::make_payment() and redirects out;
    |   * PaymentGatewayService (paymob / fawry / instapay) is automatic-only,
    |     driven from the admin checkout endpoint and settled by the public
    |     /gateway/{gateway}/callback/{transaction} webhook.
    |
    | With the switch off, all four entry points refuse: the checkout gateway
    | list shows manual rows only, the automatic branch of the checkout form is
    | rejected, PaymentGatewayService::initiate() will not create a transaction,
    | and the webhook answers 404 so no callback — live, stale or replayed — can
    | move a transaction to paid.
    |
    | Turning it back on is `PAYMENTS_AUTOMATIC_ENABLED=true` plus the provider
    | keys, and nothing else. Read RUNBOOK §10.4 first: adding a platform
    | api_key flips PaymentGatewayService::mode() to `live`, and for fawry and
    | instapay the signature does not cover the outcome, so callbacks settle as
    | `pending` until a server-to-server status query is wired in. Enabling the
    | switch without that is how a tenant pays and gets nothing.
    |
    | The switch is deliberately NOT the only control on the manual side. Manual
    | approval is a platform action: the money goes to the platform, not to the
    | tenant, so approving it must not be reachable by the tenant whose
    | subscription it activates. See the `role:superadmin` gates on
    | admin.subscription-reports.* and admin.manual-payments.* in routes/admin.php.
    |
    */

    'automatic_enabled' => env('PAYMENTS_AUTOMATIC_ENABLED', false),

];
