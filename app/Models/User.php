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

    protected $fillable = [
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

    protected static function booted()
    {
        static::addGlobalScope('tenant', function ($query) {
            $user = request()->user();

            if (
                $user &&
                $user->role !== 'superadmin' &&
                !request()->is('admin/*')
            ) {
                $query->where('business_id', $user->business_id);
            }
        });
    }

    protected $hidden = [
        'password',
        'remember_token',
    ];

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
