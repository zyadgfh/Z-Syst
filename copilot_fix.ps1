# Copilot diagnostics and fix script
# Usage: Run in an elevated PowerShell: .\copilot_fix.ps1

$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$out = Join-Path $env:TEMP "copilot_diagnostics_$timestamp"
New-Item -ItemType Directory -Path $out -Force | Out-Null

Write-Output "Diagnostics folder: $out"

# 1) Gather VS Code logs
$vscodeLogs = Join-Path $env:APPDATA "Code\logs"
if (Test-Path $vscodeLogs) {
    $dest = Join-Path $out "vscode_logs"
    Copy-Item -Path $vscodeLogs -Destination $dest -Recurse -ErrorAction SilentlyContinue
    Write-Output "Copied VS Code logs to $dest"
} else {
    Write-Output "VS Code logs directory not found: $vscodeLogs"
}

# 2) Gather VS Code user globalStorage for GitHub Copilot
$globalStorage = Join-Path $env:APPDATA "Code\User\globalStorage"
if (Test-Path $globalStorage) {
    $copilotStorage = Get-ChildItem -Path $globalStorage -Filter "*copilot*" -Directory -ErrorAction SilentlyContinue
    if ($copilotStorage) {
        $dest = Join-Path $out "globalStorage"
        New-Item -ItemType Directory -Path $dest -Force | Out-Null
        foreach ($d in $copilotStorage) { Copy-Item -Path $d.FullName -Destination $dest -Recurse -ErrorAction SilentlyContinue }
        Write-Output "Copied Copilot globalStorage to $dest"
    } else { Write-Output "No Copilot globalStorage folders found in $globalStorage" }
} else { Write-Output "VS Code globalStorage path not found: $globalStorage" }

# 3) Gather Copilot extension folder(s)
$extensionsPath = Join-Path $env:USERPROFILE ".vscode\extensions"
if (Test-Path $extensionsPath) {
    $copilotExts = Get-ChildItem -Path $extensionsPath -Filter "*copilot*" -Directory -ErrorAction SilentlyContinue
    if ($copilotExts) {
        $dest = Join-Path $out "extensions"
        New-Item -ItemType Directory -Path $dest -Force | Out-Null
        foreach ($d in $copilotExts) { Copy-Item -Path $d.FullName -Destination $dest -Recurse -ErrorAction SilentlyContinue }
        Write-Output "Copied Copilot extension folders to $dest"
    } else { Write-Output "No Copilot extension folders found in $extensionsPath" }
} else { Write-Output "Extensions path not found: $extensionsPath" }

# 4) Gather installed extensions list and code version (if code CLI available)
$codeCmd = "code"
try {
    $codeVersion = & $codeCmd --version 2>$null
    if ($LASTEXITCODE -eq 0) {
        $meta = Join-Path $out "code_cli_info.txt"
        $codeVersion | Out-File -FilePath $meta -Encoding utf8
        & $codeCmd --list-extensions --show-versions | Out-File -FilePath $meta -Append -Encoding utf8
        Write-Output "Saved code CLI version and extensions to $meta"
    } else { Write-Output "'code' CLI not available in PATH or returned an error." }
} catch { Write-Output "Failed to run 'code' CLI: $_" }

# 5) Copy VS Code settings and key files (User settings)
$userSettings = Join-Path $env:APPDATA "Code\User"
if (Test-Path $userSettings) {
    $dest = Join-Path $out "user_settings"
    New-Item -ItemType Directory -Path $dest -Force | Out-Null
    Copy-Item -Path (Join-Path $userSettings "settings.json") -Destination $dest -ErrorAction SilentlyContinue
    Copy-Item -Path (Join-Path $userSettings "logs") -Destination $dest -Recurse -ErrorAction SilentlyContinue
    Write-Output "Copied user settings to $dest"
} else { Write-Output "VS Code User settings path not found: $userSettings" }

# 6) Prompt to optionally reinstall Copilot extension using 'code' CLI
$canUseCode = $false
try { & $codeCmd --version *> $null; if ($LASTEXITCODE -eq 0) { $canUseCode = $true } } catch { }
if ($canUseCode) {
    $choice = Read-Host "'code' CLI detected. Reinstall GitHub Copilot extension now? (yes/no)"
    if ($choice -match "^y") {
        Write-Output "Uninstalling GitHub.copilot extension..."
        & $codeCmd --uninstall-extension GitHub.copilot 2>&1 | Out-File -FilePath (Join-Path $out "code_uninstall.txt") -Encoding utf8
        Start-Sleep -Seconds 2
        Write-Output "Installing GitHub.copilot extension..."
        & $codeCmd --install-extension GitHub.copilot 2>&1 | Out-File -FilePath (Join-Path $out "code_install.txt") -Encoding utf8
    } else { Write-Output "Skipping uninstall/install of Copilot extension." }
} else {
    Write-Output "'code' CLI not usable; skipping automated reinstall step." 
}

# 7) Restart VS Code (ask user first)
$restartChoice = Read-Host "Kill Code.exe processes and attempt to restart VS Code? (yes/no)"
if ($restartChoice -match "^y") {
    Get-Process -Name Code -ErrorAction SilentlyContinue | ForEach-Object { Write-Output "Stopping process Id=$($_.Id)"; Stop-Process -Id $_.Id -Force }
    if ($canUseCode) { Start-Process -FilePath $codeCmd }
    Write-Output "Restart requested. If VS Code doesn't start, launch it manually."
} else { Write-Output "Skipping restart." }

# 8) Zip diagnostics output
$zipPath = Join-Path $env:TEMP "copilot_diagnostics_$timestamp.zip"
Try {
    Add-Type -AssemblyName System.IO.Compression.FileSystem
    [System.IO.Compression.ZipFile]::CreateFromDirectory($out, $zipPath)
    Write-Output "Created zip: $zipPath"
} Catch {
    Write-Output "Failed to create zip automatically. Diagnostics folder: $out"
}

Write-Output "Completed. Provide the zip file or folder path if you want further help."