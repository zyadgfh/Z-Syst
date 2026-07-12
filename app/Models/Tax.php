<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    use HasFactory, HasCompany;

    protected $fillable = [
        'name',
        'rate',
        'status',
        'sub_tax',
        'company_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'rate' => 'double',
        'sub_tax' => 'json',
        'status' => 'boolean',
        'company_id' => 'integer',
    ];
}
