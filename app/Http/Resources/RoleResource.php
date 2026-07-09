<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Role */
class RoleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'color_badge' => $this->color_badge,
            'priority' => $this->priority,
            'is_system' => (bool) $this->is_system,
            'status' => (bool) $this->status,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            $this->mergeWhen($this->relationLoaded('permissions'), [
                'permissions' => PermissionResource::collection($this->whenLoaded('permissions')),
            ]),
        ];
    }
}
