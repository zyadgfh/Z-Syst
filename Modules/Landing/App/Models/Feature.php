<?php

namespace Modules\Landing\App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Landing\Database\Factories\FeatureFactory;

class Feature extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'bg_color',
        'image',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    protected static function newFactory()
    {
        return FeatureFactory::new();
    }
}
