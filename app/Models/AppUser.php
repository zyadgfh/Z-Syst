<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Models\Concerns\HasSupabaseRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AppUser - table `app_users` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class AppUser extends Authenticatable
{
    use HasSupabaseRoles;
    use Notifiable;

    protected $table = 'app_users';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'business_id',
        'name',
        'role',
        'phone',
        'image',
        'lang',
        'status',
        'visibility',
        'branch_id',
    ];

    protected $casts = [
        'visibility' => 'array',
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
