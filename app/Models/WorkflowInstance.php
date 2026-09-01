<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkflowInstance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id',
        'workflow_definition_id',
        'entity_type',
        'entity_id',
        'status',
        'current_step_id',
        'initiated_by',
        'initiated_at',
        'completed_at',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'entity_id' => 'integer',
        'status' => 'string',
        'initiated_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'workflow_definition_id');
    }

    public function currentStep(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'current_step_id');
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(WorkflowApproval::class, 'workflow_instance_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(WorkflowHistory::class, 'workflow_instance_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId);
    }

    public function scopeForEntity($query, $entityType, $entityId)
    {
        return $query->where('entity_type', $entityType)->where('entity_id', $entityId);
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, ['approved', 'rejected', 'cancelled']);
    }

    public function canAdvance(): bool
    {
        return $this->status === 'in_progress' && $this->currentStep;
    }

    public function getNextStep(): ?WorkflowStep
    {
        if (!$this->currentStep) {
            return $this->definition->steps()->ordered()->first();
        }

        return $this->definition->steps()
            ->where('sequence', '>', $this->currentStep->sequence)
            ->ordered()
            ->first();
    }
}
