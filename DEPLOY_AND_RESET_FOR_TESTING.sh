#!/bin/bash

echo "╔════════════════════════════════════════════════════════════╗"
echo "║     Deploy Settlement Fix & Reset for Testing              ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Step 1: Pull latest code
echo -e "${YELLOW}Step 1: Pulling latest code from GitHub...${NC}"
git pull origin main
if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Failed to pull code${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Code updated${NC}"
echo ""

# Step 2: Clear Laravel caches
echo -e "${YELLOW}Step 2: Clearing Laravel caches...${NC}"
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
echo -e "${GREEN}✅ Caches cleared${NC}"
echo ""

# Step 3: Ask if user wants to reset
echo -e "${YELLOW}Step 3: Reset system for testing?${NC}"
echo "This will delete all transactions and reset balances."
echo -n "Do you want to reset? (yes/no): "
read -r answer

if [ "$answer" = "yes" ]; then
    echo ""
    echo -e "${YELLOW}Running reset script...${NC}"
    php reset_for_fresh_testing.php
    echo ""
fi

# Step 4: Show next steps
echo ""
echo "╔════════════════════════════════════════════════════════════╗"
echo "║                  DEPLOYMENT COMPLETE! ✅                   ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""
echo "📋 Next Steps:"
echo ""
echo "1. Make a test deposit to a CLIENT virtual account"
echo "   Get account number: php -r \"require 'vendor/autoload.php'; \\\$app = require 'bootstrap/app.php'; \\\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap(); \\\$va = DB::table('virtual_accounts')->whereNotNull('company_user_id')->first(); echo 'Client VA: ' . \\\$va->palmpay_account_number . PHP_EOL;\""
echo ""
echo "2. Check the transaction:"
echo "   php check_all_transactions.php"
echo ""
echo "3. Verify settlement queue:"
echo "   php check_settlement_queue.php"
echo ""
echo "4. Check admin page:"
echo "   https://kobopoint.com/secure/pending-settlements"
echo ""
echo "Expected Results:"
echo "  ✅ Client deposits: settlement_status = 'unsettled'"
echo "  ✅ Master deposits: settlement_status = 'settled'"
echo "  ✅ Unsettled transactions in settlement_queue"
echo "  ✅ Company dashboard shows 'Pending Settlement'"
echo ""
