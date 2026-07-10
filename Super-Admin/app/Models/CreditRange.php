<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditRange extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'name',
        'range',
        'status',
    ];

    protected $casts =[
        'business_id' => 'integer',
        'range' => 'double',
        'status' => 'integer',
    ];
}
