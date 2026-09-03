<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPortalActivity extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'business_id',
        'supplier_portal_user_id',
        'action',
        'entity_type',
        'entity_id',
        'details',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'details' => 'array',
    ];

    /**
     * Get the business that owns the activity.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the supplier user who performed the action.
     */
    public function supplierUser(): BelongsTo
    {
        return $this->belongsTo(SupplierPortalUser::class, 'supplier_portal_user_id');
    }

    /**
     * Get the entity associated with the activity.
     */
    public function entity()
    {
        if (!$this->entity_type || !$this->entity_id) {
            return null;
        }

        return $this->belongsTo($this->entity_type, 'entity_id');
    }
}