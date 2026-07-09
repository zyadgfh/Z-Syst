<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InsuranceCompany extends Model
{
    use HasCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'contact_person',
        'phone',
        'email',
        'address',
        'discount_percentage',
        'contract_terms',
        'payment_terms',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function plans(): HasMany
    {
        return $this->hasMany(InsurancePlan::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(InsuranceClaim::class);
    }
}
