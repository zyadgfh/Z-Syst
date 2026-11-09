<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;

class PatientMed extends Model
{
    
    use TenantScoped;
protected $fillable = [
        'business_id', 'party_id', 'product_id', 'chronic', 'days_supply',
        'last_filled_at', 'next_refill_at', 'active', 'notes',
    ];

    protected $casts = [
        'chronic' => 'boolean', 'active' => 'boolean',
        'last_filled_at' => 'datetime', 'next_refill_at' => 'datetime',
    ];

    public function party() { return $this->belongsTo(Party::class); }
    public function product() { return $this->belongsTo(Product::class); }
}
