<?php

namespace Database\Seeders;

use App\Models\PosAppInterface;
use Illuminate\Database\Seeder;

class InterfaceSeeder extends Seeder
{
    public function run(): void
    {
        $pos_app_interfaces = array(
            array('image' => 'uploads/24/06/1717395096-735.png', 'status' => '1', 'created_at' => '2024-04-16 22:18:51', 'updated_at' => '2024-06-03 18:11:36'),
            array('image' => 'uploads/24/06/1717395110-964.png', 'status' => '1', 'created_at' => '2024-04-16 22:19:43', 'updated_at' => '2024-06-03 18:11:50'),
            array('image' => 'uploads/24/06/1717395121-241.png', 'status' => '1', 'created_at' => '2024-04-18 20:56:07', 'updated_at' => '2024-06-03 18:12:01'),
            array('image' => 'uploads/24/06/1717395131-322.png', 'status' => '1', 'created_at' => '2024-04-18 20:56:17', 'updated_at' => '2024-06-03 18:12:11'),
            array('image' => 'uploads/24/06/1717396714-782.png', 'status' => '1', 'created_at' => '2024-06-03 18:38:34', 'updated_at' => '2024-06-03 18:38:34'),
            array('image' => 'uploads/24/06/1717396725-163.png', 'status' => '1', 'created_at' => '2024-06-03 18:38:45', 'updated_at' => '2024-06-03 18:38:45'),
            array('image' => 'uploads/24/06/1717396734-535.png', 'status' => '1', 'created_at' => '2024-06-03 18:38:54', 'updated_at' => '2024-06-03 18:38:54'),
            array('image' => 'uploads/24/06/1717396745-346.png', 'status' => '1', 'created_at' => '2024-06-03 18:39:05', 'updated_at' => '2024-06-03 18:39:05')
        );

        PosAppInterface::insert($pos_app_interfaces);
    }
}
