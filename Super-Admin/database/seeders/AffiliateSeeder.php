<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\AffiliateAddon\App\Models\Affiliate;

class AffiliateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $affiliates = array(
            array('id' => '1', 'user_id' => '5', 'ref_code' => 'user-123', 'balance' => '500.00', 'created_at' => '2025-05-28 10:58:20', 'updated_at' => '2025-05-28 10:58:20')
        );

        Affiliate::insert($affiliates);
    }
}
