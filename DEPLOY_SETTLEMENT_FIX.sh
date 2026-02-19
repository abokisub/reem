#!/bin/bash

echo "=========================================="
echo "🚀 DEPLOYING SETTLEMENT DELAY FIX"
echo "=========================================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Step 1: Clear caches
echo -e "${YELLOW}Step 1: Clearing Laravel caches...${NC}"
php artisan cache:clear
php artisan config:clear
php artisan route:clear
echo -e "${GREEN}✓ Caches cleared${NC}"
echo ""

# Step 2: Process stuck settlements
echo -e "${YELLOW}Step 2: Processing stuck settlements...${NC}"
php PROCESS_PENDING_SETTLEMENTS_NOW.php
echo -e "${GREEN}✓ Settlements processed${NC}"
echo ""

# Step 3: Check wallet balance
echo -e "${YELLOW}Step 3: Checking wallet balance...${NC}"
php artisan tinker --execute="echo 'Balance: ₦' . number_format(App\Models\CompanyWallet::where('company_id', 1)->first()->balance, 2);"
echo ""

# Step 4: Check pending settlements
echo -e "${YELLOW}Step 4: Checking pending settlements...${NC}"
php artisan tinker --execute="echo 'Pending: ' . DB::table('settlement_queue')->where('status', 'pending')->count();"
echo ""

echo "=========================================="
echo -e "${GREEN}✅ DEPLOYMENT COMPLETE${NC}"
echo "=========================================="
echo ""
echo -e "${YELLOW}⚠️  IMPORTANT: Set up cron job!${NC}"
echo ""
echo "Run this command:"
echo -e "${GREEN}crontab -e${NC}"
echo ""
echo "Add this line:"
echo -e "${GREEN}* * * * * cd $(pwd) && php artisan settlements:process >> /dev/null 2>&1${NC}"
echo ""
echo "Save and exit (Ctrl+X, Y, Enter)"
echo ""
echo "Verify with:"
echo -e "${GREEN}crontab -l${NC}"
echo ""
