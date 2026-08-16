<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_definition_id',
        'name',
        'sequence',
        'approval_type',
        'conditions',
        'actions',
        'is_final',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'approval_type' => 'string',
        'conditions' => 'array',
        'actions' => 'array',
        'is_final' => 'boolean',
    ];

    public function definition(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'workflow_definition_id');
    }

    public function approvers(): HasMany
    {
        return $this->hasMany(WorkflowStepApprover::class, 'workflow_step_id');
    }

    public function instances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class, 'current_step_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sequence');
    }

    public function scopeFinal($query)
    {
        return $query->where('is_final', true);
    }
}
