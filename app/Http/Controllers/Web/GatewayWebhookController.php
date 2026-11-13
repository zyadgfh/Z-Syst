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
        // {gateway} is a free URL segment. Refuse anything outside the known set
        // before it reaches a query or a config() lookup — an unknown name would
        // 404 on the transaction lookup anyway, but this keeps the failure away
        // from the database entirely and makes the intent explicit.
        abort_unless(PaymentGatewayService::isSupported($gateway), 404);

        $tx = $gateways->handleCallback($gateway, $transaction, $request->all());

        return response()->json([
            'success' => $tx->status === 'paid',
            'status' => $tx->status,
            'reference' => $tx->reference,
        ]);
    }
}
