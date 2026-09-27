<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BatchLot extends Model
{
    use HasFactory, BelongsToBusiness;

    protected static function newFactory()
    {
        return \Database\Factories\BatchLotFactory::new();
    }

    protected $fillable = [
        'business_id',
        'product_id',
        'batch_number',
        'lot_number',
        'manufacture_date',
        'expiry_date',
        'recall_date',
        'supplier_name',
        'notes',
    ];

    protected $casts = [
        'manufacture_date' => 'date',
        'expiry_date' => 'date',
        'recall_date' => 'date',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function recallEvents(): HasMany
    {
        return $this->hasMany(RecallEvent::class);
    }

    public function traceabilityLogs(): HasMany
    {
        return $this->hasMany(TraceabilityLog::class);
    }

    /**
     * Scope for business
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope for expired batches
     */
    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now());
    }

    /**
     * Scope for recalled batches
     */
    public function scopeRecalled($query)
    {
        return $query->whereNotNull('recall_date');
    }

    /**
     * Scope for expiring soon (within 30 days)
     */
    public function scopeExpiringSoon($query)
    {
        return $query->where('expiry_date', '<=', now()->addDays(30))
            ->where('expiry_date', '>', now());
    }

    /**
     * Check if batch is expired
     */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date < now();
    }

    /**
     * Check if batch is recalled
     */
    public function isRecalled(): bool
    {
        return $this->recall_date !== null;
    }

    /**
     * Check if batch is expiring soon
     */
    public function isExpiringSoon(): bool
    {
        return $this->expiry_date && 
               $this->expiry_date <= now()->addDays(30) && 
               $this->expiry_date > now();
    }

    /**
     * Get days until expiry
     */
    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->expiry_date) {
            return null;
        }

        return now()->diffInDays($this->expiry_date, false);
    }

    /**
     * Get unique identifier
     */
    public function getIdentifierAttribute(): string
    {
        return $this->batch_number ?: $this->lot_number ?: 'N/A';
    }
}
