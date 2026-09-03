@echo off
REM Test Coverage Script for Z-Syst Pharmacy Management System (Windows)

echo.
echo 🧪 Running Test Coverage Analysis...
echo ====================================

REM Run tests with coverage
echo Running PHPUnit with coverage...
vendor\bin\phpunit --coverage-html=coverage\html --coverage-text=coverage.txt

REM Check if tests passed
if %ERRORLEVEL% EQU 0 (
    echo.
    echo ✓ All tests passed!
) else (
    echo.
    echo ✗ Some tests failed!
    exit /b 1
)

REM Generate coverage report
echo.
echo Generating coverage report...
if exist coverage.txt (
    echo Coverage Summary:
    type coverage.txt
)

REM Open coverage report in browser (optional)
if "%1"=="--open" (
    echo Opening coverage report in browser...
    start coverage\html\index.html
)

echo.
echo ✓ Coverage analysis complete!
echo Coverage report available at: coverage\html\index.html
