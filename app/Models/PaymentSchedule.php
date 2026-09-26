<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentSchedule extends Model
{
    use HasFactory, BelongsToBusiness;

    protected $fillable = [
        'supplier_id', 'business_id', 'scheduled_date', 'amount',
        'status', 'paid_date', 'payment_method', 'notes', 'reminders_sent',
    ];

    protected $casts = [
        'scheduled_date' => 'datetime',
        'paid_date' => 'datetime',
        'amount' => 'decimal:2',
        'reminders_sent' => 'boolean',
    ];

    public function supplier(): BelongsTo { return $this->belongsTo(Supplier::class); }
    public function business(): BelongsTo { return $this->belongsTo(Business::class); }

    public function scopeForBusiness($query, $businessId) { return $query->where('business_id', $businessId); }
    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopePaid($query) { return $query->where('status', 'paid'); }
}
