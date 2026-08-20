<?php

namespace App\Models;

use App\Services\InvoiceNumberService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'party_id',
        'business_id',
        'user_id',
        'tax_id',
        'discountAmount',
        'tax_amount',
        'dueAmount',
        'paidAmount',
        'totalAmount',
        'invoiceNumber',
        'isPaid',
        'paymentType',
        'purchaseDate',
        'purchase_data',
        'note',
        'status',
        'received_at',
        'canceled_at',
    ];

    public function details()
    {
        return $this->hasMany(PurchaseDetails::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function purchaseReturns()
    {
        return $this->hasMany(PurchaseReturn::class, 'purchase_id');
    }

    public function supplierInvoice()
    {
        return $this->hasOne(SupplierInvoice::class);
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (! $model->invoiceNumber && auth()->check()) {
                $invoiceNumberService = app(InvoiceNumberService::class);
                $model->invoiceNumber = $invoiceNumberService->generatePurchaseInvoiceNumber(auth()->user()->business_id);
            }
        });
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'purchaseDate' => 'date',
        'received_at' => 'datetime',
        'canceled_at' => 'datetime',
        'isPaid' => 'boolean',
        'discountAmount' => 'double',
        'dueAmount' => 'double',
        'paidAmount' => 'double',
        'totalAmount' => 'double',
        'purchase_data' => 'json',
    ];

    /**
     * Mark purchase as received.
     */
    public function markAsReceived(): void
    {
        $this->update([
            'status' => 'received',
            'received_at' => now(),
        ]);
    }

    /**
     * Mark purchase as partially received.
     */
    public function markAsPartial(): void
    {
        $this->update([
            'status' => 'partial',
            'received_at' => now(),
        ]);
    }

    /**
     * Cancel the purchase.
     */
    public function cancel(): void
    {
        $this->update([
            'status' => 'canceled',
            'canceled_at' => now(),
        ]);
    }

    /**
     * Check if purchase is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if purchase is received.
     */
    public function isReceived(): bool
    {
        return $this->status === 'received';
    }

    /**
     * Check if purchase is partially received.
     */
    public function isPartial(): bool
    {
        return $this->status === 'partial';
    }

    /**
     * Check if purchase is canceled.
     */
    public function isCanceled(): bool
    {
        return $this->status === 'canceled';
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get pending purchases.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get received purchases.
     */
    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }

    /**
     * Scope to get partially received purchases.
     */
    public function scopePartial($query)
    {
        return $query->where('status', 'partial');
    }

    /**
     * Scope to get canceled purchases.
     */
    public function scopeCanceled($query)
    {
        return $query->where('status', 'canceled');
    }
}
