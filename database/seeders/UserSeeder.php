<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = array(
            array('business_id' => 1,'email' => 'acnooteam@gmail.com','name' => 'Acnoo Team','role' => 'shop-owner','phone' => '+8801712022529','image' => NULL,'lang' => 'en','visibility' => NULL,'status' => NULL,'password' => bcrypt('123456'),'email_verified_at' => NULL,'remember_token' => NULL,'created_at' => now(),'updated_at' => now()),
        );

        User::insert($users);
    }
}
