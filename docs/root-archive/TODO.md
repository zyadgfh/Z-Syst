# Fix Corrupted Vendor Directory - Task Tracker

## Goal
Restore the corrupted `vendor/` directory so Laravel and Laravel Extra Intellisense work again.

## Steps
- [x] Step 1: Delete corrupted `vendor/` directory
- [x] Step 2: Run `composer install` to restore all packages from `composer.lock`
- [x] Step 3: Verify Laravel app boots (`php artisan --version`)
- [x] Step 4: Verify autoload works (`php -r "require 'vendor/autoload.php'; echo 'OK';"`)
- [x] Step 5: Reload VS Code window / restart Laravel Extra Intellisense

