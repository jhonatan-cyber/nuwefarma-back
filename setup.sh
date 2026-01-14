#!/bin/bash

# NuweFarma Quick Setup Script for Linux/Mac

echo ""
echo "========================================"
echo "     NuweFarma Backend Setup Script"
echo "========================================"
echo ""

# Check PHP
if ! command -v php &> /dev/null; then
    echo "[ERROR] PHP not found. Please install PHP 8.2+"
    exit 1
fi

# Check Composer
if ! command -v composer &> /dev/null; then
    echo "[ERROR] Composer not found. Please install Composer"
    exit 1
fi

echo "[1/6] Installing PHP dependencies..."
composer install || exit 1

echo "[2/6] Generating APP_KEY..."
php artisan key:generate || exit 1

echo "[3/6] Running migrations..."
php artisan migrate || exit 1

echo "[4/6] Seeding database..."
php artisan db:seed || exit 1

echo "[5/6] Generating Swagger documentation..."
php artisan l5-swagger:generate || exit 1

echo "[6/6] Creating storage link..."
php artisan storage:link 2>/dev/null

echo ""
echo "========================================"
echo "     ✓ Setup Complete!"
echo "========================================"
echo ""
echo "To start the server:"
echo "  php artisan serve"
echo ""
echo "API will be available at:"
echo "  http://localhost:8000/api"
echo ""
echo "Documentation at:"
echo "  http://localhost:8000/api/documentation"
echo ""
echo "Test credentials:"
echo "  Email: jhonatanancasi@gmail.com"
echo "  Password: 10571705"
echo ""
echo "========================================"
echo ""
