<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'username' => $this->username,
            'email' => $this->email,
            'phone' => $this->phone,
            'profile_photo' => $this->profile_photo,
            'profile_photo_url' => $this->profile_photo ? url("storage/{$this->profile_photo}") : null,
            'status' => $this->status,
            'is_active' => (bool) $this->is_active,
            'role' => $this->role,
            'job_title' => $this->job_title,
            'last_login_at' => $this->last_login_at?->toISOString(),
            'last_activity_at' => $this->last_activity_at?->toISOString(),
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),

            $this->mergeWhen($this->relationLoaded('company'), [
                'company' => new CompanyResource($this->whenLoaded('company')),
            ]),

            $this->mergeWhen($this->relationLoaded('branch'), [
                'branch' => new BranchResource($this->whenLoaded('branch')),
            ]),

            $this->mergeWhen($this->relationLoaded('department'), [
                'department' => new DepartmentResource($this->whenLoaded('department')),
            ]),

            $this->mergeWhen($this->relationLoaded('roles'), [
                'roles' => RoleResource::collection($this->whenLoaded('roles')),
                'permissions' => $this->whenLoaded('roles', fn () => $this->getAllPermissions()->pluck('slug')),
            ]),
        ];
    }
}