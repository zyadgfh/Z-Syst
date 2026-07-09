<?php

namespace App\Models;

use App\Traits\HasCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Drug extends Model
{
    use HasCompany, HasFactory, SoftDeletes;

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
