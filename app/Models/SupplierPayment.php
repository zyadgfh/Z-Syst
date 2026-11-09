<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * SupplierPayment - table `supplier_payments` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class SupplierPayment extends Model
{
    
    use TenantScoped;
use SoftDeletes;

    protected $table = 'supplier_payments';

    protected $fillable = [
        'business_id',
        'supplier_id',
        'payment_number',
        'payment_date',
        'payment_method',
        'reference',
        'bank_reference',
        'amount',
        'status',
        'notes',
        'approved_at',
        'file_path',
    ];

    protected $casts = [
        'supplier_id' => 'integer',
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function scopeForBusiness($query, $businessId)
    {
        return $businessId ? $query->where('business_id', $businessId) : $query;
    }
}

