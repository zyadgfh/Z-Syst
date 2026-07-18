Write-Host "Bringing up Docker Compose development environment..."

$composeFile = "docker-compose.dev.yml"

Write-Host "Building and starting containers..."
docker compose -f $composeFile up -d --build

Write-Host "Waiting for Postgres (127.0.0.1:5432)..."
$maxAttempts = 30
$attempt = 0
while ($attempt -lt $maxAttempts) {
    $attempt++
    $res = Test-NetConnection -ComputerName '127.0.0.1' -Port 5432 -WarningAction SilentlyContinue
    if ($res.TcpTestSucceeded) { break }
    Start-Sleep -Seconds 2
}

if ($attempt -ge $maxAttempts) {
    Write-Error "Postgres did not become available in time. Check docker compose logs."
    exit 1
}

Write-Host "Running migrations inside app container..."
docker compose -f $composeFile exec -T app php artisan migrate --force
Write-Host "Seeding demo data..."
docker compose -f $composeFile exec -T app php artisan db:seed --class=DemoSeeder

Write-Host "Dev environment is up. App available at http://localhost:8000"
