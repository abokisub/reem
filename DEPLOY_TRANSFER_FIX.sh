#!/bin/bash

echo "=========================================="
echo "Transfer Balance Fix Deployment"
echo "=========================================="
echo ""

# Step 1: Check current wallet state
echo "Step 1: Checking current wallet state..."
echo "------------------------------------------"
php check_wallet_balance.php
echo ""

read -p "Press Enter to continue to fix stuck transactions..."
echo ""

# Step 2: Fix stuck transactions
echo "Step 2: Fixing stuck transactions..."
echo "------------------------------------------"
php fix_transfer_balance_issue.php
echo ""

read -p "Press Enter to continue to deploy backend fix..."
echo ""

# Step 3: Deploy backend fix
echo "Step 3: Deploying backend fix..."
echo "------------------------------------------"
git pull origin main
echo ""

# Step 4: Verify deployment
echo "Step 4: Verifying deployment..."
echo "------------------------------------------"
php check_wallet_balance.php
echo ""

echo "=========================================="
echo "Deployment Complete!"
echo "=========================================="
echo ""
echo "Next Steps:"
echo "1. Test a small transfer (₦50) to verify the fix"
echo "2. Check error messages show correct balance"
echo "3. Verify no money is deducted on validation failure"
echo ""
echo "Monitor logs with:"
echo "tail -f storage/logs/laravel.log | grep TransferRequest"
echo ""
