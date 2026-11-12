<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPermission extends Model
{
    /**
     * `business_id` is fillable but is NOT a query scope on this model: grants are
     * looked up by user_id during auth resolution, and a TenantScoped global scope
     * here could hide a user's own permissions while their identity is being
     * resolved. The column exists for the database's Row Level Security policies;
     * HasSupabaseRoles populates it when a grant is created.
     */
    protected $fillable = ['business_id', 'user_id', 'name'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }
}
