#!/bin/bash

echo "=========================================="
echo "FIX RA TRANSACTIONS DISPLAY"
echo "=========================================="
echo ""
echo "ISSUE: Transactions not showing on RA Transactions page"
echo "ROOT CAUSE: transaction_type field is NULL (not set by webhook handler)"
echo "FIX: Update WebhookHandler to set transaction_type = 'va_deposit'"
echo ""
echo "=========================================="
echo ""

# Step 1: Check current transaction
echo "Step 1: Checking current transaction..."
php check_transaction_type.php
echo ""

# Step 2: Push to GitHub
echo "Step 2: Pushing fix to GitHub..."
git add app/Services/PalmPay/WebhookHandler.php
git add check_transaction_type.php
git add fix_existing_transaction_type.php
git add FIX_RA_TRANSACTIONS_DISPLAY.sh
git commit -m "Fix: Set transaction_type in webhook handler for RA Transactions display

- WebhookHandler now sets transaction_type='va_deposit' for deposits
- WebhookHandler now sets settlement_status='settled' for deposits
- Added diagnostic script to check transaction_type
- Added backfill script to fix existing transactions
- This fixes RA Transactions page not showing deposits"
git push origin main
echo ""

echo "=========================================="
echo "DEPLOYMENT INSTRUCTIONS"
echo "=========================================="
echo ""
echo "On your server, run these commands:"
echo ""
echo "1. Pull the latest code:"
echo "   cd /var/www/html"
echo "   git pull origin main"
echo ""
echo "2. Fix existing transactions (backfill):"
echo "   php fix_existing_transaction_type.php"
echo ""
echo "3. Verify the fix:"
echo "   php check_transaction_type.php"
echo ""
echo "4. Test by making a new deposit"
echo "   - The transaction should now appear on RA Transactions page"
echo ""
echo "=========================================="
echo "✅ Fix pushed to GitHub!"
echo "=========================================="
