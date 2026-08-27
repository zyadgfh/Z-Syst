<?php

namespace App\Models;

use Database\Factories\RecallEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RecallEvent extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return RecallEventFactory::new();
    }

    protected $fillable = [
        'business_id',
        'product_id',
        'batch_lot_number',
        'reason',
        'initiated_at',
        'resolved_at',
        'status',
        'description',
        'user_id',
    ];

    protected $casts = [
        'initiated_at' => 'date',
        'resolved_at' => 'date',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function affectedBatches(): BelongsToMany
    {
        return $this->belongsToMany(BatchLot::class, 'recall_affected_batches')
            ->withPivot(['quarantine_status', 'quarantined_at', 'resolved_at', 'quantity_affected', 'notes'])
            ->withTimestamps();
    }

    /**
     * Scope for business
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope for active recalls
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for resolved recalls
     */
    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    /**
     * Scope for pending recalls (not resolved)
     */
    public function scopePending($query)
    {
        return $query->whereNull('resolved_at');
    }

    /**
     * Scope for by status string
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Check if recall is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if recall is resolved
     */
    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    /**
     * Resolve the recall
     */
    public function resolve(): bool
    {
        return $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);
    }

    /**
     * Get duration in days
     */
    public function getDurationDaysAttribute(): ?int
    {
        if (! $this->resolved_at) {
            return now()->diffInDays($this->initiated_at);
        }

        return $this->resolved_at->diffInDays($this->initiated_at);
    }

    /**
     * Get count of affected batches.
     */
    public function getAffectedBatchesCountAttribute(): int
    {
        return $this->affectedBatches()->count();
    }

    /**
     * Get total quantity affected across all batches.
     */
    public function getTotalQuantityAffectedAttribute(): int
    {
        return $this->affectedBatches()->sum('recall_affected_batches.quantity_affected');
    }
}
