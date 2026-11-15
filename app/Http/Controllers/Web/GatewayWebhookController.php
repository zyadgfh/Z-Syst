<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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

        // Automatic payments are switched off (config/payments.php), so this
        // endpoint settles nothing while it is off. A callback that arrives
        // anyway is either a provider retrying a transaction opened before the
        // switch, or someone probing — and a *valid* signature is no reason to
        // accept it, because the transaction it refers to should not exist.
        //
        // 404 rather than 403 on purpose: it is the same answer an unknown
        // gateway gets, so an unauthenticated caller learns nothing about how
        // this deployment is configured. The log line is where the distinction
        // lives, for whoever is reading it.
        if (! config('payments.automatic_enabled')) {
            Log::warning('Gateway callback refused: automatic payments are disabled.', [
                'gateway' => $gateway,
                'transaction_id' => $transaction,
            ]);

            abort(404);
        }

        $tx = $gateways->handleCallback($gateway, $transaction, $request->all());

        return response()->json([
            'success' => $tx->status === 'paid',
            'status' => $tx->status,
            'reference' => $tx->reference,
        ]);
    }
}
