<?php

namespace Modules\Landing\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Message extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'phone', 'email', 'company_name', 'message'];
}
