<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * PaymentSchedule - table `payment_schedules` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class PaymentSchedule extends Model
{
    protected $table = 'payment_schedules';

    protected $fillable = [
        'business_id',
        'supplier_id',
        'scheduled_date',
        'amount',
        'status',
        'paid_date',
        'payment_method',
        'notes',
        'reminders_sent',
    ];

    protected $casts = [
        'supplier_id' => 'integer',
        'scheduled_date' => 'date',
        'amount' => 'decimal:2',
        'paid_date' => 'date',
        'reminders_sent' => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
}
