<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdvertiseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $banners = array(
            array('name' => 'Special offers','imageUrl' => 'uploads/25/02/1739422328-162.svg','status' => '1','created_at' => '2025-02-12 22:54:44','updated_at' => '2025-02-13 10:52:08'),
            array('name' => 'Super Sales Up to 30% OFF','imageUrl' => 'uploads/25/02/1739379295-429.svg','status' => '1','created_at' => '2025-02-12 22:54:55','updated_at' => '2025-02-13 10:41:00'),
            array('name' => 'Pharmaceutical Medicine','imageUrl' => 'uploads/25/02/1739422308-651.svg','status' => '1','created_at' => '2025-02-12 22:55:08','updated_at' => '2025-02-13 10:51:49')
        );

        Banner::insert($banners);
    }
}
