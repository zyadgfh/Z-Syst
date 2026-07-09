<?php

use App\Models\Branch;
use App\Models\Company;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';

$app = require __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

// Ensure testing DB is used
config(['database.default' => 'sqlite']);
config(['database.connections.sqlite.database' => ':memory:']);

$app->make(Kernel::class)->call('migrate:fresh');

$company = Company::factory()->create();
$user = User::factory()->create(['company_id' => $company->id, 'role' => 'admin']);
$branch = Branch::factory()->create(['company_id' => $company->id]);
$supplier = Supplier::factory()->create(['company_id' => $company->id]);

PurchaseOrder::factory()->count(3)->create([
    'company_id' => $company->id,
    'supplier_id' => $supplier->id,
    'branch_id' => $branch->id,
]);

echo 'purchase_orders='.PurchaseOrder::count().PHP_EOL;
$rows = PurchaseOrder::all();
foreach ($rows as $row) {
    echo $row->id.' company_id='.$row->company_id.' supplier_id='.$row->supplier_id.' branch_id='.$row->branch_id."\n";
}
