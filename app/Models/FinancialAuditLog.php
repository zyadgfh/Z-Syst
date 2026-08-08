<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialAuditLog extends Model
{
    use HasFactory;

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
        'completed_at' => 'datetime',
        'metadata' => 'json',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('audit_type', $type);
    }

    public function calculateVariance()
    {
        $expectedClosingBalance = $this->opening_balance + $this->total_revenue - $this->total_expenses;
        $this->variance = $this->closing_balance - $expectedClosingBalance;

        return $this;
    }
}
