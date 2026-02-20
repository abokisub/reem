#!/bin/bash

echo "=========================================="
echo "Deploy Settlement Receipt Account Fix"
echo "=========================================="
echo ""

echo "1. Pulling latest code from GitHub..."
git pull origin main

echo ""
echo "2. Clearing Laravel caches..."
php artisan cache:clear
php artisan config:clear
php artisan view:clear

echo ""
echo "3. Running diagnostic to verify the fix..."
php debug_settlement_receipt_final.php

echo ""
echo "=========================================="
echo "✅ Deployment Complete!"
echo "=========================================="
echo ""
echo "The settlement receipt should now show the correct account number."
echo "Visit the receipt URL to verify: /receipt/{transaction_id}"
