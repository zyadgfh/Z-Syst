<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TenantOnboardingInstance extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'onboarding_template_id',
        'initiated_by',
        'status',
        'current_step',
        'total_steps',
        'progress_data',
        'started_at',
        'completed_at',
        'notes',
    ];

    protected $casts = [
        'progress_data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(OnboardingTemplate::class);
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function stepLogs(): HasMany
    {
        return $this->hasMany(OnboardingStepLog::class);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}