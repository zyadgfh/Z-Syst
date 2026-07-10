<?php

namespace Modules\Landing\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Feature extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'bg_color',
        'image',
        'status',
    ];
}
