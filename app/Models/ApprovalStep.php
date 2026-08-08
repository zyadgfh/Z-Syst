<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id', 'step_number', 'approver_id', 'approver_role',
        'status', 'approved_at', 'notes',
    ];

    protected $casts = [
        'step_number' => 'integer',
        'approved_at' => 'datetime',
    ];

    public function workflow(): BelongsTo { return $this->belongsTo(ApprovalWorkflow::class); }
    public function approver(): BelongsTo { return $this->belongsTo(User::class, 'approver_id'); }

    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopeApproved($query) { return $query->where('status', 'approved'); }
    public function scopeRejected($query) { return $query->where('status', 'rejected'); }
}
