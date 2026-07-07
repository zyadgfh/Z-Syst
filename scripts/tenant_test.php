<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;

$manager = app(\App\Services\TenantManager::class);
$req = Request::create('/', 'GET', [], [], [], ['HTTP_HOST' => 'demo.localhost']);
$company = $manager->resolveFromRequest($req);
echo 'Resolved company id: ' . ($company?->id ?? 'null') . PHP_EOL;
