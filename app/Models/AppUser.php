<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\Concerns\HasSupabaseRoles;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AppUser - table `app_users` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class AppUser extends Authenticatable
{
    use HasFactory;

    // Authorisation comes from HasSupabaseRoles (app_users.role column +
    // config/supabase.php permissions map) — spatie's Gate::before hooks
    // into checkPermissionTo() on this trait. No assignRole() here: this
    // model intentionally does not use spatie's HasRoles trait.
    use HasApiTokens;
    use HasSupabaseRoles;
    use Notifiable;

    protected $table = 'app_users';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'business_id',
        'name',
        'email',
        'password',
        'role',
        'phone',
        'image',
        'lang',
        'status',
        'visibility',
        'branch_id',
        'two_factor_secret',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'remember_token',
    ];

    protected $casts = [
        'visibility' => 'array',
        'two_factor_secret' => 'encrypted',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
