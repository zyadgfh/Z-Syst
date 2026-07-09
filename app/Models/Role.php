<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color_badge',
        'priority',
        'is_system',
        'status',
    ];

    protected $casts = [
        'is_system' => 'boolean',
        'status' => 'boolean',
        'priority' => 'integer',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'role_has_permissions');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_has_roles');
    }

    public function scopeActive($query)
    {
        return $query->where('status', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderByDesc('priority');
    }

    public function hasPermission(string $permissionSlug): bool
    {
        return $this->permissions()
            ->where('slug', $permissionSlug)
            ->where('status', true)
            ->exists();
    }
}
