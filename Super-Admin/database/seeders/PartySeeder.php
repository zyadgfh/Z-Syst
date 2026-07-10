<?php

namespace Database\Seeders;

use App\Models\Party;
use Illuminate\Database\Seeder;

class PartySeeder extends Seeder
{
    public function run(): void
    {
        $parties = array(
            array('name' => 'Amber Bush', 'business_id' => '1', 'email' => 'amber@mailinator.com', 'type' => 'Retailer', 'phone' => '01711234567', 'due' => '0.00', 'address' => 'House 12, Road 5, Dhanmondi, Dhaka', 'image' => 'uploads/25/08/1755065759-870.jpg', 'status' => '1', 'created_at' => '2024-11-05 10:52:09', 'updated_at' => '2025-08-13 12:15:59'),
            array('name' => 'Zoe Kidd', 'business_id' => '1', 'email' => 'Zoeid@mailinator.com', 'type' => 'Dealer', 'phone' => '01876543210', 'due' => '0.00', 'address' => 'Apartment 7B, Banani, Dhaka', 'image' => 'uploads/25/08/1755065746-342.jpg', 'status' => '1', 'created_at' => '2024-11-05 10:52:37', 'updated_at' => '2025-08-13 12:15:46'),
            array('name' => 'Porter Flynn', 'business_id' => '1', 'email' => 'porter@mailinator.com', 'type' => 'Wholesaler', 'phone' => '01912345678', 'due' => '670.00', 'address' => 'House 22, Road 9, Uttara, Dhaka', 'image' => 'uploads/25/08/1755065735-399.jpg', 'status' => '1', 'created_at' => '2024-11-05 10:52:51', 'updated_at' => '2025-08-13 12:15:35'),
            array('name' => 'Chase Farmer', 'business_id' => '1', 'email' => 'chase@mailinator.com', 'type' => 'Supplier', 'phone' => '01798765432', 'due' => '400.00', 'address' => 'Road 4, Mohammadpur, Dhaka', 'image' => 'uploads/25/08/1755065778-932.jpg', 'status' => '1', 'created_at' => '2024-11-05 10:53:13', 'updated_at' => '2025-08-13 12:16:57'),
            array('name' => 'Emery Mueller', 'business_id' => '1', 'email' => 'robys@mailinator.com', 'type' => 'Retailer', 'phone' => '01622334455', 'due' => '600.00', 'address' => 'House 18, Road 11, Mirpur, Dhaka', 'image' => 'uploads/25/08/1755065611-484.png', 'status' => '1', 'created_at' => '2025-08-13 12:05:11', 'updated_at' => '2025-08-13 12:13:32'),
            array('name' => 'Liam Carter', 'business_id' => '1', 'email' => 'liamc@mailinator.com', 'type' => 'Supplier', 'phone' => '01911223344', 'due' => '0.00', 'address' => 'House 5, Road 3, Gulshan, Dhaka', 'image' => 'uploads/25/08/1755065828-624.jpg', 'status' => '1', 'created_at' => '2025-08-10 09:30:00', 'updated_at' => '2025-08-13 12:17:08'),
            array('name' => 'Olivia Smith', 'business_id' => '1', 'email' => 'olivias@mailinator.com', 'type' => 'Supplier', 'phone' => '01833445566', 'due' => '120.00', 'address' => 'Road 7, Banani, Dhaka', 'image' => 'uploads/25/08/1755065837-663.jpg', 'status' => '1', 'created_at' => '2025-08-09 11:15:00', 'updated_at' => '2025-08-13 12:17:17'),
            array('name' => 'Noah Johnson', 'business_id' => '1', 'email' => 'noahj@mailinator.com', 'type' => 'Supplier', 'phone' => '01755667788', 'due' => '0.00', 'address' => 'House 10, Road 12, Dhanmondi, Dhaka', 'image' => 'uploads/25/08/1755065889-253.png', 'status' => '1', 'created_at' => '2025-08-08 10:50:00', 'updated_at' => '2025-08-13 12:18:09'),
            array('name' => 'Emma Brown', 'business_id' => '1', 'email' => 'emmab@mailinator.com', 'type' => 'Supplier', 'phone' => '01666778899', 'due' => '350.00', 'address' => 'Apartment 2A, Gulshan, Dhaka', 'image' => 'uploads/25/08/1755065802-431.png', 'status' => '1', 'created_at' => '2025-08-07 12:05:00', 'updated_at' => '2025-08-13 12:16:42'),
            array('name' => 'Mariya Davis', 'business_id' => '1', 'email' => 'mariya@mailinator.com', 'type' => 'Supplier', 'phone' => '01999887766', 'due' => '0.00', 'address' => 'House 3, Road 6, Uttara, Dhaka', 'image' => 'uploads/25/08/1755065867-823.png', 'status' => '1', 'created_at' => '2025-08-06 14:20:00', 'updated_at' => '2025-08-13 12:17:47'),
            array('name' => 'Sophia Wilson', 'business_id' => '1', 'email' => 'sophiaw@mailinator.com', 'type' => 'Retailer', 'phone' => '01888997766', 'due' => '0.00', 'address' => 'House 8, Road 9, Banani, Dhaka', 'image' => 'uploads/25/08/1755065621-290.png', 'status' => '1', 'created_at' => '2025-08-05 13:10:00', 'updated_at' => '2025-08-13 12:13:41'),
            array('name' => 'Jasmin Lee', 'business_id' => '1', 'email' => 'jasmin@mailinator.com', 'type' => 'Dealer', 'phone' => '01733445522', 'due' => '500.00', 'address' => 'Apartment 11B, Mirpur, Dhaka', 'image' => 'uploads/25/08/1755065649-950.png', 'status' => '1', 'created_at' => '2025-08-04 15:00:00', 'updated_at' => '2025-08-13 12:14:09'),
            array('name' => 'Ava Martinez', 'business_id' => '1', 'email' => 'avam@mailinator.com', 'type' => 'Wholesaler', 'phone' => '01644556677', 'due' => '0.00', 'address' => 'House 6, Road 4, Gulshan, Dhaka', 'image' => 'uploads/25/08/1755065659-826.png', 'status' => '1', 'created_at' => '2025-08-03 16:30:00', 'updated_at' => '2025-08-13 12:14:19'),
            array('name' => 'William Garcia', 'business_id' => '1', 'email' => 'williamg@mailinator.com', 'type' => 'Retailer', 'phone' => '01922334455', 'due' => '1000.00', 'address' => 'House 14, Road 2, Dhanmondi, Dhaka', 'image' => 'uploads/25/08/1755065667-145.png', 'status' => '1', 'created_at' => '2025-08-02 11:45:00', 'updated_at' => '2025-08-13 12:14:27'),
            array('name' => 'Isabella Martinez', 'business_id' => '1', 'email' => 'isabellam@mailinator.com', 'type' => 'Dealer', 'phone' => '01877665544', 'due' => '0.00', 'address' => 'Apartment 5C, Banani, Dhaka', 'image' => 'uploads/25/08/1755065724-633.png', 'status' => '1', 'created_at' => '2025-08-01 10:10:00', 'updated_at' => '2025-08-13 12:15:24')
        );

        Party::insert($parties);
    }
}
