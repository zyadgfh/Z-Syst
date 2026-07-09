<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Permission;
use App\Models\Product;
use App\Models\ProductStock;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SampleSaleSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first() ?? Company::create(['name' => 'Demo Company']);

        $branch = Branch::first();
        if (! $branch) {
            $branch = Branch::create([
                'company_id' => $company->id,
                'name' => 'Main Branch',
                'address' => 'Default',
            ]);
        }

        $product = Product::create([
            'company_id' => $company->id,
            'product_category_id' => null,
            'sku' => 'TEST-'.strtoupper(Str::random(6)),
            'name' => 'Sample Painkiller',
            'slug' => 'sample-painkiller',
            'description' => 'Sample product for testing sales',
            'cost_price' => 5.00,
            'retail_price' => 10.00,
            'track_inventory' => true,
            'is_active' => true,
        ]);

        $stock = ProductStock::create([
            'company_id' => $company->id,
            'product_id' => $product->id,
            'branch_id' => $branch->id,
            'quantity' => 100,
            'reorder_level' => 10,
            'reorder_quantity' => 50,
            'batch_number' => 'BATCH-001',
            'expiry_date' => null,
            'is_active' => true,
        ]);

        $user = User::where('email', 'cashier+test@example.com')->first();
        if (! $user) {
            $user = User::create([
                'name' => 'Test Cashier',
                'email' => 'cashier+test@example.com',
                'password' => bcrypt('password'),
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'role' => 'cashier',
            ]);
        }

        $role = Role::where('slug', 'cashier')->first();
        if (! $role) {
            $role = Role::create([
                'name' => 'Cashier',
                'slug' => 'cashier',
                'description' => 'Point of sale operator',
                'color_badge' => '#D97706',
                'priority' => 50,
                'is_system' => false,
                'status' => true,
            ]);
        }

        $permission = Permission::where('slug', 'sales.create')->first();
        if (! $permission) {
            $permission = Permission::create([
                'name' => 'Create Sale',
                'slug' => 'sales.create',
                'module' => 'Sales',
                'group' => 'Operations',
                'sort_order' => 2,
                'status' => true,
            ]);
        }

        // Attach permission to role if not attached
        if (! $role->hasPermission('sales.create')) {
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }

        // Assign role to user
        $user->assignRole($role);

        $token = $user->createToken('sample-sales-token');

        $this->command->info('Seeded sample product: ID='.$product->id);
        $this->command->info('Seeded sample stock: ID='.$stock->id.' qty='.$stock->quantity);
        $this->command->info('Created test user: '.$user->email.' password: password');
        $this->command->info('SANCTUM_TOKEN: '.$token->plainTextToken);
    }
}
