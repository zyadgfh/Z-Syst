<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingAnalytics extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'onboarding_template_id',
        'date',
        'steps_completed',
        'total_steps',
        'time_spent_minutes',
        'metrics',
    ];

    protected $casts = [
        'date' => 'date',
        'metrics' => 'array',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(OnboardingTemplate::class);
    }

    public function scopeByDate($query, $date)
    {
        return $query->where('date', $date);
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }
}