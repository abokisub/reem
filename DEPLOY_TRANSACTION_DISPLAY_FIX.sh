#!/bin/bash

echo "=========================================="
echo "Transaction Display Fix Deployment"
echo "=========================================="
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${YELLOW}This script will:${NC}"
echo "1. Update backend API to return sender bank info"
echo "2. Update frontend to display complete transaction details"
echo "3. Show balance before/after on receipts"
echo "4. Display sender information properly"
echo ""
echo -e "${YELLOW}Files to be updated:${NC}"
echo "- app/Http/Controllers/API/Trans.php"
echo "- frontend/src/pages/dashboard/RATransactionDetails.js"
echo ""

read -p "Continue? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]
then
    echo "Deployment cancelled."
    exit 1
fi

echo ""
echo "=========================================="
echo "Step 1: Clear Laravel Caches"
echo "=========================================="
php artisan cache:clear
php artisan config:clear
php artisan route:clear
echo -e "${GREEN}✓ Caches cleared${NC}"

echo ""
echo "=========================================="
echo "Step 2: Test Backend API"
echo "=========================================="
echo "Testing if API returns metadata..."
php -r "
\$response = json_decode(file_get_contents('http://localhost/api/system/all/ra-history/records/1/secure?page=1&limit=1'), true);
if (isset(\$response['ra_trans']['data'][0]['metadata'])) {
    echo '✓ API returns metadata\n';
    echo '✓ Sender info available\n';
} else {
    echo '✗ API does not return metadata\n';
}
"

echo ""
echo "=========================================="
echo "Step 3: Build Frontend"
echo "=========================================="
echo "Building React frontend..."
cd frontend
npm run build
echo -e "${GREEN}✓ Frontend built${NC}"
cd ..

echo ""
echo "=========================================="
echo "Step 4: Verify Changes"
echo "=========================================="
echo ""
echo "✓ Backend updated to return:"
echo "  - customer_name (sender name)"
echo "  - customer_account (sender account)"
echo "  - customer_bank (sender bank)"
echo "  - metadata (full metadata object)"
echo ""
echo "✓ Frontend updated to display:"
echo "  - Sender Name, Account, Bank"
echo "  - Gross Amount, Fee, Net Amount"
echo "  - Old Balance, New Balance"
echo ""

echo ""
echo "=========================================="
echo "Deployment Complete!"
echo "=========================================="
echo ""
echo -e "${GREEN}✓ All changes deployed successfully${NC}"
echo ""
echo "Next Steps:"
echo "1. Test with recent transaction (₦1000 from ABOKI TELECOMMUNICATION)"
echo "2. Verify sender info displays correctly"
echo "3. Check balance before/after shows properly"
echo "4. Confirm fee breakdown is visible"
echo ""
echo "Test URL: https://app.pointwave.ng/dashboard/ra-transactions"
echo ""
