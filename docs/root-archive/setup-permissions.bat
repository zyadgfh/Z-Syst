@echo off
REM File Permissions Setup Script for Z-Syst Pharmacy
REM This script sets proper permissions for storage and cache directories

echo Setting up file permissions for Z-Syst Pharmacy...

REM Create storage directories if they don't exist
if not exist "storage\app" mkdir "storage\app"
if not exist "storage\framework" mkdir "storage\framework"
if not exist "storage\framework\cache" mkdir "storage\framework\cache"
if not exist "storage\framework\sessions" mkdir "storage\framework\sessions"
if not exist "storage\framework\views" mkdir "storage\framework\views"
if not exist "storage\logs" mkdir "storage\logs"

REM Set permissions for storage directory
echo Setting permissions for storage directory...
icacls "storage" /grant Everyone:(OI)(CI)F /T

REM Set permissions for bootstrap cache directory
echo Setting permissions for bootstrap cache directory...
icacls "bootstrap\cache" /grant Everyone:(OI)(CI)F /T

REM Set permissions for public directory
echo Setting permissions for public directory...
icacls "public" /grant Everyone:(OI)(CI)F /T

echo.
echo File permissions setup completed!
echo.
echo For production deployment on Linux servers, use the following commands:
echo chmod -R 755 storage bootstrap/cache
echo chmod -R 777 storage/logs
echo chmod -R 777 storage/framework/cache
echo chmod -R 777 storage/framework/sessions
echo chmod -R 777 storage/framework/views
echo chmod -R 777 bootstrap/cache
echo.
pause