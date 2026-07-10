<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = array(
            array('subscriptionName' => 'Free', 'duration' => '7', 'offerPrice' => NULL, 'subscriptionPrice' => '0.00', 'status' => '1', 'allow_multibranch' => '0', 'features' => '{"features_features_features_features_0":["Lifetime Free  Update","1"],"features_features_features_features_1":["6 months technical support","1"],"features_features_features_features_2":["WhatsApp & Skype support","1"],"features_features_features_features_3":["Live support","1"],"features_features_features_features_4":["Free installation"],"features_features_features_features_5":["Free installation Cpanel"],"features_features_features_features_6":["Advance Remote Support"]}', 'created_at' => '2024-06-05 00:08:12', 'updated_at' => '2024-06-05 15:58:15'),
            array('subscriptionName' => 'Standard', 'duration' => '30', 'offerPrice' => NULL, 'subscriptionPrice' => '10.00', 'status' => '1', 'allow_multibranch' => '1', 'features' => '{"features_features_features_0":["Lifetime Free  Update","1"],"features_features_features_1":["6 months technical support","1"],"features_features_features_2":["WhatsApp & Skype support","1"],"features_features_features_3":["Live support","1"],"features_features_features_4":["Free installation","1"],"features_features_features_5":["Free installation Cpanel"],"features_features_features_6":["Advance Remote Support"]}', 'created_at' => '2024-06-05 00:08:12', 'updated_at' => '2024-06-05 15:58:24'),
            array('subscriptionName' => 'Premium', 'duration' => '180', 'offerPrice' => '50.00', 'subscriptionPrice' => '60.00', 'status' => '1', 'allow_multibranch' => '1', 'features' => '{"features_features_0":["Lifetime Free  Update","1"],"features_features_1":["6 months technical support","1"],"features_features_2":["WhatsApp & Skype support","1"],"features_features_3":["Live support","1"],"features_features_4":["Free installation","1"],"features_features_5":["Free installation Cpanel","1"],"features_features_6":["Advance Remote Support","1"]}', 'created_at' => '2024-06-05 00:08:12', 'updated_at' => '2024-06-05 15:58:32')
        );

        Plan::insert($plans);
    }
}
