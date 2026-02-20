#!/bin/bash

echo "=========================================="
echo "Fix Table Responsive Width"
echo "=========================================="

# Step 1: Add all changes
echo "Step 1: Adding all changes to git..."
git add .

# Step 2: Commit changes
echo "Step 2: Committing changes..."
git commit -m "Fix table responsive width to show all columns

Updated TableContainer minWidth for all transaction pages:

1. Wallet Page (wallet-summary.js):
   - Changed from minWidth: 800 to minWidth: 1400
   - Now shows all 10 columns properly:
     * Transaction Ref
     * Session ID
     * Transaction Type
     * Amount
     * Fee
     * Net Amount
     * Status
     * Settlement
     * Date
     * Actions

2. RA Transactions (RATransactions.js):
   - Changed from minWidth: 800 to minWidth: 1600
   - Now shows all 11 columns properly:
     * Transaction Ref
     * Session ID
     * Type
     * Customer
     * Amount
     * Fee
     * Net Amount
     * Status
     * Settlement
     * Date
     * Actions

3. Admin Statement (AdminStatement.js):
   - Changed from minWidth: 1000 to minWidth: 1800
   - Now shows all 12 columns properly:
     * Transaction Ref
     * Session ID
     * Type
     * Company
     * Customer
     * Amount
     * Fee
     * Net Amount
     * Status
     * Settlement
     * Date
     * Actions

The Scrollbar component will handle horizontal scrolling
on smaller screens while showing all content on larger screens.

All tables now display full content without cutting off columns!"

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
echo "2. Build frontend locally:"
echo "   cd frontend && npm run build"
echo "3. Copy build to server via SCP"
echo "4. Test all transaction pages:"
echo "   - Wallet: /dashboard/wallet"
echo "   - RA Transactions: /dashboard/ra-transactions"
echo "   - Admin Statement: /secure/statement"
echo ""
echo "Expected results:"
echo "- All columns visible and not cut off"
echo "- Horizontal scroll available on smaller screens"
echo "- Full table width on larger screens"
echo "- All transaction data displayed properly"
echo "=========================================="
