<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Gateway;
use Illuminate\Database\Seeder;

class GatewaySeeder extends Seeder
{
    /**
     * Seed the checkout payment methods.
     *
     * Two things this seeder used to get wrong, both of which matter now that
     * manual payment is the only live path (config/payments.php):
     *
     * 1. It called Gateway::insert(), so every run appended a duplicate set.
     *    Nothing keyed on the gateway name, and checkout lists every row with
     *    status = 1 — so a second run meant every method twice on the payment
     *    screen. It is updateOrCreate() on `name` now, which makes it safe to
     *    re-run on production, where the table is currently empty.
     *
     * 2. It seeded all seven rows with status = '1', i.e. it switched on six
     *    gateway-hosted providers that have no credentials configured here and
     *    whose callbacks do not all prove the payment happened (RUNBOOK §10.4).
     *    Automatic rows now follow the payments.automatic_enabled switch and are
     *    off by default; the Manual row is always on, because it is the path the
     *    application runs on today.
     *
     * An automatic row left at status = 0 is inert, not broken: flipping the
     * switch on and re-running this seeder enables them together.
     */
    public function run(): void
    {
        $currency = Currency::first();
        $currencyId = $currency ? $currency->id : null;

        if (! $currencyId) {
            // Create a default USD currency if none exists
            $currencyId = Currency::create([
                'name' => 'US Dollar',
                'country_name' => 'United States',
                'code' => 'USD',
                'rate' => 1,
                'symbol' => '$',
                'position' => 'left',
                'status' => 1,
                'is_default' => 1,
            ])->id;
        }

        $automaticEnabled = (bool) config('payments.automatic_enabled');

        // Egyptian payment gateways with multi-tenant support
        $gateways = [
            ['name' => 'Vodafone Cash', 'currency_id' => $currencyId, 'mode' => 1, 'status' => $automaticEnabled, 'charge' => '0', 'image' => null, 'data' => '[]', 'manual_data' => '[]', 'is_manual' => '0', 'accept_img' => '0', 'namespace' => 'App\\Library\\VodafoneCash', 'phone_required' => 1, 'instructions' => 'Transfer money to Vodafone Cash number'],

            ['name' => 'Orange Cash', 'currency_id' => $currencyId, 'mode' => 1, 'status' => $automaticEnabled, 'charge' => '0', 'image' => null, 'data' => '[]', 'manual_data' => '[]', 'is_manual' => '0', 'accept_img' => '0', 'namespace' => 'App\\Library\\OrangeCash', 'phone_required' => 1, 'instructions' => 'Transfer money to Orange Cash number'],

            ['name' => 'InstaPay', 'currency_id' => $currencyId, 'mode' => 1, 'status' => $automaticEnabled, 'charge' => '0', 'image' => null, 'data' => '[]', 'manual_data' => '[]', 'is_manual' => '0', 'accept_img' => '0', 'namespace' => 'App\\Library\\InstaPay', 'phone_required' => 1, 'instructions' => 'Transfer money to InstaPay ID'],

            ['name' => 'Bank Card', 'currency_id' => $currencyId, 'mode' => 1, 'status' => $automaticEnabled, 'charge' => '0', 'image' => null, 'data' => '[]', 'manual_data' => '[]', 'is_manual' => '0', 'accept_img' => '0', 'namespace' => 'App\\Library\\BankCard', 'phone_required' => 0, 'instructions' => 'Transfer money to bank account'],

            ['name' => 'Fawry', 'currency_id' => $currencyId, 'mode' => 1, 'status' => $automaticEnabled, 'charge' => '0', 'image' => null, 'data' => '[]', 'manual_data' => '[]', 'is_manual' => '0', 'accept_img' => '0', 'namespace' => 'App\\Library\\Fawry', 'phone_required' => 0, 'instructions' => 'Pay using Fawry code'],

            ['name' => 'Cash Payment', 'currency_id' => $currencyId, 'mode' => 1, 'status' => $automaticEnabled, 'charge' => '0', 'image' => null, 'data' => '[]', 'manual_data' => '[]', 'is_manual' => '0', 'accept_img' => '0', 'namespace' => 'App\\Library\\CashPayment', 'phone_required' => 0, 'instructions' => 'Pay cash at branch'],

            // Manual payment: the customer uploads a receipt and names the bank
            // and transaction, a platform superadmin approves it. This is the
            // only row seeded enabled while automatic payments are off.
            ['name' => 'Manual', 'currency_id' => $currencyId, 'mode' => 1, 'status' => true, 'charge' => '0', 'image' => null, 'data' => '[]', 'manual_data' => '{"label":["Bank Name","Transaction ID"],"is_required":["1","1"]}', 'is_manual' => '1', 'accept_img' => '1', 'namespace' => null, 'phone_required' => 0, 'instructions' => null],
        ];

        foreach ($gateways as $gateway) {
            // Keyed on name so re-running updates in place. Timestamps are left
            // to Eloquent — the previous hardcoded 2024 values were rewritten on
            // every seed and made `updated_at` meaningless.
            Gateway::updateOrCreate(['name' => $gateway['name']], $gateway);
        }
    }
}
