<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncomeCategory extends Model
{
    use HasFactory, BelongsToBusiness;

    protected $fillable = [
        'categoryName',
        'business_id',
        'categoryDescription',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];
}
