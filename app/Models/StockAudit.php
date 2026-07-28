<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockAudit extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'user_id',
        'audit_number',
        'audit_type',
        'status',
        'audit_date',
        'completed_at',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'json',
        'audit_date' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(StockAuditDetail::class);
    }

    public function reconciliations(): HasMany
    {
        return $this->hasMany(StockReconciliation::class);
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
}
