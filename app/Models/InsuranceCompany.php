<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * InsuranceCompany - table `insurance_companies` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class InsuranceCompany extends Model
{
    protected $table = 'insurance_companies';

    protected $fillable = [
        'business_id',
        'name',
        'code',
        'contact_person',
        'phone',
        'email',
        'address',
        'city',
        'country',
        'tax_id',
        'status',
        'integration_type',
        'api_endpoint',
        'api_credentials',
        'default_coverage_percent',
        'default_copay_percent',
        'settlement_days',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'default_coverage_percent' => 'decimal:4',
        'default_copay_percent' => 'decimal:4',
        'settlement_days' => 'integer',
        'metadata' => 'array',
    ];

    protected $hidden = ['api_credentials'];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class, 'tax_id');
    }
}
