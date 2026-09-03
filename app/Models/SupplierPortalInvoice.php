<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPortalInvoice extends Model
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
        'invoice_number',
        'invoice_date',
        'amount',
        'currency',
        'file_path',
        'file_name',
        'file_mime_type',
        'status',
        'verification_notes',
        'verified_at',
        'paid_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'invoice_date' => 'date',
        'amount' => 'decimal:2',
        'verified_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    /**
     * Get the business that owns the invoice.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the purchase order associated with the invoice.
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Get the supplier user who uploaded the invoice.
     */
    public function supplierUser(): BelongsTo
    {
        return $this->belongsTo(SupplierPortalUser::class, 'supplier_portal_user_id');
    }

    /**
     * Check if invoice is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if invoice is verified.
     */
    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    /**
     * Check if invoice is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    /**
     * Check if invoice is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    /**
     * Get file URL.
     */
    public function getFileUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }
}