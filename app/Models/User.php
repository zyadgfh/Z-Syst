<?php

namespace App\Models;

use App\Core\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use App\Models\Permission as AppPermission;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, HasCompany;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'business_id',
        'name',
        'role',
        'email',
        'phone',
        'image',
        'lang',
        'status',
        'password',
        'visibility',
        'remember_token',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password' => 'hashed',
        'visibility' => 'json',
        'email_verified_at' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Department::class);
    }

    public function hasPermission(string $permission): bool
    {
        try {
            // Resolve permission by slug or name
            $perm = AppPermission::where('slug', $permission)
                ->orWhere('name', $permission)
                ->first();

            if (! $perm) {
                return false;
            }

            // Check direct role->permission relationship to avoid cached results
            return $this->roles()
                ->whereHas('permissions', fn ($q) => $q->where('id', $perm->id))
                ->exists();
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function isSuperAdmin(): bool
    {
        // Support legacy `role` attribute used in factories/tests
        if (! empty($this->role) && in_array($this->role, ['super_admin', 'super-admin', 'superadmin'], true)) {
            return true;
        }

        try {
            // Common super-admin role names used across the app
            return $this->hasRole(['super-admin', 'superadmin']);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
