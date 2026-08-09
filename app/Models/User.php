<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'business_id',
        'name',
        'role',
        'email',
        'phone',
        'image',
        'lang',
        'status',
        'password',
        'visibility',
        'remember_token',
        'email_verified_at',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        // Only apply tenant scope in non-admin contexts
        static::addGlobalScope('tenant', function ($query) {
            if (auth()->check() &&
                auth()->user()->role !== 'superadmin' &&
                ! request()->is('admin/*')) {
                $query->where('business_id', auth()->user()->business_id);
            }
        });
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'password' => 'hashed',
        'visibility' => 'json',
        'email_verified_at' => 'datetime',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
