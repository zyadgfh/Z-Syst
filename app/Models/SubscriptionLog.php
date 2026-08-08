<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'subscription_id', 'business_id', 'action', 'old_data', 'new_data',
        'performed_by', 'notes',
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForAction($query, string $action)
    {
        return $query->where('action', $action);
    }
}
