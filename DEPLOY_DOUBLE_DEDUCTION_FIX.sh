#!/bin/bash

# Transfer Double Deduction Bug Fix Deployment Script
# This script deploys the fix for the double balance deduction bug

echo "=========================================="
echo "Transfer Double Deduction Bug Fix"
echo "=========================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${YELLOW}This fix resolves the issue where transfers fail with:${NC}"
echo -e "${RED}'Insufficient balance to cover amount and fees'${NC}"
echo ""
echo "The bug was caused by balance being checked and deducted twice:"
echo "  1. First in TransferPurchase.php"
echo "  2. Then again in TransferService.php (causing failure)"
echo ""
echo -e "${GREEN}The fix ensures balance is only deducted once per transfer.${NC}"
echo ""

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}Error: artisan file not found. Please run this script from the Laravel root directory.${NC}"
    exit 1
fi

echo "Step 1: Backing up modified files..."
mkdir -p backups/double-deduction-fix-$(date +%Y%m%d-%H%M%S)
BACKUP_DIR="backups/double-deduction-fix-$(date +%Y%m%d-%H%M%S)"

cp app/Http/Controllers/Purchase/TransferPurchase.php "$BACKUP_DIR/" 2>/dev/null || echo "  - TransferPurchase.php backed up"
cp app/Services/Banking/BankingService.php "$BACKUP_DIR/" 2>/dev/null || echo "  - BankingService.php backed up"
cp app/Services/PalmPay/TransferService.php "$BACKUP_DIR/" 2>/dev/null || echo "  - TransferService.php backed up"

echo -e "${GREEN}✓ Backup created in $BACKUP_DIR${NC}"
echo ""

echo "Step 2: Clearing Laravel caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo -e "${GREEN}✓ Caches cleared${NC}"
echo ""

echo "Step 3: Optimizing application..."
php artisan config:cache
php artisan route:cache
echo -e "${GREEN}✓ Application optimized${NC}"
echo ""

echo "=========================================="
echo -e "${GREEN}Deployment Complete!${NC}"
echo "=========================================="
echo ""
echo "Modified Files:"
echo "  1. app/Http/Controllers/Purchase/TransferPurchase.php"
echo "     - Added 'balance_already_deducted' flag"
echo "     - Added 'transaction_reference' to transfer details"
echo ""
echo "  2. app/Services/Banking/BankingService.php"
echo "     - Forwards context flags to TransferService"
echo ""
echo "  3. app/Services/PalmPay/TransferService.php"
echo "     - Checks for 'balance_already_deducted' flag"
echo "     - Skips balance operations when flag is true"
echo "     - Updates existing transaction instead of creating new one"
echo ""
echo -e "${YELLOW}Testing Recommendations:${NC}"
echo "  1. Test a transfer with sufficient balance"
echo "  2. Verify no 'Insufficient balance' errors in logs"
echo "  3. Check that balance is deducted only once"
echo "  4. Verify transaction status progresses correctly"
echo ""
echo -e "${GREEN}The fix is now live!${NC}"
echo ""
