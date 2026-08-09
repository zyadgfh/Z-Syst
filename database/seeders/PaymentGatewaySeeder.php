<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\CompanyPaymentGateway;
use Illuminate\Database\Seeder;

class PaymentGatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing businesses to create payment gateways for
        $businesses = Business::all();

        if ($businesses->isEmpty()) {
            $this->command->warn('No businesses found. Please create businesses first.');

            return;
        }

        foreach ($businesses as $business) {
            // Create Cash payment gateway (always available)
            CompanyPaymentGateway::firstOrCreate(
                [
                    'company_id' => $business->id,
                    'branch_id' => null,
                    'gateway_type' => CompanyPaymentGateway::GATEWAY_CASH,
                ],
                [
                    'is_active' => true,
                    'config_data' => json_encode([
                        'require_verification' => true,
                        'allow_partial_payment' => false,
                        'auto_complete' => true,
                    ]),
                    'branch_config_data' => null,
                    'transaction_fee' => 0,
                    'transaction_fee_type' => 'percentage',
                    'sort_order' => 6,
                    'notes' => 'Cash payment for in-store transactions',
                ]
            );

            // Create Vodafone Cash gateway (with sandbox config for testing)
            CompanyPaymentGateway::firstOrCreate(
                [
                    'company_id' => $business->id,
                    'branch_id' => null,
                    'gateway_type' => CompanyPaymentGateway::GATEWAY_VODAFONE_CASH,
                ],
                [
                    'is_active' => false, // Disabled by default until configured
                    'config_data' => json_encode([
                        'merchant_id' => env('VODAFONE_CASH_MERCHANT_ID', ''),
                        'api_key' => env('VODAFONE_CASH_API_KEY', ''),
                        'api_secret' => env('VODAFONE_CASH_API_SECRET', ''),
                        'environment' => env('VODAFONE_CASH_ENVIRONMENT', 'sandbox'),
                    ]),
                    'branch_config_data' => null,
                    'transaction_fee' => 2.5,
                    'transaction_fee_type' => 'percentage',
                    'sort_order' => 1,
                    'notes' => 'Vodafone Cash mobile wallet payments',
                ]
            );

            // Create Bank Card gateway
            CompanyPaymentGateway::firstOrCreate(
                [
                    'company_id' => $business->id,
                    'branch_id' => null,
                    'gateway_type' => CompanyPaymentGateway::GATEWAY_BANK_CARD,
                ],
                [
                    'is_active' => false, // Disabled by default until configured
                    'config_data' => json_encode([
                        'merchant_id' => env('BANK_CARD_MERCHANT_ID', ''),
                        'api_key' => env('BANK_CARD_API_KEY', ''),
                        'api_secret' => env('BANK_CARD_API_SECRET', ''),
                        'environment' => env('BANK_CARD_ENVIRONMENT', 'sandbox'),
                        'accept_meeza' => env('BANK_CARD_ACCEPT_MEEZA', false),
                        'accept_visamastercard' => env('BANK_CARD_ACCEPT_VISAMASTERCARD', true),
                    ]),
                    'branch_config_data' => null,
                    'transaction_fee' => 1.5,
                    'transaction_fee_type' => 'percentage',
                    'sort_order' => 2,
                    'notes' => 'Bank card payments (Visa, Mastercard, Meeza)',
                ]
            );

            // Create Fawry gateway
            CompanyPaymentGateway::firstOrCreate(
                [
                    'company_id' => $business->id,
                    'branch_id' => null,
                    'gateway_type' => CompanyPaymentGateway::GATEWAY_FAWRY,
                ],
                [
                    'is_active' => false, // Disabled by default until configured
                    'config_data' => json_encode([
                        'merchant_code' => env('FAWRY_MERCHANT_CODE', ''),
                        'security_key' => env('FAWRY_SECURITY_KEY', ''),
                        'environment' => env('FAWRY_ENVIRONMENT', 'sandbox'),
                    ]),
                    'branch_config_data' => null,
                    'transaction_fee' => 2.0,
                    'transaction_fee_type' => 'percentage',
                    'sort_order' => 3,
                    'notes' => 'Fawry payment network',
                ]
            );

            // Create Orange Cash gateway
            CompanyPaymentGateway::firstOrCreate(
                [
                    'company_id' => $business->id,
                    'branch_id' => null,
                    'gateway_type' => CompanyPaymentGateway::GATEWAY_ORANGE_CASH,
                ],
                [
                    'is_active' => false, // Disabled by default until configured
                    'config_data' => json_encode([
                        'merchant_id' => env('ORANGE_CASH_MERCHANT_ID', ''),
                        'api_key' => env('ORANGE_CASH_API_KEY', ''),
                        'api_secret' => env('ORANGE_CASH_API_SECRET', ''),
                        'environment' => env('ORANGE_CASH_ENVIRONMENT', 'sandbox'),
                    ]),
                    'branch_config_data' => null,
                    'transaction_fee' => 2.5,
                    'transaction_fee_type' => 'percentage',
                    'sort_order' => 4,
                    'notes' => 'Orange Cash mobile wallet payments',
                ]
            );

            // Create InstaPay gateway
            CompanyPaymentGateway::firstOrCreate(
                [
                    'company_id' => $business->id,
                    'branch_id' => null,
                    'gateway_type' => CompanyPaymentGateway::GATEWAY_INSTAPAY,
                ],
                [
                    'is_active' => false, // Disabled by default until configured
                    'config_data' => json_encode([
                        'merchant_id' => env('INSTAPAY_MERCHANT_ID', ''),
                        'api_key' => env('INSTAPAY_API_KEY', ''),
                        'api_secret' => env('INSTAPAY_API_SECRET', ''),
                        'environment' => env('INSTAPAY_ENVIRONMENT', 'sandbox'),
                    ]),
                    'branch_config_data' => null,
                    'transaction_fee' => 1.0,
                    'transaction_fee_type' => 'percentage',
                    'sort_order' => 5,
                    'notes' => 'InstaPay instant payment system',
                ]
            );

            // Create branch-specific payment gateway for first business (for testing)
            if ($business->id === 1) {
                CompanyPaymentGateway::firstOrCreate(
                    [
                        'company_id' => $business->id,
                        'branch_id' => 1, // Assuming main branch has ID 1
                        'gateway_type' => CompanyPaymentGateway::GATEWAY_CASH,
                    ],
                    [
                        'is_active' => true,
                        'config_data' => json_encode([
                            'require_verification' => false, // Branch-specific override
                            'allow_partial_payment' => true, // Branch-specific override
                            'auto_complete' => true,
                        ]),
                        'branch_config_data' => json_encode([
                            'require_verification' => false,
                            'allow_partial_payment' => true,
                        ]),
                        'transaction_fee' => 0,
                        'transaction_fee_type' => 'percentage',
                        'sort_order' => 1,
                        'notes' => 'Cash payment with branch-specific settings',
                    ]
                );
            }
        }

        $this->command->info('Payment gateway seeder completed successfully.');
        $this->command->info('Note: All gateways except Cash are disabled by default.');
        $this->command->info('Please configure API credentials in .env and enable gateways via admin panel.');
    }
}
