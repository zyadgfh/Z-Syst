<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierRating extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'supplier_id',
        'business_id',
        'rating',
        'category',
        'review',
        'rated_by',
        'rated_at',
        'party_id',
        'purchase_order_id',
        'rating_quality',
        'rating_delivery',
        'rating_price',
        'rating_communication',
        'overall_rating',
        'is_public',
        'created_by',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'rating' => 'decimal:1',
        'rating_quality' => 'integer',
        'rating_delivery' => 'integer',
        'rating_price' => 'integer',
        'rating_communication' => 'integer',
        'overall_rating' => 'decimal:1',
        'is_public' => 'boolean',
        'rated_at' => 'datetime',
    ];

    /**
     * Get the business that owns the rating.
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /**
     * Get the party (supplier) being rated.
     */
    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class);
    }

    /**
     * Get the purchase order associated with the rating.
     */
    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Get the user who created the rating.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Calculate overall rating.
     */
    public function calculateOverallRating(): float
    {
        $ratings = [
            $this->rating_quality,
            $this->rating_delivery,
            $this->rating_price,
            $this->rating_communication,
        ];

        $validRatings = array_filter($ratings, fn($r) => $r > 0);

        if (empty($validRatings)) {
            return 0.0;
        }

        return round(array_sum($validRatings) / count($validRatings), 1);
    }
}