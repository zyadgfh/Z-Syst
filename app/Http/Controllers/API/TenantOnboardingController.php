<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StartOnboardingRequest;
use App\Http\Requests\StoreOnboardingTemplateRequest;
use App\Http\Requests\UpdateOnboardingTemplateRequest;
use App\Models\Business;
use App\Models\OnboardingTemplate;
use App\Models\TenantChecklistProgress;
use App\Models\TenantOnboardingInstance;
use App\Services\TenantOnboardingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantOnboardingController extends Controller
{
    protected TenantOnboardingService $onboardingService;

    public function __construct(TenantOnboardingService $onboardingService)
    {
        $this->onboardingService = $onboardingService;
    }

    /**
     * Get onboarding progress for current tenant.
     */
    public function progress(Request $request)
    {
        $businessId = Auth::user()->business_id;
        $progress = $this->onboardingService->getOnboardingProgress($businessId);

        return response()->json([
            'message' => 'Onboarding progress fetched successfully',
            'data' => $progress,
        ]);
    }

    /**
     * Get checklist progress for current tenant.
     */
    public function checklist(Request $request)
    {
        $businessId = Auth::user()->business_id;
        $checklist = $this->onboardingService->getChecklistProgress($businessId);

        return response()->json([
            'message' => 'Checklist progress fetched successfully',
            'data' => $checklist,
        ]);
    }

    /**
     * Complete a checklist item.
     */
    public function completeChecklistItem(Request $request, $checklistItemId)
    {
        $businessId = Auth::user()->business_id;
        $result = $this->onboardingService->completeChecklistItem($businessId, $checklistItemId, Auth::id());

        return response()->json([
            'message' => $result ? 'Checklist item completed' : 'Checklist item not found',
            'success' => $result,
        ], $result ? 200 : 422);
    }

    /**
     * Get onboarding analytics for current tenant.
     */
    public function analytics(Request $request)
    {
        $businessId = Auth::user()->business_id;
        $analytics = $this->onboardingService->getOnboardingAnalytics($businessId);

        return response()->json([
            'message' => 'Onboarding analytics fetched successfully',
            'data' => $analytics,
        ]);
    }

    /**
     * Start onboarding for current tenant.
     */
    public function start(StartOnboardingRequest $request)
    {
        $business = Business::findOrFail(Auth::user()->business_id);
        $instance = $this->onboardingService->startOnboarding($business, $request->input('template_id'), Auth::id());

        return response()->json([
            'message' => 'Onboarding started successfully',
            'data' => $instance->load('template'),
        ], 201);
    }

    /**
     * Process next onboarding step.
     */
    public function nextStep($instanceId)
    {
        $instance = TenantOnboardingInstance::where('business_id', Auth::user()->business_id)
            ->findOrFail($instanceId);

        $this->authorize('processStep', $instance);

        $result = $this->onboardingService->processNextStep($instance);

        return response()->json([
            'message' => $result ? 'Step processed successfully' : 'Step failed',
            'success' => $result,
            'data' => $instance->fresh(['stepLogs']),
        ]);
    }

    /**
     * Display a listing of onboarding templates.
     */
    public function templates(Request $request)
    {
        $templates = OnboardingTemplate::active()
            ->when($request->filled('search'), function ($query) use ($request) {
                $term = '%' . $request->input('search') . '%';
                $query->where('name', 'like', $term)
                    ->orWhere('code', 'like', $term);
            })
            ->latest()
            ->paginate($request->input('per_page', 15));

        return response()->json([
            'message' => 'Onboarding templates fetched successfully',
            'data' => $templates,
        ]);
    }

    /**
     * Store a new onboarding template.
     */
    public function storeTemplate(StoreOnboardingTemplateRequest $request)
    {
        $template = $this->onboardingService->createTemplate($request->validated());

        return response()->json([
            'message' => 'Onboarding template created successfully',
            'data' => $template,
        ], 201);
    }

    /**
     * Display the specified onboarding template.
     */
    public function showTemplate(OnboardingTemplate $template)
    {
        $this->authorize('viewTemplate', $template);

        $template->load(['checklistItems', 'welcomeEmails', 'automationRules']);

        return response()->json([
            'message' => 'Onboarding template fetched successfully',
            'data' => $template,
        ]);
    }

    /**
     * Update the specified onboarding template.
     */
    public function updateTemplate(UpdateOnboardingTemplateRequest $request, OnboardingTemplate $template)
    {
        $this->authorize('updateTemplate', $template);

        $template->update($request->validated());

        return response()->json([
            'message' => 'Onboarding template updated successfully',
            'data' => $template->fresh(),
        ]);
    }

    /**
     * Remove the specified onboarding template.
     */
    public function destroyTemplate(OnboardingTemplate $template)
    {
        $this->authorize('deleteTemplate', $template);

        $template->delete();

        return response()->json([
            'message' => 'Onboarding template deleted successfully',
        ]);
    }

    /**
     * Get default onboarding template.
     */
    public function defaultTemplate()
    {
        $template = OnboardingTemplate::default()->first();

        if (!$template) {
            return response()->json([
                'message' => 'No default template found',
            ], 404);
        }

        return response()->json([
            'message' => 'Default template fetched successfully',
            'data' => $template->load(['checklistItems', 'welcomeEmails']),
        ]);
    }
}