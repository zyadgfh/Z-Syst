<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DoctorActivityResource;
use App\Http\Resources\DoctorAttentionAlertResource;
use App\Http\Resources\DoctorAttentionScoreResource;
use App\Models\DoctorAttentionSettings;
use App\Services\DoctorAttentionService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DoctorAttentionController extends Controller
{
    protected DoctorAttentionService $attentionService;

    public function __construct(DoctorAttentionService $attentionService)
    {
        $this->attentionService = $attentionService;
    }

    /**
     * Display doctor attention dashboard.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $statistics = $this->attentionService->getStatistics($request->user()->business_id);
        $needingAttention = $this->attentionService->getDoctorsNeedingAttention($request->user()->business_id);
        $critical = $this->attentionService->getCriticalDoctors($request->user()->business_id);
        $unreadAlerts = $this->attentionService->getUnreadAlerts($request->user()->id, $request->user()->business_id);

        return response()->json([
            'success' => true,
            'data' => [
                'statistics' => $statistics,
                'needing_attention' => DoctorAttentionScoreResource::collection($needingAttention),
                'critical' => DoctorAttentionScoreResource::collection($critical),
                'unread_alerts' => DoctorAttentionAlertResource::collection($unreadAlerts),
            ],
        ]);
    }

    /**
     * Display doctors needing attention.
     */
    public function needingAttention(Request $request): AnonymousResourceCollection
    {
        $doctors = $this->attentionService->getDoctorsNeedingAttention($request->user()->business_id);

        return DoctorAttentionScoreResource::collection($doctors);
    }

    /**
     * Display critical doctors.
     */
    public function critical(Request $request): AnonymousResourceCollection
    {
        $doctors = $this->attentionService->getCriticalDoctors($request->user()->business_id);

        return DoctorAttentionScoreResource::collection($doctors);
    }

    /**
     * Display alerts list.
     */
    public function alerts(Request $request): AnonymousResourceCollection
    {
        $alerts = \App\Models\DoctorAttentionAlert::forBusiness($request->user()->business_id)
            ->with(['doctor', 'medicalRep'])
            ->latest()
            ->paginate($request->per_page ?? 15);

        return DoctorAttentionAlertResource::collection($alerts);
    }

    /**
     * Mark alert as read.
     */
    public function markAsRead(Request $request, $alertId): JsonResponse
    {
        $alert = \App\Models\DoctorAttentionAlert::findOrFail($alertId);
        $alert->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Alert marked as read',
            'data' => new DoctorAttentionAlertResource($alert),
        ]);
    }

    /**
     * Mark alert as action taken.
     */
    public function markAsActionTaken(Request $request, $alertId): JsonResponse
    {
        $request->validate([
            'action_details' => 'required|string|max:500',
        ]);

        $this->attentionService->markAlertAsActionTaken($alertId, $request->action_details);

        return response()->json([
            'success' => true,
            'message' => 'Action marked as taken',
        ]);
    }

    /**
     * Get attention statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $statistics = $this->attentionService->getStatistics($request->user()->business_id);

        return response()->json([
            'success' => true,
            'data' => $statistics,
        ]);
    }

    /**
     * Update attention settings.
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $request->validate([
            'referral_drop_threshold' => 'nullable|numeric|min:0|max:100',
            'referral_drop_period_days' => 'nullable|integer|min:1|max:90',
            'inactivity_threshold_days' => 'nullable|integer|min:1|max:90',
            'critical_inactivity_days' => 'nullable|integer|min:1|max:180',
            'attention_score_warning' => 'nullable|numeric|min:0|max:100',
            'attention_score_critical' => 'nullable|numeric|min:0|max:100',
            'enable_push_notifications' => 'nullable|boolean',
            'enable_email_notifications' => 'nullable|boolean',
            'enable_sms_notifications' => 'nullable|boolean',
            'notify_roles' => 'nullable|array',
            'alert_frequency_hours' => 'nullable|integer|min:1|max:168',
        ]);

        $settings = DoctorAttentionSettings::getDefaults($request->user()->business_id, $request->user()->branch_id);
        $settings->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Settings updated successfully',
            'data' => $settings,
        ]);
    }

    /**
     * Get attention settings.
     */
    public function getSettings(Request $request): JsonResponse
    {
        $settings = DoctorAttentionSettings::getDefaults($request->user()->business_id, $request->user()->branch_id);

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Trigger manual score calculation.
     */
    public function calculateScores(Request $request): JsonResponse
    {
        $this->attentionService->calculateScoresForBusiness($request->user()->business_id, $request->user()->branch_id);

        return response()->json([
            'success' => true,
            'message' => 'Attention scores calculated successfully',
        ]);
    }

    /**
     * Record doctor activity.
     */
    public function recordActivity(Request $request): JsonResponse
    {
        $request->validate([
            'doctor_id' => 'required|exists:parties,id',
            'referral_count' => 'nullable|integer|min:0',
            'referral_amount' => 'nullable|numeric|min:0',
            'prescription_count' => 'nullable|integer|min:0',
            'details' => 'nullable|array',
        ]);

        $activity = $this->attentionService->recordActivity([
            'doctor_id' => $request->doctor_id,
            'business_id' => $request->user()->business_id,
            'branch_id' => $request->user()->branch_id,
            'medical_rep_id' => $request->user()->id,
            'activity_date' => $request->activity_date ?? now(),
            'referral_count' => $request->referral_count ?? 0,
            'referral_amount' => $request->referral_amount ?? 0,
            'prescription_count' => $request->prescription_count ?? 0,
            'details' => $request->details,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Activity recorded successfully',
            'data' => new DoctorActivityResource($activity),
        ], 201);
    }
}
