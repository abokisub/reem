#!/bin/bash

echo "=========================================="
echo "FIXING RECEIPT RECIPIENT DETAILS - FINAL"
echo "=========================================="
echo ""

echo "CHANGES MADE:"
echo "1. Updated ReceiptService to handle BOTH column name formats:"
echo "   - palmpay_account_number OR account_number"
echo "   - palmpay_account_name OR account_name"
echo "   - palmpay_bank_name OR bank_name"
echo ""
echo "2. Added debug script to identify which columns exist"
echo ""

# Step 1: Push to GitHub
echo "Step 1: Pushing to GitHub..."
git add app/Services/ReceiptService.php
git add debug_receipt_generation.php
git add FIX_RECEIPT_RECIPIENT_FINAL.sh
git commit -m "Fix receipt recipient details - handle both column name formats"
git push origin main

echo ""
echo "✅ Pushed to GitHub"
echo ""

echo "=========================================="
echo "DEPLOYMENT STEPS FOR SERVER"
echo "=========================================="
echo ""
echo "Run these commands on your server:"
echo ""
echo "cd /home/aboksdfs/app.pointwave.ng"
echo "git pull origin main"
echo ""
echo "# Clear all caches"
echo "php artisan cache:clear"
echo "php artisan view:clear"
echo "php artisan config:clear"
echo "php artisan optimize:clear"
echo ""
echo "# Run debug script to see which columns exist"
echo "php debug_receipt_generation.php"
echo ""
echo "# Then test the receipt again"
echo "# Go to: https://app.pointwave.ng/dashboard/ra-transactions/2"
echo "# Click 'View Receipt'"
echo ""
echo "The recipient details should now show correctly!"
echo ""
