<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicineType extends Model
{
    use HasFactory, HasCompany;

    protected $fillable = [
        'name',
        'company_id',
        'status'
    ];
}
