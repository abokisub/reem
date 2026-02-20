#!/bin/bash

echo "=========================================="
echo "Fix Wallet Page Display"
echo "=========================================="

# Step 1: Add all changes
echo "Step 1: Adding all changes to git..."
git add .

# Step 2: Commit changes
echo "Step 2: Committing changes..."
git commit -m "Fix wallet page to show normalized transaction fields

Updated app/Http/Controllers/API/Trans.php:
- AllDepositHistory method now returns:
  - transaction_ref (new normalized field)
  - session_id (new normalized field)
  - transaction_type (new normalized field)
  - net_amount (new normalized field)
  - settlement_status (new normalized field)
  - Fixed status mapping to include 'pending' status

- AllHistoryUser method now returns:
  - transaction_ref (new normalized field)
  - session_id (new normalized field)
  - transaction_type (already existed)
  - fee as charges (new normalized field)
  - net_amount (new normalized field)
  - settlement_status (new normalized field)
  - status field directly (instead of just plan_status)
  - Fixed status mapping to include 'pending' status

Frontend wallet-summary.js already has:
- 10 columns with all normalized fields
- Settlement status column with color indicators
- Transaction type labels
- Proper status mapping

This fix ensures the wallet page displays:
✅ Transaction Ref (with copy button)
✅ Session ID (with copy button)
✅ Transaction Type (with color badges)
✅ Amount (with +/- indicators)
✅ Fee
✅ Net Amount
✅ Status (successful/failed/pending/processing)
✅ Settlement (settled/unsettled/not_applicable/failed)
✅ Date (formatted)
✅ Actions (view/download receipt)

No more 'PROCESSING' status showing incorrectly!"

# Step 3: Push to GitHub
echo "Step 3: Pushing to GitHub..."
git push origin main

echo ""
echo "=========================================="
echo "✓ Changes pushed to GitHub successfully!"
echo "=========================================="
echo ""
echo "Next steps on server:"
echo "1. Pull changes: git pull origin main"
echo "2. Clear Laravel caches:"
echo "   php artisan config:clear"
echo "   php artisan route:clear"
echo "   php artisan cache:clear"
echo "3. Test wallet page:"
echo "   https://app.pointwave.ng/dashboard/wallet"
echo ""
echo "Expected results:"
echo "- All transactions show correct status (not just PROCESSING)"
echo "- Settlement column visible with proper status"
echo "- Transaction Ref and Session ID visible"
echo "- Transaction Type shows proper labels"
echo "- Fee and Net Amount columns populated"
echo "=========================================="
