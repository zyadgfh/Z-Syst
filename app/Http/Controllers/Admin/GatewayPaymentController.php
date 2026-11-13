<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GatewayTransaction;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
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
        // `amount` is deliberately NOT a validation rule any more.
        //
        // It used to be `nullable|numeric|min:1` and was then taken from the
        // request when present, which let any user holding settings-update pay
        // an arbitrary sum for their own subscription: send amount=1, complete
        // the (correctly signed) gateway callback for that 1 EGP transaction,
        // and handleCallback() flips the subscription to active because the
        // signature matches the amount that was recorded at init time. The
        // gateway HMAC proves the payer paid the transaction; only the server
        // can know what the transaction was supposed to cost.
        //
        // Clients that still post `amount` are unaffected — the field is simply
        // ignored, so nothing has to be released in lockstep with this change.
        $data = $request->validate([
            'gateway' => 'required|in:paymob,fawry,instapay',
            'subscription_id' => 'nullable|exists:subscriptions,id',
        ]);

        $businessId = (int) $request->user()->business_id;
        $subscription = isset($data['subscription_id'])
            ? Subscription::where('business_id', $businessId)->findOrFail($data['subscription_id'])
            : Subscription::where('business_id', $businessId)->where('status', '!=', 'cancelled')->latest('id')->first();

        // Price comes from the plan row, which the tenant cannot write.
        // Subscription has no plan() relation, so query the column directly.
        $amount = $subscription
            ? round((float) SubscriptionPlan::whereKey($subscription->plan_id)->value('price'), 2)
            : 0.0;

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
