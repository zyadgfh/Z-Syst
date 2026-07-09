<?php

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Services\TenantManager;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;

$manager = app(TenantManager::class);
$req = Request::create('/', 'GET', [], [], [], ['HTTP_HOST' => 'demo.localhost']);
$company = $manager->resolveFromRequest($req);
echo 'Resolved company id: '.($company?->id ?? 'null').PHP_EOL;
