<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receipt extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'sale_id',
        'purchase_id',
        'receipt_number',
        'type',
        'format',
        'status',
        'user_id',
        'data',
    ];

    protected $casts = [
        'data' => 'json',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for business
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope for sales receipts
     */
    public function scopeSales($query)
    {
        return $query->where('type', 'sale');
    }

    /**
     * Scope for purchase receipts
     */
    public function scopePurchases($query)
    {
        return $query->where('type', 'purchase');
    }

    /**
     * Scope for generated receipts
     */
    public function scopeGenerated($query)
    {
        return $query->where('status', 'generated');
    }

    /**
     * Scope for printed receipts
     */
    public function scopePrinted($query)
    {
        return $query->where('status', 'printed');
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'sale' => __('Sale'),
            'purchase' => __('Purchase'),
            'return' => __('Return'),
            'refund' => __('Refund'),
            default => ucfirst($this->type),
        };
    }

    /**
     * Get format label
     */
    public function getFormatLabelAttribute(): string
    {
        return match ($this->format) {
            'pdf' => 'PDF',
            'html' => 'HTML',
            'thermal' => __('Thermal'),
            default => strtoupper($this->format),
        };
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'generated' => __('Generated'),
            'printed' => __('Printed'),
            'emailed' => __('Emailed'),
            'failed' => __('Failed'),
            default => ucfirst($this->status),
        };
    }
}
