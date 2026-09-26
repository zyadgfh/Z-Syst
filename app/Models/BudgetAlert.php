<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetAlert extends Model
{
    use HasFactory, BelongsToBusiness;

    protected $fillable = [
        'budget_id', 'business_id', 'alert_type', 'threshold',
        'alert_sent_at', 'resolved_at', 'notes',
    ];

    protected $casts = [
        'threshold' => 'decimal:2',
        'alert_sent_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function budget(): BelongsTo { return $this->belongsTo(PurchaseBudget::class); }
    public function business(): BelongsTo { return $this->belongsTo(Business::class); }

    public function scopeUnresolved($query) { return $query->whereNull('resolved_at'); }
}
