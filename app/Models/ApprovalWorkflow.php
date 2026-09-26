<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApprovalWorkflow extends Model
{
    use BelongsToBusiness;
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'type', 'entity_id', 'business_id', 'current_step',
        'status', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'current_step' => 'integer',
    ];

    public function business(): BelongsTo { return $this->belongsTo(Business::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
    public function steps(): HasMany { return $this->hasMany(ApprovalStep::class); }

    public function scopeForBusiness($query, $businessId) { return $query->where('business_id', $businessId); }
    public function scopePending($query) { return $query->where('status', 'pending'); }
    public function scopeApproved($query) { return $query->where('status', 'approved'); }
    public function scopeRejected($query) { return $query->where('status', 'rejected'); }
}
