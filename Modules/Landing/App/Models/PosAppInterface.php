<?php

namespace Modules\Landing\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PosAppInterface extends Model
{
    use HasFactory;

    protected $fillable = [
        'image',
        'status'
    ];
}
