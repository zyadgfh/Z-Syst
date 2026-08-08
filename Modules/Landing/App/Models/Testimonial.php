<?php

namespace Modules\Landing\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    use HasFactory;

    protected $fillable = [
        'text',
        'star',
        'client_name',
        'client_image',
        'work_at',
    ];
}
