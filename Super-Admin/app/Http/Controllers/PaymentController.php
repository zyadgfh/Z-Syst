<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use App\Models\Gateway;
use App\Models\Business;
use App\Helpers\HasUploader;
use Illuminate\Http\Request;
use App\Models\PlanSubscribe;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;
use Modules\AffiliateAddon\App\Models\Affiliate;
use Modules\AffiliateAddon\App\Models\AffiliateTransaction;

class PaymentController extends Controller
{
    use HasUploader;
    /**
     * Display a listing of the resource.
     */
    public function index($plan_id, $business_id)
    {
        $plan = Plan::findOrFail($plan_id);
        session()->put('business_id', $business_id);
        session()->put('platform', request('platform') ?? 'web');
        $business = Business::findOrFail($business_id);
        $plan_data = plan_data($business_id);

        $has_free_subscriptions = Plan::where('subscriptionPrice', '<=', 0)->orWhere('offerPrice', '<=', 0)->first();

        if ($plan->subscriptionPrice <= 0 && $has_free_subscriptions) {
            return redirect(route('order.status', ['status' => 'failed']))->with('message', __('Sorry, you cannot subscribe to a free plan again.'));
        }

        if (
            session('platform') == 'web' && ($plan_data ?? false) && ($plan_data->plan_id == $plan->id && $business->will_expire > now()->addDays(7)) ||
            ($business->will_expire >= now()->addDays($plan->duration))
        ) {
            return redirect(route('order.status', ['status' => 'failed']))->with('message', __('You have already subscribed to this plan. Please try again after - ' . formatted_date($business->will_expire)));
        }

         $gateways = Gateway::with('currency:id,code,rate,symbol,position')->where('status', 1)->get();

        return view('payments.index', compact('gateways', 'plan'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function payment(Request $request, $plan_id, $gateway_id)
    {
        $request->validate([
            'phone' => 'max:15|min:5',
        ]);

        $plan = Plan::findOrFail($plan_id);
        $gateway = Gateway::findOrFail($gateway_id);
        $business = Business::findOrFail(session("business_id"));

        if ($gateway->is_manual) {
            $request->validate([
                'attachment' => 'required|mimes:jpg,jpeg,png,pdf|file',
            ]);

            DB::beginTransaction();
            try {
                $attachment = $request->attachment ? $this->upload($request, 'attachment') : NULL;

                $subscribe = PlanSubscribe::create([
                    'plan_id' => $plan->id,
                    'duration' => $plan->duration,
                    'business_id' => $business->id,
                    'price' => $plan->subscriptionPrice,
                    'gateway_id' => $gateway_id,
                    'payment_status' => 'unpaid',
                    'allow_multibranch' => $plan->allow_multibranch,
                    'addon_domain_limit' => $plan->addon_domain_limit ?? 0,
                    'subdomain_limit' => $plan->subdomain_limit ?? 0,
                    'notes' => [
                        'manual_data' => $request->manual_data,
                        'attachment' => $attachment
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

        $amount = $plan->offerPrice ?? $plan->subscriptionPrice;

        if ($gateway->namespace == 'App\Library\SslCommerz') {
            Session::put('fund_callback.success_url', '/ssl-commerz//payment/success');
            Session::put('fund_callback.cancel_url', '/ssl-commerz//payment/failed');
        } else {
            Session::put('fund_callback.success_url', '/payment/success');
            Session::put('fund_callback.cancel_url', '/payment/failed');
        }

        $currency = $gateway->currency;
        $user = User::where('business_id', $business->id)->first();

        $payment_data['country'] = $request->country ?? 'ZMW';
        $payment_data['currency'] = $currency->code ?? 'USD';
        $payment_data['email'] = $user->email;
        $payment_data['name'] = $business->companyName;
        $payment_data['phone'] = $request->phone ?? $business->phoneNumber;
        $payment_data['billName'] = __('Make plan purchase payment');
        $payment_data['amount'] = $amount;
        $payment_data['mode'] = $gateway->mode;
        $payment_data['charge'] = $gateway->charge ?? 0;
        $payment_data['pay_amount'] = round(convert_money($amount, $currency) + $gateway->charge);
        $payment_data['gateway_id'] = $gateway->id;
        $payment_data['payment_type'] = 'plan_payment';
        $payment_data['request_from'] = 'merchant';
        $payment_data['plan_id'] = $plan_id;
        $payment_data['business_id'] = $business->id;
        $payment_data['platform'] = session('platform');

        foreach ($gateway->data ?? [] as $key => $info) {
            $payment_data[$key] = $info;
        }

        session()->put('gateway_id', $gateway->id);
        session()->put('plan', $plan);

        $redirect = $gateway->namespace::make_payment($payment_data);
        return $redirect;
    }

    public function success()
    {
        DB::beginTransaction();
        try {

            $plan = session('plan');
            $gateway_id = session('gateway_id');

            if (!$plan && !session('plan_id')) {
                return redirect(route('order.status', ['status' => 'failed']))->with('error', __('Transaction failed, Please try again.'));
            }

            if (session('plan_id') && !$plan) {
                $plan = Plan::findOrFail(session('plan_id'));
            }

            $business = Business::findOrFail(session("business_id"));

            $subscribe = PlanSubscribe::create([
                'plan_id' => $plan->id,
                'payment_status' => 'paid',
                'gateway_id' => $gateway_id,
                'duration' => $plan->duration,
                'business_id' => $business->id,
                'price' => $plan->subscriptionPrice,
                'allow_multibranch' => $plan->allow_multibranch,
                'addon_domain_limit' => $plan->addon_domain_limit ?? 0,
                'subdomain_limit' => $plan->subdomain_limit ?? 0,
            ]);

            $business->update([
                'subscriptionDate' => now(),
                'plan_subscribe_id' => $subscribe->id,
                'will_expire' => now()->addDays($plan->duration),
            ]);

            if (moduleCheck('AffiliateAddon') && $business->affiliator_id) {
                $affiliateUser = User::find($business->affiliator_id);

                if ($affiliateUser && $plan->affiliate_commission > 0) {
                    $commission = ($plan->subscriptionPrice * $plan->affiliate_commission) / 100;

                    Affiliate::where('user_id', $affiliateUser->id)->increment('balance', $commission);

                    AffiliateTransaction::create([
                        'user_id'        => $affiliateUser->id,
                        'business_id'    => $business->id,
                        'trx'            => strtoupper(str()->random(10)),
                        'type'           => 'credit',
                        'amount'         => $commission,
                        'title'          => 'Commission from Order #' . $subscribe->id,
                        'reference_id'   => $subscribe->id,
                        'reference_type' => 'PlanSubscribe',
                        'note'           => 'User purchased via your referral link',
                    ]);
                }
            }

            session()->forget('plan');
            session()->forget('plan_id');
            session()->forget('gateway_id');
            session()->forget('business_id');
            Cache::forget('plan-data-' . $business->id);

            DB::commit();
            return redirect(route('order.status', ['status' => 'success']))->with('message', __('New subscription order successfully.'));
        } catch (\Exception $e) {
            DB::rollback();
            return redirect(route('order.status', ['status' => 'failed']))->with('message', __('Something went wrong!'));
        }
    }

    public function failed()
    {
        if (session('platform') == 'web') {
            $payment_msg = session('payment_msg');
            session()->forget('payment_msg');
            return redirect(route('business.subscriptions.index'))->with('error', $payment_msg ?? __('Transaction failed, Please try again.'));
        }

        return redirect(route('order.status', ['status' => 'failed']))->with('message', __('Something went wrong!'));
    }

    public function sslCommerzSuccess(Request $request)
    {
        DB::beginTransaction();
        try {

            if (!$request->value_a || !$request->value_b || !$request->value_c) {
                return redirect(route('order.status', ['status' => 'failed']))->with('error', __('Transaction failed, Please try again.'));
            }

            $plan = session('plan');
            $gateway_id = session('gateway_id');
            if (!$plan) {
                return redirect(route('order.status', ['status' => 'failed']))->with('error', __('Transaction failed, Please try again.'));
            }

            $business = Business::findOrFail(session("business_id"));

            $subscribe = PlanSubscribe::create([
                'plan_id' => $plan->id,
                'payment_status' => 'paid',
                'gateway_id' => $gateway_id,
                'duration' => $plan->duration,
                'business_id' => $business->id,
                'price' => $plan->subscriptionPrice,
                'allow_multibranch' => $plan->allow_multibranch,
                'addon_domain_limit' => $plan->addon_domain_limit ?? 0,
                'subdomain_limit' => $plan->subdomain_limit ?? 0,
            ]);

            $business->update([
                'subscriptionDate' => now(),
                'plan_subscribe_id' => $subscribe->id,
                'will_expire' => now()->addDays($plan->duration),
            ]);

            session()->forget('gateway_id');
            session()->forget('plan');
            Cache::forget('plan-data-' . $business->id);

            DB::commit();
            return redirect(route('order.status', ['status' => 'success']))->with('message', __('New subscription order successfully.'));
        } catch (\Exception $e) {
            DB::rollback();
            return redirect(route('order.status', ['status' => 'failed']))->with('message', __('Something went wrong!'));
        }
    }

    public function sslCommerzFailed()
    {
        return redirect(route('order.status', ['status' => 'failed']))->with('error', __('Transaction failed, Please try again.'));
    }

    public function orderStatus()
    {
        if (session('platform') == 'web') {
            if (moduleCheck('Business')) {
                if (session('user_id')) {
                    session()->forget('user_id');
                    return redirect(route('business.dashboard.index'))->with('message', request('message') ?? __('Subscription order completed.'));
                } else {
                    return redirect(route('business.subscriptions.index'))->with('message', request('message') ?? __('New subscription order successfully.'));
                }
            } else {
                return redirect(route('home', ['success_modal' => 1]))->with('message', request('message') ?? __('Subscription order successfully, Please download the apk for manage your business.'));
            }
        }
        session()->forget('platform');
        return request('status');
    }
}
