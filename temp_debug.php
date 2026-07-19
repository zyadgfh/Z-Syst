<?php
require 'vendor/autoload.php';
 = require 'bootstrap/app.php';
->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
 = Illuminate\Http\Request::create('/api/v1/reports/sales', 'GET');
 = ->handle();
echo ->getStatusCode();
echo "\n";
echo ->getContent();
