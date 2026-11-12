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
        'user_id',
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
        // expenses.user_id is a uuid (app_users.id); an 'integer' cast turned every
        // actor id into 0 on read.
        'user_id' => 'string',
        'business_id' => 'integer',
        'expense_category_id' => 'integer',
    ];
}
