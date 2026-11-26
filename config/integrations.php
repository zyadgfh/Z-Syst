<?php

/*
|--------------------------------------------------------------------------
| External integrations — flags + credentials (all off by default)
|--------------------------------------------------------------------------
| Each block enables a third-party service used by app/Services/Integrations.
| Services no-op gracefully when disabled, so previews and local dev keep
| working without credentials.
*/

return [

    'http_timeout' => env('INTEGRATIONS_HTTP_TIMEOUT', 15),

    'paymob' => [
        'enabled' => env('PAYMOB_ENABLED', false),
        'base_url' => env('PAYMOB_BASE_URL', 'https://accept.paymob.com/api'),
        'api_key' => env('PAYMOB_API_KEY'),
        'integration_id' => env('PAYMOB_INTEGRATION_ID'),
        'iframe_id' => env('PAYMOB_IFRAME_ID'),
        'hmac_secret' => env('PAYMOB_HMAC_SECRET'),
        'currency' => env('PAYMOB_CURRENCY', 'EGP'),
        'hmac_keys' => [
            'amount_cents', 'created_at', 'currency', 'error_occured', 'has_parent_transaction',
            'id', 'integration_id', 'is_3d_secure', 'is_auth', 'is_capture', 'is_refunded',
            'is_standalone_payment', 'is_voided', 'order.id', 'owner', 'pending',
            'source_data.pan', 'source_data.sub_type', 'source_data.type', 'success',
        ],
    ],

    'fawry' => [
        'enabled' => env('FAWRY_ENABLED', false),
        // Staging by default: pointing a half-configured install at the
        // production host would create real orders with bad signatures.
        'sandbox' => env('FAWRY_SANDBOX', true),
        'base_url' => env('FAWRY_BASE_URL')
            ?: (env('FAWRY_SANDBOX', true)
                ? 'https://atfawry.fawrystaging.com'
                : 'https://www.atfawry.com'),
        'merchant_code' => env('FAWRY_MERCHANT_CODE'),
        'secure_key' => env('FAWRY_SECURE_KEY'),
        // Aliases read by PaymentGatewayService::mode() / verifySignature()
        // (they probe services.{gateway}.* then integrations.{gateway}.*);
        // Fawry's merchant code / secure key ARE its platform credentials.
        'api_key' => env('FAWRY_MERCHANT_CODE'),
        'hmac_secret' => env('FAWRY_SECURE_KEY'),
        'customer_profile_id' => env('FAWRY_CUSTOMER_PROFILE_ID', '0'),
        // Request-side token per Fawry's charge docs (their response echoes
        // it uppercased as PAYATFAWRY).
        'payment_method' => env('FAWRY_PAYMENT_METHOD', 'PayAtFawry'),
        'currency' => env('FAWRY_CURRENCY', 'EGP'),
        'language' => env('FAWRY_LANGUAGE', 'en-gb'),
    ],

    'whatsapp' => [
        'enabled' => env('WHATSAPP_ENABLED', false),
        'version' => env('WHATSAPP_VERSION', 'v21.0'),
        'token' => env('WHATSAPP_TOKEN'),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'),
    ],

    'eta' => [
        'enabled' => env('ETA_ENABLED', false),
        'sandbox' => env('ETA_SANDBOX', true),
        'base_url' => env('ETA_BASE_URL', 'https://api.invoicing.eta.gov.eg/api/v1'),
        'client_id' => env('ETA_CLIENT_ID'),
        'client_secret' => env('ETA_CLIENT_SECRET'),
        'issuer_tax_id' => env('ETA_ISSUER_TAX_ID'),
        'issuer_name' => env('ETA_ISSUER_NAME', 'Pharmacy'),
        'issuer_governate' => env('ETA_ISSUER_GOVERNATE', 'Cairo'),
        'issuer_city' => env('ETA_ISSUER_CITY', 'Cairo'),
        'issuer_street' => env('ETA_ISSUER_STREET', '-'),
        'activity_code' => env('ETA_ACTIVITY_CODE', '4772'),
    ],

    'uploads' => [
        // Ship NEW user uploads to the Supabase Storage 'uploads' bucket via
        // HasUploader (requires SUPABASE_URL + SUPABASE_SERVICE_ROLE_KEY).
        // Local disk stays the automatic fallback on any failure.
        'cloud_enabled' => env('UPLOADS_CLOUD_ENABLED', false),
        'bucket' => env('UPLOADS_CLOUD_BUCKET', 'uploads'),

        // H-02: upload allowlist — SVG intentionally excluded (stored-XSS
        // when served inline same-origin). Extend only with attachment/CDN
        // isolation in place.
        'allowed_extensions' => explode(',', env('UPLOADS_ALLOWED_EXTENSIONS', 'png,jpg,jpeg,webp,gif,pdf')),
        'allowed_mimes' => explode(',', env('UPLOADS_ALLOWED_MIMES', 'image/png,image/jpeg,image/webp,image/gif,application/pdf')),
    ],

    'backups' => [
        // Ship backup:database artifacts to the Supabase Storage 'backups'
        // bucket (requires SUPABASE_URL + SUPABASE_SERVICE_ROLE_KEY).
        'cloud_enabled' => env('BACKUP_CLOUD_ENABLED', false),
        'bucket' => env('BACKUP_CLOUD_BUCKET', 'backups'),
        'keep_days' => (int) env('BACKUP_CLOUD_KEEP_DAYS', 30),
    ],
];
