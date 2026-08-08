<?php

namespace Modules\Landing\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Landing\App\Models\PosAppInterface;

class InterfaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pos_app_interfaces = [
            ['id' => '1', 'image' => 'uploads/25/02/1738837202-296.svg', 'status' => '1', 'created_at' => '2024-04-16 16:18:51', 'updated_at' => '2025-02-06 16:20:02'],
            ['id' => '2', 'image' => 'uploads/25/02/1738837191-92.svg', 'status' => '1', 'created_at' => '2024-04-16 16:19:43', 'updated_at' => '2025-02-06 16:19:51'],
            ['id' => '3', 'image' => 'uploads/25/02/1738837179-166.svg', 'status' => '1', 'created_at' => '2024-04-18 14:56:07', 'updated_at' => '2025-02-06 16:19:39'],
            ['id' => '4', 'image' => 'uploads/25/02/1738837165-952.svg', 'status' => '1', 'created_at' => '2024-04-18 14:56:17', 'updated_at' => '2025-02-06 16:19:25'],
            ['id' => '5', 'image' => 'uploads/25/02/1739075653-36.svg', 'status' => '1', 'created_at' => '2025-02-09 10:34:14', 'updated_at' => '2025-02-09 10:34:14'],
            ['id' => '6', 'image' => 'uploads/25/02/1739075666-116.svg', 'status' => '1', 'created_at' => '2025-02-09 10:34:26', 'updated_at' => '2025-02-09 10:34:26'],
            ['id' => '7', 'image' => 'uploads/25/02/1739075674-934.svg', 'status' => '1', 'created_at' => '2025-02-09 10:34:34', 'updated_at' => '2025-02-09 10:34:34'],
            ['id' => '8', 'image' => 'uploads/25/02/1739075683-891.svg', 'status' => '1', 'created_at' => '2025-02-09 10:34:43', 'updated_at' => '2025-02-09 10:34:43'],
        ];

        PosAppInterface::insert($pos_app_interfaces);
    }
}
