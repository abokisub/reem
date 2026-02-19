#!/bin/bash

echo "=========================================="
echo "Settlement System Fix"
echo "=========================================="
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${YELLOW}This script will:${NC}"
echo "1. Process all pending settlements immediately"
echo "2. Credit funds to company wallets"
echo "3. Show you the results"
echo ""

read -p "Continue? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]
then
    echo "Cancelled."
    exit 1
fi

echo ""
echo "=========================================="
echo "Step 1: Check Pending Settlements"
echo "=========================================="
php artisan tinker --execute="echo 'Pending: ' . DB::table('settlement_queue')->where('status', 'pending')->count();"

echo ""
echo "=========================================="
echo "Step 2: Process Settlements"
echo "=========================================="
php PROCESS_PENDING_SETTLEMENTS_NOW.php

echo ""
echo "=========================================="
echo "Step 3: Verify Wallet Balance"
echo "=========================================="
php artisan tinker --execute="
\$wallet = App\Models\CompanyWallet::where('company_id', 1)->first();
if (\$wallet) {
    echo 'Company Wallet Balance: ₦' . number_format(\$wallet->balance, 2) . PHP_EOL;
} else {
    echo 'Wallet not found' . PHP_EOL;
}
"

echo ""
echo "=========================================="
echo "Step 4: Check Remaining Pending"
echo "=========================================="
php artisan tinker --execute="echo 'Still Pending: ' . DB::table('settlement_queue')->where('status', 'pending')->count();"

echo ""
echo "=========================================="
echo "Next Steps"
echo "=========================================="
echo ""
echo -e "${GREEN}✓ Settlements processed!${NC}"
echo ""
echo "To prevent this in future, set up a cron job:"
echo ""
echo "  * * * * * cd $(pwd) && php artisan settlements:process"
echo ""
echo "Or disable settlement queue for immediate crediting:"
echo "  Go to: Admin → Discount/Charges → Bank Transfer Charges"
echo "  Uncheck 'Enable Auto Settlement'"
echo ""
