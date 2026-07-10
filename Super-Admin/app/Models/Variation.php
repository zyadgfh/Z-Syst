<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Variation extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'name',
        'status',
        'values',
    ];

    protected $casts = [
        'values' => 'json',
    ];
}
