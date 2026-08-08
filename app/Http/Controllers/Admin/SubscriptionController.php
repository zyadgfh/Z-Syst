<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubscriptionRequest;
use App\Http\Resources\SubscriptionResource;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SubscriptionController extends Controller
{
    protected SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    public function index(Request $request)
    {
        $query = Subscription::query()
            ->with(['plan', 'business'])
            ->latest();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $subscriptions = $query->paginate($request->per_page ?? 15);

        if ($request->wantsJson()) {
            return SubscriptionResource::collection($subscriptions);
        }

        return view('admin.subscriptions.index', compact('subscriptions'));
    }

    public function create(Request $request)
    {
        $plans = SubscriptionPlan::active()->get();
        $businesses = \App\Models\Business::all();
        return view('admin.subscriptions.create', compact('plans', 'businesses'));
    }

    public function store(SubscriptionRequest $request): JsonResponse
    {
        $subscription = $this->subscriptionService->createSubscription(
            $request->business_id,
            $request->plan_id,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Subscription created successfully',
            'data' => new SubscriptionResource($subscription),
        ], 201);
    }

    public function show(Request $request, Subscription $subscription)
    {
        $subscription->load(['plan', 'business', 'invoices', 'usageRecords', 'logs']);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => new SubscriptionResource($subscription),
            ]);
        }

        return view('admin.subscriptions.show', compact('subscription'));
    }

    public function upgrade(Request $request, Subscription $subscription): JsonResponse
    {
        $subscription = $this->subscriptionService->upgradeSubscription(
            $subscription,
            $request->plan_id,
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Subscription upgraded successfully',
            'data' => new SubscriptionResource($subscription),
        ]);
    }

    public function downgrade(Request $request, Subscription $subscription): JsonResponse
    {
        $subscription = $this->subscriptionService->downgradeSubscription(
            $subscription,
            $request->plan_id,
            $request->user()->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Subscription downgraded successfully',
            'data' => new SubscriptionResource($subscription),
        ]);
    }

    public function cancel(Request $request, Subscription $subscription): JsonResponse
    {
        $subscription = $this->subscriptionService->cancelSubscription(
            $subscription,
            $request->user()->id,
            $request->reason ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Subscription cancelled successfully',
            'data' => new SubscriptionResource($subscription),
        ]);
    }

    public function renew(Request $request, Subscription $subscription): JsonResponse
    {
        $subscription = $this->subscriptionService->renewSubscription($subscription);

        return response()->json([
            'success' => true,
            'message' => 'Subscription renewed successfully',
            'data' => new SubscriptionResource($subscription),
        ]);
    }

    public function generateInvoice(Request $request, Subscription $subscription): JsonResponse
    {
        $invoice = $this->subscriptionService->generateInvoice($subscription);

        return response()->json([
            'success' => true,
            'message' => 'Invoice generated successfully',
            'data' => $invoice,
        ]);
    }

    public function expiring(Request $request): JsonResponse
    {
        $subscriptions = $this->subscriptionService->getExpiringSubscriptions($request->days ?? 7);

        return response()->json([
            'success' => true,
            'data' => SubscriptionResource::collection($subscriptions),
        ]);
    }

    public function trialEnding(Request $request): JsonResponse
    {
        $subscriptions = $this->subscriptionService->getTrialEndingSubscriptions($request->days ?? 3);

        return response()->json([
            'success' => true,
            'data' => SubscriptionResource::collection($subscriptions),
        ]);
    }
}
