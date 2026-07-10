<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Modules\AffiliateAddon\App\Models\Affiliate;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'business_id',
        'branch_id',
        'active_branch_id',
        'name',
        'role',
        'email',
        'phone',
        'image',
        'lang',
        'password',
        'visibility',
        'provider',
        'provider_id',
        'is_verified',
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
        'business_id' => 'integer',
        'is_verified' => 'integer',
        'branch_id' => 'integer',
        'active_branch_id' => 'integer',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class)->withTrashed();
    }

    public function active_branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, $this->branch_id ? 'branch_id' : 'active_branch_id')->withTrashed();
    }

    public function affiliate(): HasOne
    {
        return $this->hasOne(Affiliate::class);
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->role === 'shop-owner') return true;
        [$module, $action] = explode('.', $permission);
        return isset($this->visibility[$module][$action]) && $this->visibility[$module][$action] == "1";
    }

    public function hasAnyPermission(array $permissions)
    {
        if ($this->role === 'shop-owner') return true;

        $visibility = $this->visibility ?? [];
        foreach ($permissions as $permission) {
            [$module, $action] = explode('.', $permission);

            if (!empty($visibility[$module][$action]) && $visibility[$module][$action] == "1") {
                return true;
            }
        }

        return false;
    }

    public function accessToMultiBranch()
    {
        return moduleCheck('MultiBranchAddon') && !$this->branch_id && !$this->active_branch_id && multibranch_active() && branch_count();
    }
}
