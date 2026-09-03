<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubscriptionRequest;
use App\Http\Resources\SubscriptionResource;
use App\Models\Subscription;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SubscriptionController extends Controller
{
    protected SubscriptionService $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Subscription::query()
            ->with(['plan', 'business'])
            ->latest();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $subscriptions = $query->paginate($request->per_page ?? 15);

        return SubscriptionResource::collection($subscriptions);
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

    public function show(Subscription $subscription): JsonResponse
    {
        $subscription->load(['plan', 'business', 'invoices', 'usageRecords', 'logs']);

        return response()->json([
            'success' => true,
            'data' => new SubscriptionResource($subscription),
        ]);
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

    public function usage(Request $request): JsonResponse
    {
        $usage = $this->subscriptionService->getUsage(
            $request->user()->business_id,
            $request->metric_name,
            $request->period ?? 'month'
        );

        return response()->json([
            'success' => true,
            'data' => $usage,
        ]);
    }

    public function checkLimits(Request $request): JsonResponse
    {
        $withinLimits = $this->subscriptionService->checkLimits(
            $request->user()->business_id,
            $request->metric_name
        );

        return response()->json([
            'success' => true,
            'data' => [
                'within_limits' => $withinLimits,
                'metric_name' => $request->metric_name,
            ],
        ]);
    }
}
