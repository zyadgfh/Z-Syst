<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Drug Interaction Model
 * 
 * Tracks dangerous interactions between medications
 * Critical for pharmacy compliance and patient safety
 */
class DrugInteraction extends Model
{
    use HasCompany, HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'drug_interactions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'product_a_id',
        'product_b_id',
        'interaction_level', // mild, moderate, severe, contraindicated
        'description',
        'clinical_effects',
        'management',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the first product in the interaction.
     */
    public function productA(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_a_id');
    }

    /**
     * Get the second product in the interaction.
     */
    public function productB(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_b_id');
    }

    /**
     * Get the company that owns this record.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Check if this is a dangerous interaction.
     */
    public function isDangerous(): bool
    {
        return in_array($this->interaction_level, ['severe', 'contraindicated']);
    }

    /**
     * Get all dangerous interactions for a given product.
     */
    public static function dangerousForProduct(int $productId): array
    {
        return static::where('is_active', true)
            ->where(function ($query) use ($productId) {
                $query->where('product_a_id', $productId)
                    ->orWhere('product_b_id', $productId);
            })
            ->whereIn('interaction_level', ['severe', 'contraindicated'])
            ->get()
            ->map(function ($interaction) {
                return [
                    'product_id' => $interaction->product_a_id === $productId 
                        ? $interaction->product_b_id 
                        : $interaction->product_a_id,
                    'interaction_level' => $interaction->interaction_level,
                    'description' => $interaction->description,
                ];
            })
            ->toArray();
    }
}