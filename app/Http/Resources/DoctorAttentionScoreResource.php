<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorAttentionScoreResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'doctor_id' => $this->doctor_id,
            'business_id' => $this->business_id,
            'branch_id' => $this->branch_id,
            'calculated_date' => $this->calculated_date->toIso8601String(),
            'attention_score' => (float) $this->attention_score,
            'decline_percentage' => (float) $this->decline_percentage,
            'days_inactive' => $this->days_inactive,
            'last_referral_date' => $this->last_referral_date?->toIso8601String(),
            'baseline_referrals' => (float) $this->baseline_referrals,
            'current_period_referrals' => $this->current_period_referrals,
            'previous_period_referrals' => $this->previous_period_referrals,
            'status' => $this->status,
            'alert_reason' => $this->alert_reason,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            // Computed fields
            'needs_attention' => $this->needsAttention(),
            'is_critical' => $this->isCritical(),
            'has_low_score' => $this->hasLowScore(),
            'has_significant_decline' => $this->hasSignificantDecline(),
            'is_inactive_too_long' => $this->isInactiveTooLong(),
            'urgency_level' => $this->getUrgencyLevel(),

            // Relationships
            'doctor' => new PartyResource($this->whenLoaded('doctor')),
        ];
    }
}
