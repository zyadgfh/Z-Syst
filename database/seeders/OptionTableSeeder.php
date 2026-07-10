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
        $options = array(
            array('key' => 'general','value' => '{"title":"Pharmacy Store","copy_right":"\\u00a9 2025 Acnoo, all rights reserved.","admin_footer_text":"Development By","admin_footer_link_text":"acnoo","admin_footer_link":"https:\\/\\/acnoo.com\\/","favicon":"uploads\\/25\\/02\\/1738492477-204.png","admin_logo":"uploads\\/25\\/02\\/1739264470-923.svg","frontend_logo":"uploads\\/25\\/02\\/1739264471-519.svg"}','status' => '1','created_at' => '2024-04-15 12:55:07','updated_at' => '2025-02-11 15:01:11'),
        );

        Option::insert($options);
    }
}
