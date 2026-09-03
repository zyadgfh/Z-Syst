<?php

namespace App\Http\Controllers;

use App\Helpers\HasUploader;
use App\Models\Business;
use App\Models\CompanyPaymentGateway;
use App\Models\Gateway;
use App\Models\PaymentTransaction;
use App\Models\Plan;
use App\Models\PlanSubscribe;
use App\Models\User;
use App\Services\PaymentGatewayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    use HasUploader;

    protected PaymentGatewayService $paymentGatewayService;

    public function __construct(PaymentGatewayService $paymentGatewayService)
    {
        $this->paymentGatewayService = $paymentGatewayService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index($id, $business_id)
    {
        $plan = Plan::findOrFail($id);
        session()->put('business_id', $business_id);

        // Get new Egyptian payment gateways
        $companyGateways = $this->paymentGatewayService->getAvailableGateways($business_id);

        // Also show legacy manual gateways for backward compatibility
        $legacyGateways = Gateway::with('currency:id,code,rate,symbol,position')
            ->where('status', 1)
            ->where('is_manual', 1)
            ->get();

        return view('payments.index', compact('companyGateways', 'legacyGateways', 'plan'));
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
        $business = Business::findOrFail(session('business_id'));
        $user = User::where('business_id', $business->id)->firstOrFail();

        // Check if this is a new Egyptian payment gateway or legacy gateway
        $companyGateway = CompanyPaymentGateway::find($gateway_id);

        if ($companyGateway) {
            return $this->processEgyptianGatewayPayment($request, $plan, $business, $user, $companyGateway);
        } else {
            return $this->processLegacyManualPayment($request, $plan, $business, $user, $gateway_id);
        }
    }

    /**
     * Process payment through Egyptian payment gateways.
     */
    protected function processEgyptianGatewayPayment(Request $request, $plan, $business, $user, CompanyPaymentGateway $gateway)
    {
        try {
            $amount = $plan->offerPrice ?? $plan->subscriptionPrice;

            $paymentData = [
                'amount' => $amount,
                'currency' => 'EGP',
                'customer_phone' => $request->phone ?? $business->phoneNumber,
                'customer_email' => $user->email,
                'transaction_type' => PaymentTransaction::TYPE_SUBSCRIPTION,
                'metadata' => [
                    'plan_id' => $plan->id,
                    'business_id' => $business->id,
                    'plan_name' => $plan->subscriptionName,
                ],
                'processed_by' => auth()->id(),
            ];

            // Add card data if bank card payment
            if ($gateway->gateway_type === CompanyPaymentGateway::GATEWAY_BANK_CARD) {
                $paymentData['card_data'] = [
                    'card_number' => $request->card_number,
                    'card_holder' => $request->card_holder,
                    'expiry_month' => $request->expiry_month,
                    'expiry_year' => $request->expiry_year,
                    'cvv' => $request->cvv,
                ];
            }

            // Add cash-specific data
            if ($gateway->gateway_type === CompanyPaymentGateway::GATEWAY_CASH) {
                $paymentData['received_amount'] = $request->received_amount;
                $paymentData['payment_notes'] = $request->payment_notes;
                $paymentData['verified_by'] = $request->verified_by;
            }

            $transaction = $this->paymentGatewayService->processPayment($gateway->id, $paymentData);

            if ($transaction->status === PaymentTransaction::STATUS_COMPLETED) {
                return $this->completeSubscription($plan, $business, $gateway->id, $transaction);
            } elseif ($transaction->status === PaymentTransaction::STATUS_PENDING) {
                return redirect(route('order.status', ['status' => 'pending']))
                    ->with('message', __('Payment is being processed. Transaction ID: ').$transaction->internal_reference);
            } else {
                return redirect(route('order.status', ['status' => 'failed']))
                    ->with('error', __('Payment failed: ').$transaction->failure_reason);
            }

        } catch (\Exception $e) {
            return redirect(route('order.status', ['status' => 'failed']))
                ->with('error', __('Payment processing error: ').$e->getMessage());
        }
    }

    /**
     * Process legacy manual payment (backward compatibility).
     */
    protected function processLegacyManualPayment(Request $request, $plan, $business, $user, $gateway_id)
    {
        $gateway = Gateway::findOrFail($gateway_id);

        if (! $gateway->is_manual) {
            return redirect(route('order.status', ['status' => 'failed']))
                ->with('error', __('Payment gateways have been removed. Use the new Egyptian payment gateways or manual payment.'));
        }

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

            return redirect(route('order.status', ['status' => 'success']))
                ->with('message', __('New subscription purchased requested.'));

        } catch (\Exception $e) {
            DB::rollback();

            return redirect(route('order.status', ['status' => 'failed']))
                ->with('message', __('Something went wrong!'));
        }
    }

    /**
     * Complete subscription after successful payment.
     */
    protected function completeSubscription($plan, $business, $gatewayId, $transaction)
    {
        DB::beginTransaction();
        try {
            $has_free_subscriptions = Plan::where('subscriptionPrice', '<=', 0)->orWhere('offerPrice', '<=', 0)->first();

            if ($plan->subscriptionPrice <= 0 && $has_free_subscriptions) {
                return response()->json([
                    'status' => 406,
                    'message' => __('Sorry, you cannot subscribe to a free plan again.'),
                ], 406);
            }

            $subscribe = PlanSubscribe::create([
                'plan_id' => $plan->id,
                'duration' => $plan->duration,
                'business_id' => $business->id,
                'price' => $plan->subscriptionPrice,
                'gateway_id' => $gatewayId,
                'payment_status' => 'paid',
                'notes' => [
                    'transaction_id' => $transaction->id,
                    'reference_id' => $transaction->reference_id,
                ],
            ]);

            $business->update([
                'subscriptionDate' => now(),
                'plan_subscribe_id' => $subscribe->id,
                'will_expire' => now()->addDays($plan->duration),
            ]);

            session()->forget('business_id');

            DB::commit();

            return redirect(route('order.status', ['status' => 'success']))
                ->with('message', __('New subscription order successfully.'));

        } catch (\Exception $e) {
            DB::rollback();

            return redirect(route('order.status', ['status' => 'failed']))
                ->with('message', __('Something went wrong!'));
        }
    }

    /**
     * Display order status.
     */
    public function orderStatus()
    {
        return request('status');
    }

    /**
     * Payment callback for gateways that redirect back.
     */
    public function paymentCallback(Request $request)
    {
        $referenceId = $request->input('reference_id');
        $gatewayType = $request->input('gateway_type');

        try {
            $transaction = PaymentTransaction::where('reference_id', $referenceId)
                ->where('gateway_type', $gatewayType)
                ->firstOrFail();

            if ($transaction->status === PaymentTransaction::STATUS_PENDING) {
                // Verify payment status with gateway
                $verification = $this->paymentGatewayService->verifyPayment($transaction->gateway_id, $referenceId);

                if ($verification['success']) {
                    $transaction->markAsCompleted($referenceId, $verification['data']);

                    // Complete subscription if it's a subscription payment
                    if ($transaction->transaction_type === PaymentTransaction::TYPE_SUBSCRIPTION) {
                        $plan = Plan::find($transaction->metadata['plan_id'] ?? null);
                        $business = Business::find($transaction->metadata['business_id'] ?? null);

                        if ($plan && $business) {
                            return $this->completeSubscription($plan, $business, $transaction->gateway_id, $transaction);
                        }
                    }

                    return redirect(route('order.status', ['status' => 'success']))
                        ->with('message', __('Payment completed successfully.'));
                } else {
                    $transaction->markAsFailed($verification['error'] ?? 'Payment verification failed');

                    return redirect(route('order.status', ['status' => 'failed']))
                        ->with('error', __('Payment verification failed.'));
                }
            }

            return redirect(route('order.status', ['status' => $transaction->status]));

        } catch (\Exception $e) {
            return redirect(route('order.status', ['status' => 'failed']))
                ->with('error', __('Payment callback error: ').$e->getMessage());
        }
    }
}
