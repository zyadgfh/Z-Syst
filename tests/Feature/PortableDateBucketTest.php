<?php

namespace Tests\Feature;

use App\Support\SqlDateBucket;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

class PortableDateBucketTest extends TestCase
{
    public function test_sqlite_iso_week_groups_year_boundaries_correctly(): void
    {
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $bucket = SqlDateBucket::expression('sample_date', 'week');
        $rows = DB::select("SELECT $bucket AS period FROM (SELECT '2021-01-01' AS sample_date)");
        $this->assertSame('2020-W53', $rows[0]->period);
    }

    public function test_invalid_period_is_rejected(): void
    {
        config(['database.default' => 'sqlite']);
        DB::purge('sqlite');
        $this->expectException(InvalidArgumentException::class);
        SqlDateBucket::expression('sample_date', 'invalid');
    }
}
