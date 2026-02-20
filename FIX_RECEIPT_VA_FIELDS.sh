#!/bin/bash

echo "=========================================="
echo "FIX: Receipt N/A Fields - Virtual Account"
echo "=========================================="
echo ""
echo "ISSUE: Receipt page showing N/A for recipient details"
echo "ROOT CAUSE: Backend API not returning va_account_name/va_account_number fields"
echo "FIX: Updated AllRATransactions to populate virtual account fields"
echo ""

# Step 1: Push to GitHub
echo "Step 1: Pushing fix to GitHub..."
git add app/Http/Controllers/API/Trans.php
git commit -m "Fix: Add virtual account fields to RA transactions API response

- Added va_account_name, va_account_number, va_bank_name to transaction response
- Frontend receipt page expects these fields for recipient details
- Fixes N/A display issue on receipt page for deposits
- Handles both palmpay_* and generic column names with fallback"

git push origin main

if [ $? -eq 0 ]; then
    echo "✅ Successfully pushed to GitHub"
else
    echo "❌ Failed to push to GitHub"
    exit 1
fi

echo ""
echo "=========================================="
echo "NEXT: Deploy on Server"
echo "=========================================="
echo ""
echo "Run these commands on the server:"
echo ""
echo "cd /home/aboksdfs/app.pointwave.ng"
echo "git pull origin main"
echo "php artisan cache:clear"
echo "php artisan config:clear"
echo "php artisan view:clear"
echo ""
echo "Then test by:"
echo "1. Go to /dashboard/ra-transactions"
echo "2. Click on any deposit transaction"
echo "3. Check Recipient Details section"
echo "4. Should show account name and number (not N/A)"
echo ""
