<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CashRegister - table `cash_registers` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class CashRegister extends Model
{
    protected $table = 'cash_registers';

    protected $fillable = [
        'business_id',
        'branch_id',
        'opened_by',
        'closed_by',
        'status',
        'opened_at',
        'closed_at',
        'opening_balance',
        'closing_balance',
        'expected_balance',
        'cash_sales',
        'cash_refunds',
        'cash_in',
        'cash_out',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'opening_balance' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'expected_balance' => 'decimal:2',
        'cash_sales' => 'decimal:4',
        'cash_refunds' => 'decimal:4',
        'cash_in' => 'decimal:4',
        'cash_out' => 'decimal:4',
        'metadata' => 'array',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }
}
