Param(
    [switch]$RunMigrations,
    [switch]$RunSeed
)

Write-Host "Running setup-dev.ps1: checking prerequisites..."

function Check-Command($cmd) {
    $which = & where.exe $cmd 2>$null
    return $LASTEXITCODE -eq 0
}

if (-not (Check-Command php)) {
    Write-Error "PHP not found in PATH. Install PHP and ensure it's available in your PATH."
    exit 1
}

if (-not (Check-Command composer)) {
    Write-Warning "Composer not found. Attempting to use vendor/bin if present."
}

Write-Host "Installing PHP dependencies (composer install) ..."
if (Check-Command composer) {
    composer install
} else {
    Write-Warning "Skipping composer install - composer not available."
}

Write-Host "Generating app key if missing..."
php artisan key:generate

if ($RunMigrations) {
    Write-Host "Running migrations..."
    php artisan migrate --force
}

if ($RunSeed) {
    Write-Host "Seeding demo data..."
    php artisan db:seed --class=DemoSeeder
}

Write-Host "Setup script finished. If any step failed, inspect the output and run commands manually."
