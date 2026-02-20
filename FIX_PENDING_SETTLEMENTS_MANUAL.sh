#!/bin/bash

echo "=========================================="
echo "Fix Pending Settlements (Manual)"
echo "=========================================="
echo ""
echo "This will fix the 2 pending settlements showing in admin panel"
echo ""

echo "1. Running fix script..."
php fix_pending_settlements_manual.php

echo ""
echo "2. Clearing Laravel caches..."
php artisan cache:clear
php artisan config:clear
php artisan view:clear

echo ""
echo "=========================================="
echo "✅ Fix Complete!"
echo "=========================================="
echo ""
echo "NEXT STEPS:"
echo "1. Refresh the admin pending settlements page"
echo "2. The 2 transactions should now show as 'settled'"
echo "3. They should disappear from the pending settlements list"
echo ""
