<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierShipment extends Model
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
        'tracking_number',
        'carrier',
        'status',
        'estimated_delivery_date',
        'actual_delivery_date',
        'shipping_notes',
        'delivery_address',
        'delivery_contact',
        'delivery_phone',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'estimated_delivery_date' => 'date',
        'actual_delivery_date' => 'date',
    ];

    /**
     * Get the business that owns the shipment.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the purchase order associated with the shipment.
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Get the supplier user who created the shipment.
     */
    public function supplierUser(): BelongsTo
    {
        return $this->belongsTo(SupplierPortalUser::class, 'supplier_portal_user_id');
    }

    /**
     * Check if shipment is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if shipment is shipped.
     */
    public function isShipped(): bool
    {
        return $this->status === 'shipped';
    }

    /**
     * Check if shipment is in transit.
     */
    public function isInTransit(): bool
    {
        return $this->status === 'in_transit';
    }

    /**
     * Check if shipment is delivered.
     */
    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    /**
     * Check if shipment is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Get delivery status in days.
     */
    public function getDeliveryStatus(): string
    {
        if ($this->isDelivered()) {
            return 'Delivered';
        }

        if ($this->isCancelled()) {
            return 'Cancelled';
        }

        if (!$this->estimated_delivery_date) {
            return 'Pending';
        }

        $daysUntilDelivery = now()->diffInDays($this->estimated_delivery_date, false);

        if ($daysUntilDelivery < 0) {
            return 'Overdue';
        }

        if ($daysUntilDelivery <= 2) {
            return 'Arriving Soon';
        }

        return 'On Schedule';
    }
}