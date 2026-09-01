<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountType extends Model
{
    public $timestamps = false;

    protected $fillable = ['name', 'code', 'is_debit_positive'];

    protected $casts = [
        'is_debit_positive' => 'boolean',
    ];

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    /**
     * Standard account types.
     */
    public const ASSET = 'Asset';
    public const LIABILITY = 'Liability';
    public const EQUITY = 'Equity';
    public const REVENUE = 'Revenue';
    public const EXPENSE = 'Expense';

    public static function seed(): void
    {
        $types = [
            ['name' => self::ASSET,     'code' => 'A', 'is_debit_positive' => true],
            ['name' => self::LIABILITY, 'code' => 'L', 'is_debit_positive' => false],
            ['name' => self::EQUITY,    'code' => 'E', 'is_debit_positive' => false],
            ['name' => self::REVENUE,   'code' => 'R', 'is_debit_positive' => false],
            ['name' => self::EXPENSE,   'code' => 'X', 'is_debit_positive' => true],
        ];

        foreach ($types as $type) {
            static::updateOrCreate(['name' => $type['name']], $type);
        }
    }
}
