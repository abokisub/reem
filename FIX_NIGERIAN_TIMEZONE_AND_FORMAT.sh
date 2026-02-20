#!/bin/bash

echo "=========================================="
echo "FIX: Nigerian Timezone and Date Format"
echo "=========================================="
echo ""
echo "ISSUE: Dates showing in UTC format (2026-02-20T14:28:14.000000Z)"
echo "FIX: Convert entire system to Nigerian time (WAT - Africa/Lagos)"
echo ""

# Step 1: Push backend changes to GitHub
echo "Step 1: Pushing backend changes to GitHub..."
git add config/app.php
git commit -m "Fix: Set application timezone to Africa/Lagos (Nigerian time)

- Changed timezone from UTC to Africa/Lagos (WAT - UTC+1)
- All backend timestamps will now use Nigerian time
- Affects all database operations and API responses"

git push origin main

if [ $? -eq 0 ]; then
    echo "✅ Backend changes pushed to GitHub"
else
    echo "❌ Failed to push backend changes"
    exit 1
fi

echo ""
echo "Step 2: Pushing frontend changes to GitHub..."
git add frontend/src/utils/formatTime.js \
        frontend/src/pages/dashboard/RATransactionDetails.js \
        frontend/src/pages/dashboard/RATransactions.js \
        frontend/src/pages/dashboard/wallet-summary.js \
        frontend/src/pages/admin/AdminStatement.js \
        frontend/package.json

git commit -m "Fix: Convert all date displays to Nigerian time and format

- Updated formatTime.js to use date-fns-tz for timezone conversion
- All dates now convert from UTC to Africa/Lagos (WAT)
- Updated inline date formatters in all transaction pages
- Format: DD/MM/YYYY HH:MM:SS WAT (24-hour format)
- Receipt page format: DD MMM YYYY, H:MM AM/PM WAT (12-hour format)
- Added date-fns-tz package dependency"

git push origin main

if [ $? -eq 0 ]; then
    echo "✅ Frontend changes pushed to GitHub"
else
    echo "❌ Failed to push frontend changes"
    exit 1
fi

echo ""
echo "=========================================="
echo "NEXT: Deploy on Server"
echo "=========================================="
echo ""
echo "Run these commands on the server:"
echo ""
echo "# Backend deployment:"
echo "cd /home/aboksdfs/app.pointwave.ng"
echo "git pull origin main"
echo "php artisan cache:clear"
echo "php artisan config:clear"
echo ""
echo "# Frontend deployment:"
echo "cd /home/aboksdfs/app.pointwave.ng/frontend"
echo "npm install  # Install date-fns-tz package"
echo "npm run build"
echo "cd .."
echo "php artisan view:clear"
echo ""
echo "=========================================="
echo "EXPECTED RESULTS"
echo "=========================================="
echo ""
echo "Before: 2026-02-20T14:28:14.000000Z"
echo "After:  20/02/2026 15:28:14 WAT"
echo ""
echo "Receipt page:"
echo "Before: 2026-02-20T14:28:14.000000Z"
echo "After:  20 Feb 2026, 3:28 PM WAT"
echo ""
echo "Note: WAT is UTC+1, so times will be 1 hour ahead of UTC"
echo ""
