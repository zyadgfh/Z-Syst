#!/bin/bash
cd "D:\Zyad\laragon\www"
composer install --no-interaction --prefer-dist 2>&1 | tail -5
echo "=== RUNNING TESTS ==="
php artisan test 2>&1 | tail -10
