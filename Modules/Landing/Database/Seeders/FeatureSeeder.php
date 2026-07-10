<?php

namespace Modules\Landing\Database\Seeders;

use Modules\Landing\App\Models\Feature;
use Illuminate\Database\Seeder;

class FeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = array(
            array('title' => 'More Features...','bg_color' => '#F0E8FF','image' => 'uploads/25/02/1738834287-44.svg','status' => '1','created_at' => '2024-01-12 11:19:15','updated_at' => '2025-02-06 15:31:27'),
            array('title' => 'Multi Currency','bg_color' => '#FFF2E0','image' => 'uploads/25/02/1738834263-945.svg','status' => '1','created_at' => '2024-01-13 16:47:17','updated_at' => '2025-02-06 15:31:03'),
            array('title' => '100+ Languages','bg_color' => '#DCF3E7','image' => 'uploads/25/02/1739761116-24.svg','status' => '1','created_at' => '2024-01-14 16:47:42','updated_at' => '2025-02-06 15:30:12'),
            array('title' => 'Inventory Sales','bg_color' => '#DFF4FE','image' => 'uploads/25/02/1738834176-353.svg','status' => '1','created_at' => '2024-01-15 16:48:00','updated_at' => '2025-02-06 15:29:36'),
            array('title' => 'Reports','bg_color' => '#DFF4FE','image' => 'uploads/25/02/1738834145-561.svg','status' => '1','created_at' => '2024-01-16 16:48:20','updated_at' => '2025-02-06 15:29:05'),
            array('title' => 'Expiring','bg_color' => '#FFE7E2','image' => 'uploads/25/02/1738834120-507.svg','status' => '1','created_at' => '2024-01-17 16:48:57','updated_at' => '2025-02-06 15:28:40'),
            array('title' => 'Loss/Profit','bg_color' => '#F3E6FF','image' => 'uploads/25/02/1738834088-894.svg','status' => '1','created_at' => '2024-01-18 16:49:22','updated_at' => '2025-02-06 15:28:08'),
            array('title' => 'Stock','bg_color' => '#FBECE0','image' => 'uploads/25/02/1738834065-575.svg','status' => '1','created_at' => '2024-01-19 16:49:53','updated_at' => '2025-02-06 15:27:45'),
            array('title' => 'Ledger','bg_color' => '#E4FFEB','image' => 'uploads/25/02/1738834034-594.svg','status' => '1','created_at' => '2024-01-20 16:50:15','updated_at' => '2025-02-06 15:27:14'),
            array('title' => 'Sales List','bg_color' => '#FFF8D6','image' => 'uploads/25/02/1738834012-316.svg','status' => '1','created_at' => '2024-01-21 16:50:37','updated_at' => '2025-02-06 15:26:52'),
            array('title' => 'Purchase List','bg_color' => '#E1FFFD','image' => 'uploads/25/02/1738833988-693.svg','status' => '1','created_at' => '2024-01-22 16:51:05','updated_at' => '2025-02-06 15:26:28'),
            array('title' => 'Due List','bg_color' => '#F7ECFF','image' => 'uploads/25/02/1738833961-395.svg','status' => '1','created_at' => '2024-01-23 16:51:36','updated_at' => '2025-02-06 15:26:01'),
            array('title' => 'Products','bg_color' => '#FFF1E6','image' => 'uploads/25/02/1738984859-376.svg','status' => '1','created_at' => '2025-02-08 09:17:20','updated_at' => '2025-02-08 09:20:59'),
            array('title' => 'Purchase','bg_color' => '#E8F7EF','image' => 'uploads/25/02/1739444031-316.svg','status' => '1','created_at' => '2025-02-08 09:21:20','updated_at' => '2025-02-08 09:21:20'),
            array('title' => 'Parties','bg_color' => '#FFF0F4','image' => 'uploads/25/02/1739444014-290.svg','status' => '1','created_at' => '2025-02-08 09:21:44','updated_at' => '2025-02-08 09:21:44'),
            array('title' => 'Sales','bg_color' => '#ECEFFF','image' => 'uploads/25/02/1738984931-145.svg','status' => '1','created_at' => '2025-02-08 09:22:11','updated_at' => '2025-02-08 09:22:11')

         );

        Feature::insert($features);
    }
}
