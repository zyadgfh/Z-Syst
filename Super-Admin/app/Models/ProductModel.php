<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'business_id',
        'status'
    ];

    protected $casts = [
        'business_id' => 'integer',
        'status' => 'integer',
    ];
}
