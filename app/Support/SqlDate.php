<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Cross-database SQL date formatting helpers.
 *
 * The application runs on MySQL in production and sqlite in development /
 * tests. Raw MySQL-only functions (DATE_FORMAT) break under sqlite, so every
 * raw date projection must go through this helper.
 */
class SqlDate
{
    /**
     * Build a driver-appropriate SQL expression formatting a date column.
     *
     * $format uses MySQL DATE_FORMAT patterns (e.g. '%Y-%m-%d'); sqlite's
     * strftime understands the same patterns, so no translation is needed.
     */
    public static function format(string $column, string $format): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return sprintf("strftime('%s', %s)", $format, $column);
        }

        return sprintf("DATE_FORMAT(%s, '%s')", $column, $format);
    }
}
