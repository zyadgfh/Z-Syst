<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expense extends Model
{
    use TenantScoped;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'business_id',
        'amount',
        'expense_category_id',
        'expanse_for',
        'payment_type',
        'reference_no',
        'note',
        'expense_date',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'amount' => 'double',
        'user_id' => 'integer',
        'business_id' => 'integer',
        'expense_category_id' => 'integer',
    ];
}
