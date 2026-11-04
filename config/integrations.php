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

    'backups' => [
        // Ship backup:database artifacts to the Supabase Storage 'backups'
        // bucket (requires SUPABASE_URL + SUPABASE_SERVICE_ROLE_KEY).
        'cloud_enabled' => env('BACKUP_CLOUD_ENABLED', false),
        'bucket' => env('BACKUP_CLOUD_BUCKET', 'backups'),
        'keep_days' => (int) env('BACKUP_CLOUD_KEEP_DAYS', 30),
    ],
];
