<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
    ];

    public $timestamps = true;

    public static function get($key, $default = null)
    {
        $s = static::where('key', $key)->first();
        if (! $s) {
            return $default;
        }

        if ($s->type === 'encrypted') {
            try {
                return \Illuminate\Support\Facades\Crypt::decryptString($s->value);
            } catch (\Throwable $e) {
                return $default;
            }
        }

        return $s->value;
    }

    public static function setEncrypted($key, $plain)
    {
        $encrypted = \Illuminate\Support\Facades\Crypt::encryptString($plain);
        return static::updateOrCreate(['key' => $key], ['value' => $encrypted, 'type' => 'encrypted']);
    }
}
