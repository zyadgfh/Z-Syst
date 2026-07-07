<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasCompany;

class Drug extends Model
{
    use HasFactory, SoftDeletes, HasCompany;

    protected $fillable = [
        'company_id',
        'uuid',
        'name',
        'generic_name',
        'barcode',
        'manufacturer',
        'form',
        'strength',
        'notes',
    ];

    protected $casts = [
        'uuid' => 'string',
    ];

    public static function booted()
    {
        // any model-specific boot logic
    }
}
