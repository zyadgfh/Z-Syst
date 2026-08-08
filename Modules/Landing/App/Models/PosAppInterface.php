<?php

namespace Modules\Landing\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PosAppInterface extends Model
{
    use HasFactory;

    protected $fillable = [
        'image',
        'status',
    ];
}
