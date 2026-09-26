<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicineType extends Model
{
    use HasFactory, BelongsToBusiness;

    protected $fillable = [
        'name',
        'business_id',
        'status',
    ];
}
