<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowStepApprover extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_step_id',
        'user_id',
        'role_id',
        'approval_level',
        'sequence',
        'is_primary',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'is_primary' => 'boolean',
    ];

    public function step(): BelongsTo
    {
        return $this->belongsTo(WorkflowStep::class, 'workflow_step_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function scopeRequired($query)
    {
        return $query->where('approval_level', 'required');
    }

    public function scopeOptional($query)
    {
        return $query->where('approval_level', 'optional');
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sequence');
    }
}
