<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * FinancialAuditLog - table `financial_audit_logs` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class FinancialAuditLog extends Model
{
    protected $table = 'financial_audit_logs';

    protected $fillable = [
        'business_id',
        'user_id',
        'audit_number',
        'audit_type',
        'start_date',
        'end_date',
        'status',
        'opening_balance',
        'total_revenue',
        'total_expenses',
        'closing_balance',
        'variance',
        'notes',
        'metadata',
        'completed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'opening_balance' => 'decimal:2',
        'total_revenue' => 'decimal:2',
        'total_expenses' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'variance' => 'decimal:4',
        'metadata' => 'array',
        'completed_at' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function appUser(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'user_id');
    }
}
