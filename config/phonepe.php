<?php

return array(
    'merchantId' => env('PHONEPE_MERCHANT_ID', 'PGTESTPAYUAT'),
    'merchantUserId' => env('PHONEPE_MERCHANT_USER_ID', 'MUID123'),
    'env' => env('PHONEPE_ENV', 'staging'),
    'saltKey' => env('PHONEPE_SALT_KEY', '099eb0cd-02cf-4e2a-8aca-3e6c6aff0399'),
    'saltIndex' => env('PHONEPE_SALT_INDEX', '1'),
    'redirectUrl' => env('PHONEPE_REDIRECT_URL', 'phonepe/status'),
    'callBackUrl' => env('PHONEPE_CALLBACK_URL', 'phonepe/status')
);
