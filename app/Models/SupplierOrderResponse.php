<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierOrderResponse extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'business_id',
        'purchase_order_id',
        'supplier_portal_user_id',
        'response_type',
        'response_notes',
        'proposed_total',
        'proposed_delivery_date',
        'modifications',
        'responded_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'proposed_total' => 'decimal:2',
        'proposed_delivery_date' => 'date',
        'modifications' => 'array',
        'responded_at' => 'datetime',
    ];

    /**
     * Get the business that owns the response.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the purchase order associated with the response.
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Get the supplier user who created the response.
     */
    public function supplierUser(): BelongsTo
    {
        return $this->belongsTo(SupplierPortalUser::class, 'supplier_portal_user_id');
    }

    /**
     * Check if response is accepted.
     */
    public function isAccepted(): bool
    {
        return $this->response_type === 'accepted';
    }

    /**
     * Check if response is rejected.
     */
    public function isRejected(): bool
    {
        return $this->response_type === 'rejected';
    }

    /**
     * Check if response is modified.
     */
    public function isModified(): bool
    {
        return $this->response_type === 'modified';
    }

    /**
     * Check if response is pending.
     */
    public function isPending(): bool
    {
        return $this->response_type === 'pending';
    }
}