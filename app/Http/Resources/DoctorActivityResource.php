<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorActivityResource extends JsonResource
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
            'medical_rep_id' => $this->medical_rep_id,
            'activity_date' => $this->activity_date->toIso8601String(),
            'referral_count' => $this->referral_count,
            'referral_amount' => (float) $this->referral_amount,
            'prescription_count' => $this->prescription_count,
            'details' => $this->details,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),

            // Relationships
            'doctor' => new PartyResource($this->whenLoaded('doctor')),
            'medical_rep' => new UserResource($this->whenLoaded('medicalRep')),
        ];
    }
}
