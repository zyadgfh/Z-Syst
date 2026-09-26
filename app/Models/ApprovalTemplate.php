<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalTemplate extends Model
{
    use HasFactory, BelongsToBusiness;

    protected $fillable = [
        'business_id', 'type', 'name', 'description', 'steps_config',
        'is_active', 'is_default', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'steps_config' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function business(): BelongsTo { return $this->belongsTo(Business::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function updatedBy(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }

    public function scopeActive($query) { return $query->where('is_active', true); }
    public function scopeDefault($query) { return $query->where('is_default', true); }
}
