<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;

/** Gateway callbacks (Paymob/Fawry/InstaPay) — signature-verified. */
class GatewayWebhookController extends Controller
{
    public function __invoke(string $gateway, int $transaction, Request $request, PaymentGatewayService $gateways)
    {
        $tx = $gateways->handleCallback($gateway, $transaction, $request->all());

        return response()->json([
            'success' => $tx->status === 'paid',
            'status' => $tx->status,
            'reference' => $tx->reference,
        ]);
    }
}
