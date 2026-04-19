#!/bin/bash

echo "=== Emergency Fix for Kobopoint Virtual Accounts ==="
echo ""
echo "Step 1: Checking current status..."
php check_missing_kobopoint_accounts.php

echo ""
echo "Step 2: Do you want to regenerate missing accounts? (y/n)"
read -r response

if [[ "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
    echo ""
    echo "Regenerating accounts..."
    echo "Note: This uses the admin dashboard regenerate function"
    echo "Please click 'Regenerate Accounts' in your admin dashboard"
    echo ""
    echo "OR run this command manually:"
    echo "php artisan company:assign-fresh-kyc 4 --regenerate"
else
    echo "Operation cancelled"
fi

echo ""
echo "Done!"
