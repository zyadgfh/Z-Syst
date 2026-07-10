<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Shelf extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'name',
        'status',
    ];

    public function racks()
    {
        return $this->belongsToMany(Rack::class, 'rack_shelf');
    }
}
