<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Business;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing businesses to create branches for
        $businesses = Business::all();

        if ($businesses->isEmpty()) {
            $this->command->warn('No businesses found. Please create businesses first.');

            return;
        }

        foreach ($businesses as $business) {
            // Create main branch for each business
            Branch::firstOrCreate(
                [
                    'company_id' => $business->id,
                    'branch_code' => $this->generateBranchCode($business->companyName, 'MAIN'),
                ],
                [
                    'branch_name' => 'Main Branch',
                    'address' => $business->address ?? 'Default Address',
                    'phone' => $business->phoneNumber ?? 'Default Phone',
                    'email' => null,
                    'is_active' => true,
                    'location_lat' => null,
                    'location_lng' => null,
                    'settings' => json_encode([
                        'business_hours' => [
                            'monday' => ['09:00', '21:00'],
                            'tuesday' => ['09:00', '21:00'],
                            'wednesday' => ['09:00', '21:00'],
                            'thursday' => ['09:00', '21:00'],
                            'friday' => ['09:00', '21:00'],
                            'saturday' => ['09:00', '21:00'],
                            'sunday' => ['09:00', '21:00'],
                        ],
                        'tax_rate' => 14.0,
                        'currency' => 'EGP',
                    ]),
                ]
            );

            // Create additional branches for some businesses (for testing multi-branch scenarios)
            if ($business->id <= 2) {
                Branch::firstOrCreate(
                    [
                        'company_id' => $business->id,
                        'branch_code' => $this->generateBranchCode($business->companyName, 'BRANCH2'),
                    ],
                    [
                        'branch_name' => 'Branch 2',
                        'address' => 'Secondary Location Address',
                        'phone' => $business->phoneNumber ?? 'Secondary Phone',
                        'email' => null,
                        'is_active' => true,
                        'location_lat' => 30.0444 + ($business->id * 0.01),
                        'location_lng' => 31.2357 + ($business->id * 0.01),
                        'settings' => json_encode([
                            'business_hours' => [
                                'monday' => ['08:00', '22:00'],
                                'tuesday' => ['08:00', '22:00'],
                                'wednesday' => ['08:00', '22:00'],
                                'thursday' => ['08:00', '22:00'],
                                'friday' => ['08:00', '22:00'],
                                'saturday' => ['08:00', '22:00'],
                                'sunday' => ['10:00', '20:00'],
                            ],
                            'tax_rate' => 14.0,
                            'currency' => 'EGP',
                        ]),
                    ]
                );
            }
        }

        $this->command->info('Branch seeder completed successfully.');
    }

    /**
     * Generate a unique branch code based on company name and branch type.
     */
    private function generateBranchCode(string $companyName, string $branchType): string
    {
        // Extract initials from company name
        $initials = '';
        $words = explode(' ', strtoupper($companyName));
        foreach ($words as $word) {
            $initials .= substr($word, 0, 1);
        }

        // Limit to 3 characters
        $initials = substr($initials, 0, 3);

        // Add branch type suffix
        $suffix = match ($branchType) {
            'MAIN' => '001',
            'BRANCH2' => '002',
            default => '999',
        };

        return $initials.'-'.$suffix;
    }
}
