<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QualityStandard extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id', 'category_id', 'temperature_min', 'temperature_max',
        'humidity_min', 'humidity_max', 'acceptable_defects', 'criteria', 'is_active',
    ];

    protected $casts = [
        'temperature_min' => 'decimal:2',
        'temperature_max' => 'decimal:2',
        'humidity_min' => 'decimal:2',
        'humidity_max' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function business(): BelongsTo { return $this->belongsTo(Business::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }

    public function scopeActive($query) { return $query->where('is_active', true); }
}
