# =====================================================
# إعداد VS Code لمشروع إدارة الصيدلية (Windows)
# =====================================================

$ErrorActionPreference = "Continue"

Write-Host "🏥 بدء إعداد VS Code لمشروع إدارة الصيدلية..." -ForegroundColor Cyan
Write-Host ""

# 1️⃣ إنشاء مجلد .vscode
if (!(Test-Path ".vscode")) {
    New-Item -ItemType Directory -Path ".vscode" | Out-Null
    Write-Host "✅ تم إنشاء مجلد .vscode" -ForegroundColor Green
}

# 2️⃣ extensions.json
$extensionsJson = @'
{
  "recommendations": [
    "bmewburn.vscode-intelephense-client",
    "onecentlin.laravel-extension-pack",
    "onecentlin.laravel-blade",
    "xdebug.php-debug",
    "junstyle.php-cs-fixer",
    "neilbrayfield.php-docblocker",
    "ryannaddy.laravel-artisan",
    "onecentlin.laravel-goto-view",
    "mikestead.dotenv",
    "dbaeumer.vscode-eslint",
    "esbenp.prettier-vscode",
    "christian-kohler.path-intellisense",
    "christian-kohler.npm-intellisense",
    "Prisma.prisma",
    "mtxr.sqltools",
    "Dart-Code.flutter",
    "Dart-Code.dart-code",
    "usernamehw.errorlens",
    "bradlc.vscode-tailwindcss",
    "ritwickdey.LiveServer",
    "eamodio.gitlens",
    "mhutchie.git-graph",
    "rangav.vscode-thunder-client",
    "streetsidesoftware.code-spell-checker",
    "gruntfuggly.todo-tree",
    "aaron-bond.better-comments",
    "oderwat.indent-rainbow",
    "PKief.material-icon-theme",
    "zhuangtongfa.material-theme",
    "Codeium.codeium",
    "recca0120.vscode-phpunit",
    "ms-playwright.playwright",
    "formulahendry.auto-rename-tag",
    "formulahendry.auto-close-tag",
    "alefragnani.project-manager"
  ]
}
'@
$extensionsJson | Out-File -FilePath ".vscode\extensions.json" -Encoding UTF8
Write-Host "✅ تم إنشاء extensions.json" -ForegroundColor Green

# 3️⃣ settings.json (نفس المحتوى السابق)
$settingsJson = @'
{
  "workbench.iconTheme": "material-icon-theme",
  "workbench.colorTheme": "One Dark Pro",
  "editor.fontSize": 14,
  "editor.tabSize": 4,
  "editor.wordWrap": "on",
  "editor.formatOnSave": true,
  "editor.bracketPairColorization.enabled": true,
  "editor.guides.bracketPairs": true,
  "editor.inlineSuggest.enabled": true,
  "files.autoSave": "afterDelay",
  "files.autoSaveDelay": 1000,
  "files.exclude": {
    "**/node_modules": true,
    "**/vendor": true,
    "**/storage/framework": true
  },
  "[php]": {
    "editor.defaultFormatter": "junstyle.php-cs-fixer",
    "editor.tabSize": 4
  },
  "[javascript]": {
    "editor.defaultFormatter": "esbenp.prettier-vscode",
    "editor.tabSize": 2
  },
  "[typescript]": {
    "editor.defaultFormatter": "esbenp.prettier-vscode",
    "editor.tabSize": 2
  },
  "[dart]": {
    "editor.formatOnSave": true,
    "editor.tabSize": 2
  },
  "tailwindCSS.includeLanguages": {
    "blade": "html",
    "html": "html"
  },
  "codeium.enableConfig": {
    "*": true,
    "php": true,
    "blade": true,
    "javascript": true,
    "typescript": true,
    "dart": true
  },
  "errorLens.enabledDiagnosticLevels": ["error", "warning"],
  "git.autofetch": true,
  "git.enableSmartCommit": true,
  "phpunit.php": "php",
  "phpunit.phpunit": ".\\vendor\\bin\\phpunit"
}
'@
$settingsJson | Out-File -FilePath ".vscode\settings.json" -Encoding UTF8
Write-Host "✅ تم إنشاء settings.json" -ForegroundColor Green

# 4️⃣ launch.json
$launchJson = @'
{
  "version": "0.2.0",
  "configurations": [
    {
      "name": "Listen for Xdebug (Laravel)",
      "type": "php",
      "request": "launch",
      "port": 9003
    },
    {
      "name": "Flutter: Debug",
      "type": "dart",
      "request": "launch",
      "program": "pharmacy-store-app-codecanyon-main/lib/main.dart"
    },
    {
      "name": "Node.js: Debug Backend",
      "type": "node",
      "request": "launch",
      "runtimeExecutable": "npm",
      "runtimeArgs": ["run", "dev"],
      "cwd": "${workspaceFolder}/backend"
    }
  ]
}
'@
$launchJson | Out-File -FilePath ".vscode\launch.json" -Encoding UTF8
Write-Host "✅ تم إنشاء launch.json" -ForegroundColor Green

