#!/bin/bash

# Clear Caches and Test Receipt - Complete Script
# This script clears all Laravel caches and tests receipt generation

echo "=========================================="
echo "CLEARING ALL LARAVEL CACHES"
echo "=========================================="

# Clear all caches
php artisan cache:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear
php artisan optimize:clear

echo ""
echo "✅ All caches cleared successfully"
echo ""

echo "=========================================="
echo "VERIFYING VIRTUAL ACCOUNT DATA"
echo "=========================================="

# Run diagnostic script
php check_virtual_account_data.php

echo ""
echo "=========================================="
echo "NEXT STEPS"
echo "=========================================="
echo ""
echo "1. Open your browser in INCOGNITO/PRIVATE mode"
echo "2. Go to: https://app.pointwave.ng/dashboard/ra-transactions"
echo "3. Click on the transaction: txn_699861639a0ca24142"
echo "4. Click 'View Receipt' or 'Download Receipt'"
echo "5. Check if RECIPIENT DETAILS now shows:"
echo "   - Account Name: PointWave Business-Jamil Abubakar Bashir(PointWave)"
echo "   - Account Number: 6690945661"
echo "   - Bank: PalmPay"
echo ""
echo "If still showing N/A, make a NEW test transaction to verify."
echo ""
