<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingAutomationRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'onboarding_template_id',
        'name',
        'trigger_event',
        'trigger_conditions',
        'action_type',
        'action_config',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'trigger_conditions' => 'array',
        'action_config' => 'array',
        'is_active' => 'boolean',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(OnboardingTemplate::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByEvent($query, string $event)
    {
        return $query->where('trigger_event', $event);
    }
}