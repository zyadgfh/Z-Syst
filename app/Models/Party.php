<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Party extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type',
        'name',
        'email',
        'phone',
        'due',
        'image',
        'status',
        'address',
        'business_id',
        'opening_balance',
    ];

    public function sales_dues(): HasMany
    {
        return $this->hasMany(Sale::class)->where('dueAmount', '>', 0);
    }

    public function purchases_dues(): HasMany
    {
        return $this->hasMany(Purchase::class)->where('dueAmount', '>', 0);
    }

    public function activities()
    {
        return $this->hasMany(DoctorActivity::class, 'doctor_id');
    }

    public function attentionScores()
    {
        return $this->hasMany(DoctorAttentionScore::class, 'doctor_id');
    }

    public function attentionAlerts()
    {
        return $this->hasMany(DoctorAttentionAlert::class, 'doctor_id');
    }

    public function latestAttentionScore()
    {
        return $this->hasOne(DoctorAttentionScore::class, 'doctor_id')->latest('calculated_date');
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'due' => 'double',
    ];
}
