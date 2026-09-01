<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCampaignRequest;
use App\Http\Requests\UpdateCampaignRequest;
use App\Models\Campaign;
use App\Models\Party;
use App\Services\MarketingAutomationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MarketingAutomationController extends Controller
{
    protected MarketingAutomationService $marketingService;

    public function __construct(MarketingAutomationService $marketingService)
    {
        $this->marketingService = $marketingService;
    }

    /**
     * Display a listing of campaigns.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Campaign::class);

        $data = Campaign::select('id', 'business_id', 'name', 'type', 'subject', 'target_segment', 'scheduled_at', 'status')
            ->where('business_id', Auth::user()->business_id)
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%' . $request->input('search') . '%';
                $query->where('name', 'like', $term)
                    ->orWhere('subject', 'like', $term);
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->input('status'));
            })
            ->when($request->filled('type'), function ($query) use ($request) {
                $query->where('type', $request->input('type'));
            })
            ->latest()
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $data,
        ]);
    }

    /**
     * Store a newly created campaign.
     */
    public function store(StoreCampaignRequest $request)
    {
        $this->authorize('create', Campaign::class);

        $data = $request->validated();
        $data['business_id'] = Auth::user()->business_id;

        $campaign = $this->marketingService->createCampaign($data, $data['business_id']);

        return response()->json([
            'message' => __('Campaign created successfully.'),
            'data' => $campaign,
        ], 201);
    }

    /**
     * Display the specified campaign.
     */
    public function show(Campaign $campaign)
    {
        $this->authorize('view', $campaign);

        $campaign->load('recipients.party:id,name,email,phone', 'metrics');

        return response()->json([
            'message' => __('Data fetched successfully.'),
            'data' => $campaign,
        ]);
    }

    /**
     * Update the specified campaign.
     */
    public function update(UpdateCampaignRequest $request, Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        $data = $request->validated();
        $campaign->update($data);

        return response()->json([
            'message' => __('Campaign updated successfully.'),
            'data' => $campaign->fresh(),
        ]);
    }

    /**
     * Remove the specified campaign.
     */
    public function destroy(Campaign $campaign)
    {
        $this->authorize('delete', $campaign);

        $campaign->recipients()->delete();
        $campaign->metrics()->delete();
        $campaign->delete();

        return response()->json([
            'message' => __('Campaign deleted successfully.'),
        ]);
    }

    /**
     * Send campaign to recipients.
     */
    public function send(Campaign $campaign)
    {
        $this->authorize('send', $campaign);

        $result = $this->marketingService->sendCampaign($campaign);

        return response()->json([
            'message' => $result['success'] ? 'Campaign sent successfully' : 'Campaign send failed',
            'data' => $result,
        ], $result['success'] ? 200 : 422);
    }

    /**
     * Get campaign analytics.
     */
    public function analytics(Campaign $campaign)
    {
        $this->authorize('view', $campaign);

        $analytics = $this->marketingService->getCampaignAnalytics($campaign);

        return response()->json([
            'message' => 'Campaign analytics fetched successfully',
            'data' => $analytics,
        ]);
    }

    /**
     * Get customer segments.
     */
    public function segments(Request $request)
    {
        $this->authorize('viewAny', Campaign::class);

        $segments = $this->marketingService->getCustomerSegments(Auth::user()->business_id);

        return response()->json([
            'message' => 'Customer segments fetched successfully',
            'data' => $segments,
        ]);
    }

    /**
     * Schedule campaign.
     */
    public function schedule(Request $request, Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        $request->validate([
            'scheduled_at' => 'required|date|after:now',
        ]);

        $campaign->update([
            'scheduled_at' => $request->input('scheduled_at'),
            'status' => 'scheduled',
        ]);

        return response()->json([
            'message' => 'Campaign scheduled successfully',
            'data' => $campaign->fresh(),
        ]);
    }

    /**
     * Cancel scheduled campaign.
     */
    public function cancel(Campaign $campaign)
    {
        $this->authorize('update', $campaign);

        if (! in_array($campaign->status, ['draft', 'scheduled'])) {
            return response()->json([
                'message' => 'Cannot cancel campaign with status: ' . $campaign->status,
            ], 422);
        }

        $campaign->update([
            'status' => 'cancelled',
        ]);

        return response()->json([
            'message' => 'Campaign cancelled successfully',
            'data' => $campaign->fresh(),
        ]);
    }
}