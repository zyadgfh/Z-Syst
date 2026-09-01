<?php

namespace App\Models;

use Database\Factories\BatchLotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BatchLot extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return BatchLotFactory::new();
    }

    protected $fillable = [
        'business_id',
        'product_id',
        'batch_number',
        'lot_number',
        'quantity',
        'manufacture_date',
        'expiry_date',
        'recall_date',
        'status',
        'supplier_name',
        'notes',
    ];

    protected $casts = [
        'manufacture_date' => 'date',
        'expiry_date' => 'date',
        'recall_date' => 'date',
        'quantity' => 'integer',
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

    public function affectedRecalls(): BelongsToMany
    {
        return $this->belongsToMany(RecallEvent::class, 'recall_affected_batches')
            ->withPivot(['quarantine_status', 'quarantined_at', 'resolved_at', 'quantity_affected', 'notes'])
            ->withTimestamps();
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
     * Scope for quarantined batches
     */
    public function scopeQuarantined($query)
    {
        return $query->where('status', 'quarantined');
    }

    /**
     * Scope for active batches
     */
    public function scopeActiveBatches($query)
    {
        return $query->where('status', 'active');
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
     * Check if batch is quarantined
     */
    public function isQuarantined(): bool
    {
        return $this->status === 'quarantined';
    }

    /**
     * Quarantine this batch
     */
    public function quarantine(): bool
    {
        return $this->update([
            'status' => 'quarantined',
            'recall_date' => $this->recall_date ?? now(),
        ]);
    }

    /**
     * Release this batch from quarantine
     */
    public function release(): bool
    {
        return $this->update(['status' => 'active']);
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
        if (! $this->expiry_date) {
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
