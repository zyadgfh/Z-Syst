<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GatewayTransaction;
use App\Models\Subscription;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;

class GatewayPaymentController extends Controller
{
    public function __construct(protected PaymentGatewayService $gateways)
    {
        $this->middleware('permission:settings-update')->only('checkoutSubscription');
    }

    /** Start an online payment for the tenant's subscription (Paymob/Fawry/InstaPay). */
    public function checkoutSubscription(Request $request)
    {
        $data = $request->validate([
            'gateway' => 'required|in:paymob,fawry,instapay',
            'subscription_id' => 'nullable|exists:subscriptions,id',
            'amount' => 'nullable|numeric|min:1',
        ]);

        $businessId = (int) $request->user()->business_id;
        $subscription = isset($data['subscription_id'])
            ? Subscription::where('business_id', $businessId)->findOrFail($data['subscription_id'])
            : Subscription::where('business_id', $businessId)->where('status', '!=', 'cancelled')->latest('id')->first();
        $amount = (float) ($data['amount'] ?? $subscription?->amount ?? 0);

        if ($amount <= 0) {
            return response()->json(['success' => false, 'message' => __('لا يوجد مبلغ مستحق')], 422);
        }

        $tx = $this->gateways->initiate($businessId, $data['gateway'], 'subscription', $subscription?->id, $amount);

        return response()->json([
            'success' => true,
            'data' => [
                'transaction_id' => $tx->id,
                'mode' => $tx->payload['mode'] ?? 'simulation',
                'redirect_url' => $tx->payload['redirect_url'] ?? null,
                'amount' => $tx->amount,
            ],
        ]);
    }

    public function index(Request $request)
    {
        return view('admin.gateways.transactions', [
            'transactions' => GatewayTransaction::forBusiness((int) $request->user()->business_id)->latest('id')->paginate(15),
        ]);
    }
}
