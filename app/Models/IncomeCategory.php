<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncomeCategory extends Model
{
    use HasFactory, HasCompany;

    protected $fillable = [
        'categoryName',
        'company_id',
        'categoryDescription',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];
}
