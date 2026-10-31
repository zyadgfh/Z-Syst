<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DoctorAttentionAlert - table `doctor_attention_alerts` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class DoctorAttentionAlert extends Model
{
    use SoftDeletes;

    protected $table = 'doctor_attention_alerts';

    protected $fillable = [
        'doctor_id',
        'business_id',
        'branch_id',
        'medical_rep_id',
        'alert_type',
        'severity',
        'message',
        'details',
        'is_sent',
        'sent_at',
        'is_read',
        'read_at',
        'action_taken',
        'action_details',
        'action_taken_at',
    ];

    protected $casts = [
        'doctor_id' => 'integer',
        'details' => 'array',
        'is_sent' => 'boolean',
        'sent_at' => 'datetime',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'action_taken' => 'boolean',
        'action_taken_at' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function appUser(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'medical_rep_id');
    }
}
