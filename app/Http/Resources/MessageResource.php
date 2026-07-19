<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Message */
class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'subject' => $this->subject,
            'content' => $this->content,
            'metadata' => $this->metadata,
            'is_read' => $this->is_read,
            'read_at' => $this->read_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            // Type label in Arabic
            'type_label' => $this->getTypeLabel(),

            $this->mergeWhen($this->relationLoaded('sender'), [
                'sender' => new UserResource($this->whenLoaded('sender')),
            ]),

            $this->mergeWhen($this->relationLoaded('recipient'), [
                'recipient' => new UserResource($this->whenLoaded('recipient')),
            ]),

            $this->mergeWhen($this->relationLoaded('branch'), [
                'branch' => new BranchResource($this->whenLoaded('branch')),
            ]),
        ];
    }

    /**
     * Get type label in Arabic.
     */
    private function getTypeLabel(): string
    {
        return match ($this->type) {
            'stock_refill' => 'طلب تعبئة مخزون',
            'prescription_ready' => 'وصفة جاهزة',
            'low_stock' => 'مخزون منخفض',
            'purchase_request' => 'طلب مشتريات',
            'general' => 'عام',
            default => 'غير محدد',
        };
    }
}