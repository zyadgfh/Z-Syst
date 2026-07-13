<?php
$root = realpath(__DIR__ . '/..');
require $root . '/vendor/autoload.php';

use Illuminate\Support\Str;

function getTableName(string $filename): string
{
    $content = file_get_contents($filename);
    if (preg_match('/\$table\s*=\s*["\']([^"\']+)["\']/', $content, $matches)) {
        return $matches[1];
    }
    return '';
}

$models = [];
foreach (glob($root . '/app/Models/*.php') as $file) {
    $name = basename($file, '.php');
    $table = getTableName($file);
    if ($table === '') {
        $table = Str::snake(Str::plural($name));
    }
    $models[$name] = ['table' => $table, 'file' => $file];
}

$migs = [];
foreach (glob($root . '/database/migrations/*.php') as $file) {
    $content = file_get_contents($file);
    if (preg_match_all('/Schema::create\(\s*["\']([^"\']+)["\']/', $content, $matches)) {
        foreach ($matches[1] as $table) {
            $migs[$table] = basename($file);
        }
    }
}

$facs = array_flip(array_map(fn($path) => basename($path), glob($root . '/database/factories/*.php')));
$seeds = array_flip(array_map(fn($path) => basename($path), glob($root . '/database/seeders/*.php')));

echo "model,table,migration,factory,seeder\n";
foreach ($models as $name => $info) {
    $table = $info['table'];
    $migration = $migs[$table] ?? '';
    $factory = isset($facs[$name . 'Factory.php']) ? 'yes' : 'no';
    $seeder = isset($seeds[$name . 'Seeder.php']) ? 'yes' : 'no';
    echo "$name,$table,$migration,$factory,$seeder\n";
}
