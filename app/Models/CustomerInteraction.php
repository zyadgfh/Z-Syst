<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerInteraction extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'party_id',
        'type',
        'notes',
        'user_id',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for business
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    /**
     * Scope for specific party
     */
    public function scopeForParty($query, $partyId)
    {
        return $query->where('party_id', $partyId);
    }

    /**
     * Scope for calls
     */
    public function scopeCalls($query)
    {
        return $query->where('type', 'call');
    }

    /**
     * Scope for visits
     */
    public function scopeVisits($query)
    {
        return $query->where('type', 'visit');
    }

    /**
     * Scope for emails
     */
    public function scopeEmails($query)
    {
        return $query->where('type', 'email');
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'call' => __('Phone Call'),
            'visit' => __('In-Store Visit'),
            'email' => __('Email'),
            'meeting' => __('Meeting'),
            'support' => __('Support Ticket'),
            default => ucfirst($this->type),
        };
    }
}
