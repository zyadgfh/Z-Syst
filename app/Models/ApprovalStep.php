<?php

namespace App\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalStep extends Model
{
    use HasFactory;

    /**
     * Steps belong to a business exactly like their parent workflow does, and the
     * migration declares `business_id` NOT NULL with a foreign key. Without this
     * trait a step was written with no tenant at all: ApprovalWorkflowService
     * passes business_id to the workflow but not to the steps, so every step
     * either violated the NOT NULL constraint (on a freshly migrated database) or
     * landed as NULL in production, where TenantScoped's `business_id = tenant`
     * filter makes a NULL row invisible to every tenant — an orphan nobody can
     * read back. The trait fills it on create and scopes reads; the service also
     * passes it explicitly so the value never depends on there being an
     * authenticated user (queue workers and console commands have none).
     */
    use TenantScoped;

    protected $fillable = [
        'business_id', 'workflow_id', 'step_number', 'approver_id', 'approver_role',
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
