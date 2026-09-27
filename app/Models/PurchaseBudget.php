<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PurchaseBudget extends Model
{
    use BelongsToBusiness;
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'business_id', 'branch_id', 'category_id', 'period',
        'budget_amount', 'spent_amount', 'remaining_amount',
        'start_date', 'end_date', 'status', 'created_by', 'approved_by',
    ];

    protected $casts = [
        'budget_amount' => 'decimal:2',
        'spent_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    public function business(): BelongsTo { return $this->belongsTo(Business::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function createdBy(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function approvedBy(): BelongsTo { return $this->belongsTo(User::class, 'approved_by'); }
    public function alerts(): HasMany { return $this->hasMany(BudgetAlert::class); }
    public function transactions(): HasMany { return $this->hasMany(BudgetTransaction::class); }

    public function scopeForBusiness($query, $businessId) { return $query->where('business_id', $businessId); }
    public function scopeActive($query) { return $query->where('status', 'active'); }

    public function getSpentPercentageAttribute(): float
    {
        return $this->budget_amount > 0 ? ($this->spent_amount / $this->budget_amount) * 100 : 0;
    }
}
