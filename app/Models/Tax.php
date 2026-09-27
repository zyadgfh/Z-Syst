<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    use HasFactory, BelongsToBusiness;

    protected $fillable = [
        'name',
        'rate',
        'status',
        'sub_tax',
        'business_id',
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
        'business_id' => 'integer',
    ];
}
