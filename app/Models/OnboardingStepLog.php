<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingStepLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'onboarding_instance_id',
        'step_name',
        'step_order',
        'status',
        'step_data',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'step_data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function instance(): BelongsTo
    {
        return $this->belongsTo(TenantOnboardingInstance::class);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}