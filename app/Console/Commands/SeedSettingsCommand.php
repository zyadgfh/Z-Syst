<?php

namespace App\Console\Commands;

use App\Services\Settings\SettingsService;
use Illuminate\Console\Command;

class SeedSettingsCommand extends Command
{
    protected $signature = 'settings:seed';

    protected $description = 'Seed all application setting definitions and default permissions';

    public function handle(SettingsService $settingsService): int
    {
        $this->info('Seeding application setting definitions...');

        $results = $settingsService->seedDefaults();

        $created = count(array_filter($results, fn($r) => $r === 'created'));
        $updated = count(array_filter($results, fn($r) => $r === 'updated'));

        $this->info("Done! Created: {$created}, Updated: {$updated}");

        // Seed permissions
        $this->info('Seeding settings permissions...');

        $permissions = [
            'settings-view',
            'settings-edit',
            'settings-system-edit',
            'settings-organization-edit',
            'settings-branch-edit',
            'settings-role-edit',
            'settings-user-edit',
        ];

        foreach ($permissions as $permission) {
            \Spatie\Permission\Models\Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $this->info('Permissions seeded successfully!');

        return Command::SUCCESS;
    }
}