# 5️⃣ tasks.json
$tasksJson = @'
{
  "version": "2.0.0",
  "tasks": [
    {
      "label": "Laravel: Serve",
      "type": "shell",
      "command": "php artisan serve",
      "group": "build",
      "isBackground": true
    },
    {
      "label": "Laravel: Migrate",
      "type": "shell",
      "command": "php artisan migrate",
      "group": "build"
    },
    {
      "label": "Laravel: Test",
      "type": "shell",
      "command": "php artisan test",
      "group": "test"
    },
    {
      "label": "Composer Install",
      "type": "shell",
      "command": "composer install",
      "group": "build"
    },
    {
      "label": "NPM Install (Backend)",
      "type": "shell",
      "command": "npm install",
      "options": { "cwd": "${workspaceFolder}/backend" },
      "group": "build"
    },
    {
      "label": "Prisma: Generate",
      "type": "shell",
      "command": "npx prisma generate",
      "options": { "cwd": "${workspaceFolder}/backend" },
      "group": "build"
    },
    {
      "label": "Flutter: Get Packages",
      "type": "shell",
      "command": "flutter pub get",
      "options": { "cwd": "${workspaceFolder}/pharmacy-store-app-codecanyon-main" },
      "group": "build"
    },
    {
      "label": "Playwright: Test",
      "type": "shell",
      "command": "npx playwright test",
      "group": "test"
    }
  ]
}
'@
$tasksJson | Out-File -FilePath ".vscode\tasks.json" -Encoding UTF8
Write-Host "✅ تم إنشاء tasks.json" -ForegroundColor Green

# 6️⃣ التحقق من الإضافات
Write-Host ""
Write-Host "🔍 التحقق من الإضافات المثبتة..." -ForegroundColor Cyan
Write-Host ""

$installedExtensions = code --list-extensions 2>$null
if (!$installedExtensions) {
    $installedExtensions = @()
}

$requiredExtensions = @(
    "bmewburn.vscode-intelephense-client",
    "onecentlin.laravel-extension-pack",
    "onecentlin.laravel-blade",
    "xdebug.php-debug",
    "dbaeumer.vscode-eslint",
    "esbenp.prettier-vscode",
    "Prisma.prisma",
    "Dart-Code.flutter",
    "Dart-Code.dart-code",
    "bradlc.vscode-tailwindcss",
    "eamodio.gitlens",
    "Codeium.codeium",
    "usernamehw.errorlens",
    "PKief.material-icon-theme"
)

$missingExtensions = @()
$installedCount = 0

foreach ($ext in $requiredExtensions) {
    if ($installedExtensions -contains $ext) {
        Write-Host "✅ $ext" -ForegroundColor Green
        $installedCount++
    } else {
        Write-Host "❌ $ext (غير مثبت)" -ForegroundColor Red
        $missingExtensions += $ext
    }
}

Write-Host ""
Write-Host "📊 الإحصائيات:" -ForegroundColor Cyan
Write-Host "   مثبت: $installedCount" -ForegroundColor Green
Write-Host "   مفقود: $($missingExtensions.Count)" -ForegroundColor Red

# 7️⃣ تثبيت الإضافات المفقودة
if ($missingExtensions.Count -gt 0) {
    Write-Host ""
    Write-Host "⚠️  سيتم تثبيت الإضافات المفقودة..." -ForegroundColor Yellow
    foreach ($ext in $missingExtensions) {
        Write-Host "📦 تثبيت: $ext" -ForegroundColor Blue
        code --install-extension $ext --force
    }
    Write-Host "✅ تم تثبيت جميع الإضافات المفقودة" -ForegroundColor Green
} else {
    Write-Host "🎉 جميع الإضافات الأساسية مثبتة!" -ForegroundColor Green
}

Write-Host ""
Write-Host "╔══════════════════════════════════════════════╗" -ForegroundColor Green
Write-Host "║  ✅ تم إعداد VS Code بنجاح!                  ║" -ForegroundColor Green
Write-Host "║  📁 الملفات في مجلد .vscode/                 ║" -ForegroundColor Green
Write-Host "║  🔄 أعد تشغيل VS Code لتفعيل الإعدادات      ║" -ForegroundColor Green
Write-Host "╚══════════════════════════════════════════════╝" -ForegroundColor Green
