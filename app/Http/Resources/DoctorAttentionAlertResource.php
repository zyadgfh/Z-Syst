<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorAttentionAlertResource extends JsonResource
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
            'alert_type' => $this->alert_type,
            'severity' => $this->severity,
            'message' => $this->message,
            'details' => $this->details,
            'is_sent' => $this->is_sent,
            'sent_at' => $this->sent_at?->toIso8601String(),
            'is_read' => $this->is_read,
            'read_at' => $this->read_at?->toIso8601String(),
            'action_taken' => $this->action_taken,
            'action_details' => $this->action_details,
            'action_taken_at' => $this->action_taken_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
            
            // Relationships
            'doctor' => new PartyResource($this->whenLoaded('doctor')),
            'medical_rep' => new UserResource($this->whenLoaded('medicalRep')),
        ];
    }
}
