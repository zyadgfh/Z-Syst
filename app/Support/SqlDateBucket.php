<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/** Portable, fixed-format date grouping expressions for supported database drivers. */
class SqlDateBucket
{
    public static function expression(string $column, string $period): string
    {
        $column = DB::connection()->getQueryGrammar()->wrap($column);
        $driver = DB::connection()->getDriverName();
        $formats = [
            'mysql' => ['hour' => '%Y-%m-%d %H:00', 'day' => '%Y-%m-%d', 'week' => '%x-W%v', 'month' => '%Y-%m', 'year' => '%Y'],
            'pgsql' => ['hour' => 'YYYY-MM-DD HH24:00', 'day' => 'YYYY-MM-DD', 'week' => 'IYYY-"W"IW', 'month' => 'YYYY-MM', 'year' => 'YYYY'],
            'sqlite' => ['hour' => '%Y-%m-%d %H:00', 'day' => '%Y-%m-%d', 'month' => '%Y-%m', 'year' => '%Y'],
        ];

        if ($driver === 'sqlite' && $period === 'week') {
            $thursday = "date($column, '-3 days', 'weekday 4')";
            return "strftime('%Y', $thursday) || '-W' || printf('%02d', (CAST(strftime('%j', $thursday) AS INTEGER) - 1) / 7 + 1)";
        }
        if (! isset($formats[$driver][$period])) {
            throw new InvalidArgumentException('Unsupported date grouping or database driver');
        }
        $format = $formats[$driver][$period];

        return match ($driver) {
            'pgsql' => "to_char($column, '$format')",
            'mysql' => "DATE_FORMAT($column, '$format')",
            'sqlite' => "strftime('$format', $column)",
        };
    }
}
