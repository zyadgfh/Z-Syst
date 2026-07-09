<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'phone',
        'email',
        'address',
        'date_of_birth',
        'gender',
        'blood_group',
        'allergies',
        'medical_history',
        'emergency_contact_name',
        'emergency_contact_phone',
        'insurance_info',
        'loyalty_points',
        'notes',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'allergies' => 'json',
        'medical_history' => 'json',
        'insurance_info' => 'json',
        'loyalty_points' => 'integer',
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

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }
}
