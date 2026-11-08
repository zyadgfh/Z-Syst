<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * BatchLot - table `batch_lots` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class BatchLot extends Model
{
    use HasFactory;

    protected $table = 'batch_lots';

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
        'product_id' => 'integer',
        'manufacture_date' => 'date',
        'expiry_date' => 'date',
        'recall_date' => 'date',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function scopeForBusiness($query, $businessId)
    {
        return $businessId ? $query->where('business_id', $businessId) : $query;
    }

    public function isExpired(): bool
    {
        return filled($this->expiry_date) && $this->expiry_date->isPast();
    }

    public function isRecalled(): bool
    {
        return filled($this->recall_date) && $this->recall_date->isPast();
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')->where('expiry_date', '<', now());
    }
}
