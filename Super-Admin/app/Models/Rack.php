<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Rack extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id',
        'name',
        'status',
    ];

    public function shelves()
    {
        return $this->belongsToMany(Shelf::class, 'rack_shelf');
    }
}
