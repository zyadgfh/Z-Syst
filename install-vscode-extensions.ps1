# =====================================================
# سكربت تثبيت إضافات VS Code - مشروع إدارة الصيدلية
# =====================================================

Write-Host "🚀 بدء تثبيت إضافات VS Code..." -ForegroundColor Green
Write-Host ""

# 1️⃣ PHP و Laravel
Write-Host "📦 تثبيت إضافات PHP و Laravel..." -ForegroundColor Cyan
$phpExtensions = @(
    "bmewburn.vscode-intelephense-client",
    "onecentlin.laravel-extension-pack",
    "onecentlin.laravel-blade",
    "xdebug.php-debug",
    "junstyle.php-cs-fixer",
    "neilbrayfield.php-docblocker",
    "ryannaddy.laravel-artisan",
    "onecentlin.laravel-goto-view",
    "naoray.laravel-goto-components",
    "mikestead.dotenv"
)
foreach ($ext in $phpExtensions) {
    code --install-extension $ext
}
Write-Host "✅ تم تثبيت إضافات PHP" -ForegroundColor Green
Write-Host ""

# 2️⃣ Node.js و TypeScript
Write-Host "📦 تثبيت إضافات Node.js و TypeScript..." -ForegroundColor Cyan
$nodeExtensions = @(
    "dbaeumer.vscode-eslint",
    "esbenp.prettier-vscode",
    "pflannery.vscode-versionlens",
    "christian-kohler.npm-intellisense",
    "christian-kohler.path-intellisense",
    "formulahendry.auto-rename-tag"
)
foreach ($ext in $nodeExtensions) {
    code --install-extension $ext
}
Write-Host "✅ تم تثبيت إضافات Node.js" -ForegroundColor Green
Write-Host ""

# 3️⃣ Prisma و قواعد البيانات
Write-Host "📦 تثبيت إضافات Prisma..." -ForegroundColor Cyan
$dbExtensions = @(
    "Prisma.prisma",
    "mtxr.sqltools",
    "qwtel.sqlite-viewer"
)
foreach ($ext in $dbExtensions) {
    code --install-extension $ext
}
Write-Host "✅ تم تثبيت إضافات قواعد البيانات" -ForegroundColor Green
Write-Host ""

# 4️⃣ Flutter و Dart
Write-Host "📦 تثبيت إضافات Flutter..." -ForegroundColor Cyan
$flutterExtensions = @(
    "Dart-Code.flutter",
    "Dart-Code.dart-code",
    "Nash.awesome-flutter-snippets",
    "robert-brunhage.flutter-riverpod-snippets",
    "usernamehw.errorlens"
)
foreach ($ext in $flutterExtensions) {
    code --install-extension $ext
}
Write-Host "✅ تم تثبيت إضافات Flutter" -ForegroundColor Green
Write-Host ""

# 5️⃣ CSS و Tailwind
Write-Host "📦 تثبيت إضافات CSS و Tailwind..." -ForegroundColor Cyan
$cssExtensions = @(
    "bradlc.vscode-tailwindcss",
    "pranaygp.vscode-css-peek",
    "ritwickdey.LiveServer",
    "formulahendry.auto-close-tag",
    "formulahendry.auto-complete-tag"
)
foreach ($ext in $cssExtensions) {
    code --install-extension $ext
}
Write-Host "✅ تم تثبيت إضافات CSS" -ForegroundColor Green
Write-Host ""

# 6️⃣ Git
Write-Host "📦 تثبيت إضافات Git..." -ForegroundColor Cyan
$gitExtensions = @(
    "eamodio.gitlens",
    "mhutchie.git-graph",
    "donjayamanne.githistory"
)
foreach ($ext in $gitExtensions) {
    code --install-extension $ext
}
Write-Host "✅ تم تثبيت إضافات Git" -ForegroundColor Green
Write-Host ""

# 7️⃣ الإنتاجية
Write-Host "📦 تثبيت إضافات الإنتاجية..." -ForegroundColor Cyan
$productivityExtensions = @(
    "rangav.vscode-thunder-client",
    "humao.rest-client",
    "streetsidesoftware.code-spell-checker",
    "gruntfuggly.todo-tree",
    "aaron-bond.better-comments",
    "oderwat.indent-rainbow",
    "CoenraadS.bracket-pair-colorizer-2",
    "PKief.material-icon-theme",
    "zhuangtongfa.material-theme",
    "alefragnani.project-manager",
    "wix.vscode-import-cost",
    "alefragnani.Bookmarks"
)
foreach ($ext in $productivityExtensions) {
    code --install-extension $ext
}
Write-Host "✅ تم تثبيت إضافات الإنتاجية" -ForegroundColor Green
Write-Host ""

# 8️⃣ AI
Write-Host "📦 تثبيت إضافات AI..." -ForegroundColor Cyan
$aiExtensions = @(
    "GitHub.copilot",
    "Codeium.codeium",
    "Continue.continue",
    "saoud-dev.cline"
)
foreach ($ext in $aiExtensions) {
    code --install-extension $ext
}
Write-Host "✅ تم تثبيت إضافات AI" -ForegroundColor Green
Write-Host ""

# 9️⃣ الاختبار
Write-Host "📦 تثبيت إضافات الاختبار..." -ForegroundColor Cyan
$testExtensions = @(
    "recca0120.vscode-phpunit",
    "Orta.vscode-jest",
    "ms-playwright.playwright"
)
foreach ($ext in $testExtensions) {
    code --install-extension $ext
}
Write-Host "✅ تم تثبيت إضافات الاختبار" -ForegroundColor Green
Write-Host ""

Write-Host "🎉 تم تثبيت جميع الإضافات بنجاح!" -ForegroundColor Green
Write-Host "💡 ملاحظة: أعد تشغيل VS Code لتفعيل جميع الإضافات" -ForegroundColor Yellow