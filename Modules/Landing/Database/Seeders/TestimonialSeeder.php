<?php

namespace Modules\Landing\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Landing\App\Models\Testimonial;

class TestimonialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $testimonials = [
            ['text' => 'Although this is well intentioned and the goal certainly is to reduce the quantity of these bothersome thoughts, the technique is', 'star' => '5', 'client_name' => 'Ronald Richards', 'client_image' => 'uploads/25/02/1738837827-35.svg', 'work_at' => 'Manager', 'created_at' => '2025-02-06 16:22:16', 'updated_at' => '2025-02-06 16:30:27'],
            ['text' => 'Although this is well intentioned and the goal certainly is to reduce the quantity of these bothersome thoughts, the technique is', 'star' => '5', 'client_name' => 'Savannah Nguyen', 'client_image' => 'uploads/25/02/1738837872-159.svg', 'work_at' => 'Quick Seba (CEO)', 'created_at' => '2025-02-06 16:22:16', 'updated_at' => '2025-02-06 16:31:12'],
            ['text' => 'Although this is well intentioned and the goal certainly is to reduce the quantity of these bothersome thoughts, the technique is', 'star' => '5', 'client_name' => 'Devon Lane', 'client_image' => 'uploads/25/02/1738837919-749.svg', 'work_at' => 'Manager', 'created_at' => '2025-02-06 16:22:16', 'updated_at' => '2025-02-06 16:31:59'],
            ['text' => 'Although this is well intentioned and the goal certainly is to reduce the quantity of these bothersome thoughts, the technique is', 'star' => '5', 'client_name' => 'Cody Fisher', 'client_image' => 'uploads/25/02/1738838108-917.svg', 'work_at' => 'Ceo Google.inc', 'created_at' => '2025-02-06 16:22:16', 'updated_at' => '2025-02-06 16:35:08'],
        ];
        Testimonial::insert($testimonials);
    }
}
