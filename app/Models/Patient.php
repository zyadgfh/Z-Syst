<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'business_id',
        'name',
        'national_id',
        'date_of_birth',
        'gender',
        'phone',
        'email',
        'address',
        'medical_history',
        'allergies',
        'chronic_conditions',
        'emergency_contact_name',
        'emergency_contact_phone',
        'notes',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'date_of_birth' => 'date',
    ];

    /**
     * Get the business that owns the patient.
     */
    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the prescriptions for the patient.
     */
    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    /**
     * Get the patient's age.
     */
    public function getAgeAttribute(): ?int
    {
        if (!$this->date_of_birth) {
            return null;
        }

        return $this->date_of_birth->age;
    }

    /**
     * Scope a query to search by name or national ID.
     */
    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('national_id', 'like', "%{$term}%")
              ->orWhere('phone', 'like', "%{$term}%");
        });
    }

    /**
     * Check if patient has specific allergy.
     */
    public function hasAllergy(string $allergy): bool
    {
        if (!$this->allergies) {
            return false;
        }

        return str_contains(strtolower($this->allergies), strtolower($allergy));
    }

    /**
     * Check if patient has specific chronic condition.
     */
    public function hasChronicCondition(string $condition): bool
    {
        if (!$this->chronic_conditions) {
            return false;
        }

        return str_contains(strtolower($this->chronic_conditions), strtolower($condition));
    }
}
