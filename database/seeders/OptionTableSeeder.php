<?php

namespace Database\Seeders;

use App\Models\Option;
use Illuminate\Database\Seeder;

class OptionTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $options = [
            ['key' => 'general', 'value' => '{"title":"Z-Syst Pharmacy","copy_right":"\\u00a9 2025 Z-Syst, all rights reserved.","admin_footer_text":"Development By","admin_footer_link_text":"Z-Syst","admin_footer_link":"https:\\/\\/z-syst.com\\/","favicon":"assets/images/logo/favicon.png","admin_logo":"assets/images/logo/logo.png","frontend_logo":"assets/images/logo/logo.png"}', 'status' => '1', 'created_at' => '2024-04-15 12:55:07', 'updated_at' => '2025-02-11 15:01:11'],
        ];

        Option::insert($options);
    }
}
