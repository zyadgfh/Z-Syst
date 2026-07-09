<?php

require __DIR__ . '/vendor/autoload.php';

$app = require __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Ensure testing DB is used
config(['database.default' => 'sqlite']);
config(['database.connections.sqlite.database' => ':memory:']);

$app->make(Illuminate\Contracts\Console\Kernel::class)->call('migrate:fresh');

$company = App\Models\Company::factory()->create();
$user = App\Models\User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
$branch = App\Models\Branch::factory()->create(['company_id' => $company->id]);
$supplier = App\Models\Supplier::factory()->create(['company_id' => $company->id]);

App\Models\PurchaseOrder::factory()->count(3)->create([
    'company_id' => $company->id,
    'supplier_id' => $supplier->id,
    'branch_id' => $branch->id,
]);

echo 'purchase_orders=' . App\Models\PurchaseOrder::count() . PHP_EOL;
$rows = App\Models\PurchaseOrder::all();
foreach ($rows as $row) {
    echo $row->id . ' company_id=' . $row->company_id . ' supplier_id=' . $row->supplier_id . ' branch_id=' . $row->branch_id . "\n";
}
