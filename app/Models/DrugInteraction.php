<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DrugInteraction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'business_id',
        'drug_a_name',
        'drug_b_name',
        'severity',
        'description',
        'mechanism',
        'recommendation',
        'source',
        'category',
        'meta',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'meta' => 'json',
    ];

    /**
     * The severity labels for display.
     */
    const SEVERITY_LABELS = [
        'contraindicated' => 'Contraindicated',
        'severe' => 'Severe',
        'moderate' => 'Moderate',
        'minor' => 'Minor',
    ];

    /**
     * The severity colors for UI.
     */
    const SEVERITY_COLORS = [
        'contraindicated' => '#DC2626', // Red
        'severe' => '#EA580C', // Orange
        'moderate' => '#EAB308', // Yellow
        'minor' => '#22C55E', // Green
    ];

    /**
     * Get the business that owns the drug interaction.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Scope a query to only include interactions for a specific business.
     */
    public function scopeForBusiness($query, $businessId)
    {
        return $query->where('business_id', $businessId)
                    ->orWhereNull('business_id'); // Include global interactions
    }

    /**
     * Scope a query to search by drug name.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('drug_a_name', 'like', '%' . $search . '%')
              ->orWhere('drug_b_name', 'like', '%' . $search . '%')
              ->orWhere('description', 'like', '%' . $search . '%');
        });
    }

    /**
     * Scope a query to filter by severity.
     */
    public function scopeSeverity($query, $severity)
    {
        return $query->where('severity', $severity);
    }

    /**
     * Get the reverse interaction (swap drug_a and drug_b).
     */
    public function getReverseAttribute(): array
    {
        return [
            'drug_a_name' => $this->drug_b_name,
            'drug_b_name' => $this->drug_a_name,
            'severity' => $this->severity,
            'description' => $this->description,
            'recommendation' => $this->recommendation,
        ];
    }
}

