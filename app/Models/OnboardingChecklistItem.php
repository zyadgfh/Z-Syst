<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnboardingChecklistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'onboarding_template_id',
        'title',
        'description',
        'category',
        'order',
        'is_required',
        'is_default',
        'resources',
    ];

    protected $casts = [
        'resources' => 'array',
        'is_required' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(OnboardingTemplate::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(TenantChecklistProgress::class);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}