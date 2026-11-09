<?php

namespace App\Http\Controllers;

use App\Helpers\HasUploader;
use App\Models\Business;
use App\Models\Gateway;
use App\Models\Plan;
use App\Models\PlanSubscribe;
use App\Models\TenantPaymentSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class PaymentController extends Controller
{
    use HasUploader;

    /**
     * Display a listing of the resource.
     */
    public function index($id, $business_id)
    {
        // M-03: the route is public, so authorization is enforced here —
        // only the owning business user (or a superadmin) may open checkout,
        // and the session business id is written from the verified identity,
        // never from the URL (session poisoning / cross-tenant checkout).
        $actor = auth()->user();
        if (! $actor) {
            abort(401, 'Authentication required.');
        }

        $isSuper = method_exists($actor, 'hasRole') && $actor->hasRole('superadmin');
        if ($actor->business_id != $business_id && ! $isSuper) {
            abort(403, 'This checkout does not belong to your business.');
        }

        $plan = Plan::findOrFail($id);
        session()->put('business_id', $isSuper ? (int) $business_id : $actor->business_id);
        $gateways = Gateway::with('currency:id,code,rate,symbol,position')->where('status', 1)->get();

        return view('payments.index', compact('gateways', 'plan'));
    }

    /**
     * Store a newly created resource in storage.
     * Supports manual payments and Egyptian payment gateways with multi-tenant support
     */
    public function payment(Request $request, $plan_id, $gateway_id)
    {
        $request->validate([
            'phone' => 'max:15|min:5',
        ]);

        // M-03: same ownership gate before any subscription write — the
        // session business id is only trusted for the verified owner
        // (or superadmin); provider webhooks/callbacks stay public.
        $actor = auth()->user();
        if (! $actor) {
            abort(401, 'Authentication required.');
        }

        $plan = Plan::findOrFail($plan_id);
        $gateway = Gateway::findOrFail($gateway_id);
        $business = Business::findOrFail(session('business_id'));

        $isSuper = method_exists($actor, 'hasRole') && $actor->hasRole('superadmin');
        if ($actor->business_id != $business->id && ! $isSuper) {
            abort(403, 'This checkout does not belong to your business.');
        }

        $user = User::where('business_id', $business->id)->firstOrFail();

        // Get tenant-specific payment settings
        $tenantSettings = TenantPaymentSetting::getSettingsForTenant($business->id, $gateway_id);

        $amount = $plan->offerPrice ?? $plan->subscriptionPrice;

        // Handle manual payments
        if ($gateway->is_manual) {
            $request->validate([
                'attachment' => 'required|max:2048|file',
            ]);

            DB::beginTransaction();
            try {

                $has_free_subscriptions = Plan::where('subscriptionPrice', '<=', 0)->orWhere('offerPrice', '<=', 0)->first();

                if ($plan->subscriptionPrice <= 0 && $has_free_subscriptions) {
                    return response()->json([
                        'status' => 406,
                        'message' => __('Sorry, you cannot subscribe to a free plan again.'),
                    ], 406);
                }

                $attachment = $request->attachment ? $this->upload($request, 'attachment') : null;

                $subscribe = PlanSubscribe::create([
                    'plan_id' => $plan->id,
                    'duration' => $plan->duration,
                    'business_id' => $business->id,
                    'price' => $plan->subscriptionPrice,
                    'gateway_id' => $gateway_id,
                    'payment_status' => 'unpaid',
                    'notes' => [
                        'manual_data' => $request->manual_data,
                        'attachment' => $attachment,
                    ],
                ]);

                sendNotification($subscribe->id, route('admin.subscription-reports.index', ['id' => $subscribe->id]), __('New subscription purchased requested.'));

                DB::commit();

                return redirect(route('order.status', ['status' => 'success']))->with('message', __('New subscription purchased requested.'));

            } catch (\Exception $e) {
                DB::rollback();

                return redirect(route('order.status', ['status' => 'failed']))->with('message', __('Something went wrong!'));
            }
        }

        // Handle Egyptian payment gateways
        if ($gateway->namespace) {
            $payment_data['currency'] = $gateway->currency->code ?? 'EGP';
            $payment_data['email'] = $user->email;
            $payment_data['name'] = $business->name;
            $payment_data['phone'] = $business->phoneNumber;
            $payment_data['billName'] = __('Make plan purchase payment');
            $payment_data['amount'] = $amount;
            $payment_data['mode'] = $gateway->mode;
            $payment_data['charge'] = $gateway->charge ?? 0;
            $payment_data['pay_amount'] = round(convert_money($amount, $gateway->currency) + $gateway->charge);
            $payment_data['gateway_id'] = $gateway->id;
            $payment_data['payment_type'] = 'plan_payment';
            $payment_data['request_from'] = 'merchant';
            $payment_data['business_id'] = $business->id;
            $payment_data['plan_id'] = $plan->id;

            // Add tenant-specific settings
            if ($tenantSettings) {
                $payment_data['merchant_phone'] = $tenantSettings->merchant_phone;
                $payment_data['merchant_name'] = $tenantSettings->merchant_name;
                $payment_data['merchant_code'] = $tenantSettings->merchant_code;
                $payment_data['merchant_key'] = $tenantSettings->merchant_key;
                $payment_data['merchant_instapay_id'] = $tenantSettings->merchant_instapay_id;
                $payment_data['bank_name'] = $tenantSettings->bank_name;
                $payment_data['account_number'] = $tenantSettings->account_number;
                $payment_data['branch_name'] = $tenantSettings->branch_name;
            }

            foreach ($gateway->data ?? [] as $key => $info) {
                $payment_data[$key] = $info;
            }

            session()->put('gateway_id', $gateway->id);
            session()->put('plan', $plan);

            $redirect = $gateway->namespace::make_payment($payment_data);

            return $redirect;
        }

        return redirect(route('order.status', ['status' => 'failed']))->with('error', __('Payment method not available.'));
    }

    /**
     * Display order status (kept for manual payment flow) — sanitized.
     */
    public function orderStatus()
    {
        $allowed = ['success', 'failed', 'pending'];
        $status = (string) request('status', 'pending');
        if (! in_array($status, $allowed, true)) {
            $status = 'pending';
        }

        return response()->view('payments.status', ['status' => e($status)]);
    }

    /**
     * Handle successful payment — now requires verified callback, never session-only.
     */
    public function success(Request $request)
    {
        // Session-only will_expire extension removed (was exploitable via GET).
        // Real activation is performed only by GatewayWebhookController after
        // HMAC verification. This endpoint now just renders a status page.
        $status = $request->session()->get('payment_verified_status', 'pending');

        return redirect(route('order.status', ['status' => $status === 'paid' ? 'success' : 'pending']))
            ->with('message', __('Payment is being verified. You will be notified once confirmed.'));
    }

    /**
     * Handle failed payment
     */
    public function failed()
    {
        return redirect(route('order.status', ['status' => 'failed']))->with('error', __('Transaction failed, Please try again.'));
    }
}
