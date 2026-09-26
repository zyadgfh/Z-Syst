<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierRating extends Model
{
    use HasFactory, BelongsToBusiness;

    protected $fillable = [
        'supplier_id',
        'business_id',
        'rating',
        'category',
        'review',
        'rated_by',
        'rated_at',
    ];

    protected $casts = [
        'rating' => 'decimal:2',
        'rated_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function ratedBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
