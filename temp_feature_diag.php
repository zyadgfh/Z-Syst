<?php
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$request = Illuminate\Http\Request::create('/features','GET');
$response = $app->make(Illuminate\Contracts\Http\Kernel::class)->handle($request);
$content = $response->getContent();
file_put_contents('temp_features_response.html', $content);
$strings = [
    'PharmaSync Features',
    'Offline & Remote Access',
    'Inventory & Product Intelligence',
];
foreach ($strings as $s) {
    $pos = strpos($content, $s);
    printf("%s: %s\n", $s, $pos === false ? 'NOT FOUND' : "FOUND at $pos");
}
