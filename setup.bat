@echo off
REM NuweFarma Quick Setup Script for Windows

echo.
echo ========================================
echo     NuweFarma Backend Setup Script
echo ========================================
echo.

REM Check PHP
php --version >nul 2>&1
if errorlevel 1 (
    echo [ERROR] PHP not found in PATH
    exit /b 1
)

REM Check Composer
composer --version >nul 2>&1
if errorlevel 1 (
    echo [ERROR] Composer not found in PATH
    exit /b 1
)

echo [1/6] Installing PHP dependencies...
call composer install
if errorlevel 1 exit /b 1

echo [2/6] Generating APP_KEY...
php artisan key:generate
if errorlevel 1 exit /b 1

echo [3/6] Running migrations...
php artisan migrate
if errorlevel 1 exit /b 1

echo [4/6] Seeding database...
php artisan db:seed
if errorlevel 1 exit /b 1

echo [5/6] Generating Swagger documentation...
php artisan l5-swagger:generate
if errorlevel 1 exit /b 1

echo [6/6] Creating storage link...
php artisan storage:link 2>nul

echo.
echo ========================================
echo     ✓ Setup Complete!
echo ========================================
echo.
echo To start the server:
echo   php artisan serve
echo.
echo API will be available at:
echo   http://localhost:8000/api
echo.
echo Documentation at:
echo   http://localhost:8000/api/documentation
echo.
echo Test credentials:
echo   Email: jhonatanancasi@gmail.com
echo   Password: 10571705
echo.
echo ========================================
echo.
pause
