<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExceptionAuditLog extends Model
{
    protected $table = 'exception_audit_log';

    protected $fillable = [
        'exception_id',
        'business_id',
        'action',
        'actor_id',
        'details',
        'old_values',
        'new_values',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function exception(): BelongsTo
    {
        return $this->belongsTo(VulnerabilityException::class, 'exception_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
