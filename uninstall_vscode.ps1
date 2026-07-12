# Complete VS Code Uninstall Script
# Warning: This will delete ALL settings, extensions, and data

Write-Host "=============================================" -ForegroundColor Cyan
Write-Host "  Complete VS Code Uninstall Script" -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host ""

$confirm = Read-Host "Are you sure you want to completely uninstall VS Code? (yes/no)"
if ($confirm -notmatch "^y") {
    Write-Host "Operation cancelled" -ForegroundColor Yellow
    exit
}

Write-Host "`n[1/7] Stopping VS Code processes..." -ForegroundColor Yellow
Get-Process -Name Code -ErrorAction SilentlyContinue | ForEach-Object {
    Write-Host "  Stopping process: $($_.Id)" -ForegroundColor Gray
    Stop-Process -Id $_.Id -Force
}
Start-Sleep -Seconds 2

Write-Host "`n[2/7] Uninstalling VS Code..." -ForegroundColor Yellow
$uninstallKeys = @(
    "HKLM:\Software\Microsoft\Windows\CurrentVersion\Uninstall\*",
    "HKLM:\Software\WOW6432Node\Microsoft\Windows\CurrentVersion\Uninstall\*",
    "HKCU:\Software\Microsoft\Windows\CurrentVersion\Uninstall\*"
)

foreach ($key in $uninstallKeys) {
    Get-ChildItem -Path $key -ErrorAction SilentlyContinue | ForEach-Object {
        $value = Get-ItemProperty -Path $_.PsPath -ErrorAction SilentlyContinue
        if ($value.DisplayName -like "*Visual Studio Code*" -or $value.DisplayName -like "*VS Code*") {
            Write-Host "  Found: $($value.DisplayName)" -ForegroundColor Gray
            if ($value.UninstallString) {
                $uninstallCmd = $value.UninstallString -replace '"', '' -replace '/l', '/silent'
                Write-Host "  Uninstalling..." -ForegroundColor Gray
                Start-Process -FilePath "cmd.exe" -ArgumentList "/c $uninstallCmd /silent" -Wait -NoNewWindow
            }
        }
    }
}

Write-Host "`n[3/7] Deleting installation folders..." -ForegroundColor Yellow
$installPaths = @(
    "$env:LOCALAPPDATA\Programs\Microsoft VS Code",
    "$env:PROGRAMFILES\Microsoft VS Code",
    "${env:PROGRAMFILES(X86)}\Microsoft VS Code",
    "$env:USERPROFILE\AppData\Local\Programs\Microsoft VS Code"
)

foreach ($path in $installPaths) {
    if (Test-Path $path) {
        Write-Host "  Deleting: $path" -ForegroundColor Gray
        Remove-Item -Path $path -Recurse -Force -ErrorAction SilentlyContinue
    }
}

Write-Host "`n[4/7] Deleting user data and settings..." -ForegroundColor Yellow
$userDataPaths = @(
    "$env:APPDATA\Code",
    "$env:LOCALAPPDATA\Code",
    "$env:USERPROFILE\.vscode",
    "$env:APPDATA\Code - Insiders",
    "$env:LOCALAPPDATA\Code - Insiders",
    "$env:USERPROFILE\.vscode-insiders"
)

foreach ($path in $userDataPaths) {
    if (Test-Path $path) {
        Write-Host "  Deleting: $path" -ForegroundColor Gray
        Remove-Item -Path $path -Recurse -Force -ErrorAction SilentlyContinue
    }
}

Write-Host "`n[5/7] Deleting extensions..." -ForegroundColor Yellow
$extensionsPath = "$env:USERPROFILE\.vscode\extensions"
if (Test-Path $extensionsPath) {
    Write-Host "  Deleting extensions folder" -ForegroundColor Gray
    Remove-Item -Path $extensionsPath -Recurse -Force -ErrorAction SilentlyContinue
}

Write-Host "`n[6/7] Cleaning registry..." -ForegroundColor Yellow
$registryPaths = @(
    "HKCU:\Software\Microsoft\VS Code",
    "HKCU:\Software\Microsoft\VS Code Insiders",
    "HKLM:\SOFTWARE\Microsoft\VS Code",
    "HKLM:\SOFTWARE\WOW6432Node\Microsoft\VS Code"
)

foreach ($path in $registryPaths) {
    if (Test-Path $path) {
        Write-Host "  Deleting: $path" -ForegroundColor Gray
        Remove-Item -Path $path -Recurse -Force -ErrorAction SilentlyContinue
    }
}

Get-ChildItem -Path "HKCU:\Software\Microsoft\Windows\CurrentVersion\Uninstall" -ErrorAction SilentlyContinue | ForEach-Object {
    $value = Get-ItemProperty -Path $_.PsPath -ErrorAction SilentlyContinue
    if ($value.DisplayName -like "*Visual Studio Code*" -or $value.DisplayName -like "*VS Code*") {
        Write-Host "  Removing registry key: $($value.DisplayName)" -ForegroundColor Gray
        Remove-Item -Path $_.PsPath -Recurse -Force -ErrorAction SilentlyContinue
    }
}

Write-Host "`n[7/7] Deleting shortcuts..." -ForegroundColor Yellow
$shortcuts = @(
    "$env:APPDATA\Microsoft\Windows\Start Menu\Programs\Visual Studio Code.lnk",
    "$env:PUBLIC\Desktop\Visual Studio Code.lnk",
    "$env:USERPROFILE\Desktop\Visual Studio Code.lnk"
)

foreach ($shortcut in $shortcuts) {
    if (Test-Path $shortcut) {
        Write-Host "  Deleting: $shortcut" -ForegroundColor Gray
        Remove-Item -Path $shortcut -Force -ErrorAction SilentlyContinue
    }
}

Write-Host "`n=============================================" -ForegroundColor Green
Write-Host "  VS Code has been completely removed!" -ForegroundColor Green
Write-Host "=============================================" -ForegroundColor Green
Write-Host ""
Write-Host "Important notes:" -ForegroundColor Yellow
Write-Host "  - All settings and extensions have been deleted" -ForegroundColor Gray
Write-Host "  - All user data has been removed" -ForegroundColor Gray
Write-Host "  - You may need to restart your system" -ForegroundColor Gray
Write-Host ""
Write-Host "To reinstall, visit: https://code.visualstudio.com" -ForegroundColor Cyan