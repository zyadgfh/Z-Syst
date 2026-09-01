<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JournalEntry extends Model
{
    protected $fillable = [
        'business_id', 'branch_id', 'entry_number', 'entry_date',
        'reference_type', 'reference_id', 'description', 'notes',
        'status', 'created_by', 'posted_by', 'posted_at',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'posted_at' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalEntryLine::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function reference()
    {
        return $this->morphTo();
    }

    /**
     * Check if debits equal credits (balanced entry).
     */
    public function isBalanced(): bool
    {
        $totals = $this->lines()
            ->selectRaw('SUM(debit) as total_debit, SUM(credit) as total_credit')
            ->first();

        return abs((float) $totals->total_debit - (float) $totals->total_credit) < 0.01;
    }

    /**
     * Get total debits.
     */
    public function getTotalDebitAttribute(): string
    {
        return $this->lines()->sum('debit');
    }

    /**
     * Get total credits.
     */
    public function getTotalCreditAttribute(): string
    {
        return $this->lines()->sum('credit');
    }

    /**
     * Generate next entry number for a business.
     */
    public static function generateEntryNumber(int $businessId): string
    {
        $today = now()->format('Ymd');
        $prefix = "JE-{$today}-";

        $lastEntry = static::where('business_id', $businessId)
            ->where('entry_number', 'like', "{$prefix}%")
            ->orderByDesc('entry_number')
            ->value('entry_number');

        if ($lastEntry) {
            $sequence = (int) substr($lastEntry, -4) + 1;
        } else {
            $sequence = 1;
        }

        return $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Scopes
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }

    public function scopeForPeriod($query, string $from, string $to)
    {
        return $query->whereBetween('entry_date', [$from, $to]);
    }
}
