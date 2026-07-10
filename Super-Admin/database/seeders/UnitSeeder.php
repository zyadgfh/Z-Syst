<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = array(
            array('unitName' => 'kg', 'business_id' => '1', 'status' => '1', 'created_at' => '2024-11-05 15:55:24', 'updated_at' => '2024-11-05 15:55:24'),
            array('unitName' => 'Piece (pcs)', 'business_id' => '1', 'status' => '1', 'created_at' => '2025-08-11 17:30:10', 'updated_at' => '2025-08-11 17:30:10'),
            array('unitName' => 'Kilogram (kg)', 'business_id' => '1', 'status' => '1', 'created_at' => '2025-08-11 17:30:19', 'updated_at' => '2025-08-11 17:30:19'),
            array('unitName' => 'Kilogram (kg)', 'business_id' => '1', 'status' => '1', 'created_at' => '2025-08-11 17:30:29', 'updated_at' => '2025-08-11 17:30:29'),
            array('unitName' => 'Liter (L)', 'business_id' => '1', 'status' => '1', 'created_at' => '2025-08-11 17:31:08', 'updated_at' => '2025-08-11 17:31:08'),
            array('unitName' => 'Milliliter (ml)', 'business_id' => '1', 'status' => '1', 'created_at' => '2025-08-11 17:31:15', 'updated_at' => '2025-08-11 17:31:15'),
            array('unitName' => 'Box', 'business_id' => '1', 'status' => '1', 'created_at' => '2025-08-11 17:31:25', 'updated_at' => '2025-08-11 17:31:25'),
            array('unitName' => 'Pack', 'business_id' => '1', 'status' => '1', 'created_at' => '2025-08-11 17:31:33', 'updated_at' => '2025-08-11 17:31:33'),
            array('unitName' => 'Dozen', 'business_id' => '1', 'status' => '1', 'created_at' => '2025-08-11 17:31:41', 'updated_at' => '2025-08-11 17:31:41'),
            array('unitName' => 'Meter (m)', 'business_id' => '1', 'status' => '1', 'created_at' => '2025-08-11 17:31:50', 'updated_at' => '2025-08-11 17:31:50')
        );

        Unit::insert($units);
    }
}
