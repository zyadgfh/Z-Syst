<?php

namespace App\Library;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class PhonePe
{
    public static function redirect_if_payment_success()
    {
        if (Session::has('fund_callback')) {
            return url(Session::get('fund_callback')['success_url']);
        } else {
            return url('payment/success');
        }
    }

    public static function redirect_if_payment_faild()
    {
        if (Session::has('fund_callback')) {
            return url(Session::get('fund_callback')['cancel_url']);
        } else {
            return url('payment/failed');
        }
    }

    public static function make_payment($array)
    {
        $clientId = $array['key_id'];
        $clientSecret = $array['key_secret'];

        $urlForToken = $array['mode'] == 'Live' ? 'https://api.phonepe.com/apis/identity-manager/v1/oauth/token' : 'https://api-preprod.phonepe.com/apis/pg-sandbox/v1/oauth/token';

        $urlForCreatePayment = $array['mode'] == 'Live' ? 'https://api.phonepe.com/apis/pg/checkout/v2/pay' : 'https://api-preprod.phonepe.com/apis/pg-sandbox/checkout/v2/pay';

        $transactionId = 'txn_' . Str::random(8);

        // Step 1: Generate Access Token
        $tokenResponse = Http::asForm()->post($urlForToken, [
            'client_version' => 1,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => 'client_credentials'
        ]);

        $tokenData = $tokenResponse->json();

        if (!isset($tokenData['access_token'])) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to get access token',
                'data' => $tokenData
            ]);
        }

        $accessToken = $tokenData['access_token'];

        // Step 2: Create Payment
        $payload = [
            "merchantOrderId" => $transactionId,
            "amount" => $array['pay_amount'] * 100,
            "expireAfter" => 1200,
            "metaInfo" => [
                "udf1" => $array['payment_type']
            ],
            "paymentFlow" => [
                "type" => "PG_CHECKOUT",
                "message" => "Payment message used for collect requests",
                "merchantUrls" => [
                    "redirectUrl" => route('phonepe.status')
                ]
            ]
        ];

        $paymentResponse = Http::withHeaders([
            'Authorization' => 'O-Bearer ' . $accessToken,
            'Content-Type' => 'application/json'
        ])->post($urlForCreatePayment, $payload);

        $paymentData = $paymentResponse->json();

        Session::put('phonepe_credentials', [
            'mode' => $array['mode'],
            'clientId' => $clientId,
            'clientSecret' => $clientSecret,
            'merchantOrderId' => $transactionId,
        ]);

        // Redirect to PhonePe payment page
        if (isset($paymentData['redirectUrl'])) {
            return redirect()->away($paymentData['redirectUrl']);
        }

        return $paymentData; // fallback for debugging
    }

    public function status()
    {
        $phonepe_credentials = Session('phonepe_credentials');

        if (!$phonepe_credentials) {
            session()->put('payment_msg', __('Invalid request, Please contact with admin.'));
            return redirect(Paytm::redirect_if_payment_faild());
        }

        $mode = $phonepe_credentials['mode'];
        $clientId = $phonepe_credentials['clientId'];
        $clientSecret = $phonepe_credentials['clientSecret'];
        $merchantOrderId = $phonepe_credentials['merchantOrderId'];

        $urlForToken = $mode == 'Live' ? 'https://api.phonepe.com/apis/identity-manager/v1/oauth/token' : 'https://api-preprod.phonepe.com/apis/pg-sandbox/v1/oauth/token';

        $statusUrl = $phonepe_credentials['mode'] == 'Live' ? 'https://api.phonepe.com/apis/pg/checkout/v2/order/' . $merchantOrderId . '/status' : 'https://api-preprod.phonepe.com/apis/pg-sandbox/checkout/v2/order/' . $merchantOrderId . '/status';

        // Step 1: Get Access Token (asForm)
        $tokenResponse = Http::asForm()->post($urlForToken, [
            'client_version' => 1,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => 'client_credentials'
        ]);

        $tokenData = $tokenResponse->json();
        $accessToken = $tokenData['access_token'] ?? null;

        if (!$accessToken) {
            return response()->json(['error' => 'Unable to get access token', 'data' => $tokenData], 400);
        }

        // Step 2: Check Order Status
        $statusResponse = Http::withHeaders([
            'Authorization' => 'O-Bearer ' . $accessToken,
            'Content-Type' => 'application/json'
        ])->get($statusUrl);

        $statusData = $statusResponse->json();

        session()->forget('phonepe_credentials');

        // Step 3: Handle Payment State
        if (isset($statusData['state'])) {
            if ($statusData['state'] === 'COMPLETED') {

                return redirect(Paytm::redirect_if_payment_success());
            } else {

                session()->put('payment_msg', __('Payment failed, Please verify your credentials or contact with admin.'));
                return redirect(Paytm::redirect_if_payment_faild());
            }
        }

        session()->put('payment_msg', 'Payment failed.');
        return redirect(Paytm::redirect_if_payment_faild());
    }
}
