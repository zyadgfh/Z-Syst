<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ControlledSubstancesLog - table `controlled_substances_log` (Supabase Postgres).
 * AUTO-GENERATED from the live schema by tools/generate-models.py - re-run after migrations.
 */
class ControlledSubstancesLog extends Model
{
    protected $table = 'controlled_substances_log';

    const UPDATED_AT = null;

    protected $fillable = [
        'business_id',
        'branch_id',
        'product_id',
        'stock_id',
        'prescription_id',
        'sale_id',
        'action',
        'quantity',
        'previous_balance',
        'remaining_balance',
        'patient_name',
        'patient_phone',
        'doctor_name',
        'doctor_license',
        'notes',
        'actor_user_id',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'stock_id' => 'integer',
        'prescription_id' => 'integer',
        'sale_id' => 'integer',
        'quantity' => 'integer',
        'previous_balance' => 'integer',
        'remaining_balance' => 'integer',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class, 'business_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'stock_id');
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class, 'prescription_id');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function appUser(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'actor_user_id');
    }
}
