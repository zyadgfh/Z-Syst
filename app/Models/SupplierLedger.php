<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierLedger extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'party_id',
        'user_id',
        'transaction_type',
        'reference_type',
        'reference_id',
        'invoice_number',
        'debit',
        'credit',
        'balance_after',
        'description',
        'notes',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    // Transaction type constants
    const TYPE_PURCHASE = 'purchase';
    const TYPE_PURCHASE_RETURN = 'purchase_return';
    const TYPE_PAYMENT = 'payment';
    const TYPE_ADJUSTMENT = 'adjustment';
    const TYPE_OPENING = 'opening';

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the referenced model (polymorphic-like).
     */
    public function reference()
    {
        return match ($this->reference_type) {
            'Purchase' => $this->belongsTo(Purchase::class, 'reference_id'),
            'PurchaseReturn' => $this->belongsTo(PurchaseReturn::class, 'reference_id'),
            default => null,
        };
    }

    /**
     * Scope for a specific business.
     */
    public function scopeForBusiness($query, int $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope for a specific supplier (party).
     */
    public function scopeForParty($query, int $partyId)
    {
        return $query->where('party_id', $partyId);
    }

    /**
     * Scope for a specific transaction type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('transaction_type', $type);
    }

    /**
     * Get the current balance for a supplier.
     */
    public static function getBalance(int $businessId, int $partyId): float
    {
        $lastEntry = static::where('business_id', $businessId)
            ->where('party_id', $partyId)
            ->latest()
            ->first();

        return $lastEntry ? (float) $lastEntry->balance_after : 0;
    }

    /**
     * Get transaction type label.
     */
    public function getTransactionTypeLabelAttribute(): string
    {
        return match ($this->transaction_type) {
            self::TYPE_PURCHASE => __('Purchase Invoice'),
            self::TYPE_PURCHASE_RETURN => __('Purchase Return'),
            self::TYPE_PAYMENT => __('Payment to Supplier'),
            self::TYPE_ADJUSTMENT => __('Balance Adjustment'),
            self::TYPE_OPENING => __('Opening Balance'),
            default => ucfirst($this->transaction_type),
        };
    }
}
