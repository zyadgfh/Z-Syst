<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Manufacturer extends Model
{
    use HasFactory, HasCompany;

    protected $fillable = [
        'name',
        'company_id',
        'business_id',
        'description',
        'status',
    ];
}
