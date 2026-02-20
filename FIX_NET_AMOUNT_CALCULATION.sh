#!/bin/bash

echo "=========================================="
echo "Fix Net Amount Calculation"
echo "=========================================="
echo ""
echo "PROBLEM:"
echo "For withdrawals/transfers, net_amount was showing:"
echo "  net_amount = amount - fee (WRONG!)"
echo ""
echo "Example:"
echo "  Company withdraws ₦100"
echo "  Fee: ₦15"
echo "  Total deducted: ₦115"
echo "  OLD net_amount: ₦85 (WRONG!)"
echo "  NEW net_amount: ₦100 (CORRECT)"
echo ""
echo "SOLUTION:"
echo "For DEBIT (withdrawals/transfers):"
echo "  net_amount = amount (what recipient receives)"
echo ""
echo "For CREDIT (deposits):"
echo "  net_amount = amount - fee (what company receives after fee)"
echo ""

echo "1. Pulling latest code from GitHub..."
git pull origin main

echo ""
echo "2. Fixing existing transactions..."
php fix_net_amount_for_debits.php

echo ""
echo "3. Clearing Laravel caches..."
php artisan cache:clear
php artisan config:clear
php artisan view:clear

echo ""
echo "=========================================="
echo "✅ Fix Complete!"
echo "=========================================="
echo ""
echo "WHAT WAS FIXED:"
echo "1. Transaction model now calculates net_amount correctly based on type"
echo "2. All existing debit transactions updated with correct net_amount"
echo "3. Future transactions will have correct net_amount automatically"
echo ""
echo "EXPECTED RESULT:"
echo "For withdrawal of ₦100 with ₦15 fee:"
echo "  - Amount: ₦100 (what recipient receives)"
echo "  - Fee: ₦15 (what system charges)"
echo "  - Net Amount: ₦100 (CORRECT - same as amount)"
echo "  - Total Deducted: ₦115 (amount + fee)"
echo ""
