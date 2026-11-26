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

        // Automatic payments are switched off (config/payments.php). This is the
        // only route into PaymentGatewayService::initiate(), which refuses as
        // well — the answer here is for the caller's benefit, so a client can
        // distinguish "this method is off" (403) from "nothing is due" (422) or
        // a malformed request (422 from validation).
        if (! config('payments.automatic_enabled')) {
            return response()->json([
                'success' => false,
                'message' => __('الدفع الآلي معطّل حالياً — استخدم طلب الدفع اليدوي'),
            ], 403);
        }

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

        // Customer details for Fawry's charge body (phone falls back to a
        // placeholder inside FawryService when the account has none).
        $user = $request->user();
        $tx = $this->gateways->initiate($businessId, $data['gateway'], 'subscription', $subscription?->id, $amount, [
            'name' => $user->name ?? '',
            'email' => $user->email ?? '',
            'phone' => $user->phone ?? '',
            'profile_id' => (string) $user->id,
        ]);

        // initiate() can record a failed transaction when the upstream charge
        // itself fails (bad credentials, Fawry unreachable). It is failed on
        // purpose so it can never settle later — report the reason instead of
        // a redirect that would go nowhere.
        if ($tx->status === 'failed') {
            return response()->json([
                'success' => false,
                'message' => $tx->payload['error'] ?? __('تعذر إنشاء طلب الدفع'),
                'transaction_id' => $tx->id,
            ], 502);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'transaction_id' => $tx->id,
                'mode' => $tx->payload['mode'] ?? 'simulation',
                'redirect_url' => $tx->payload['redirect_url'] ?? null,
                // Fawry: the merchantRefNum the customer pays against at the
                // Fawry branch; also what the notification/status query binds to.
                'reference' => $tx->reference,
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
