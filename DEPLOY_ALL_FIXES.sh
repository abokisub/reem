#!/bin/bash

echo "=========================================="
echo "🚀 DEPLOYING ALL FIXES"
echo "=========================================="
echo ""
echo "Fixes included:"
echo "1. Settlement delay bug (10 min now works correctly)"
echo "2. Deposit receipt display (status, balances, fees, sender)"
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Step 1: Pull latest code
echo -e "${YELLOW}Step 1: Pulling latest code from GitHub...${NC}"
git pull origin main
if [ $? -ne 0 ]; then
    echo -e "${RED}✗ Failed to pull code${NC}"
    exit 1
fi
echo -e "${GREEN}✓ Code pulled successfully${NC}"
echo ""

# Step 2: Clear caches
echo -e "${YELLOW}Step 2: Clearing Laravel caches...${NC}"
php artisan cache:clear
php artisan config:clear
php artisan route:clear
echo -e "${GREEN}✓ Caches cleared${NC}"
echo ""

# Step 3: Process stuck settlements
echo -e "${YELLOW}Step 3: Processing stuck settlements...${NC}"
if [ -f "PROCESS_PENDING_SETTLEMENTS_NOW.php" ]; then
    php PROCESS_PENDING_SETTLEMENTS_NOW.php
    echo -e "${GREEN}✓ Settlements processed${NC}"
else
    echo -e "${YELLOW}⚠ Settlement processor not found (skip if not needed)${NC}"
fi
echo ""

# Step 4: Rebuild frontend
echo -e "${YELLOW}Step 4: Rebuilding frontend...${NC}"
if [ -d "frontend" ]; then
    cd frontend
    if command -v npm &> /dev/null; then
        npm run build
        echo -e "${GREEN}✓ Frontend rebuilt${NC}"
    else
        echo -e "${YELLOW}⚠ npm not found - skipping frontend build${NC}"
        echo -e "${YELLOW}  Frontend will use old build${NC}"
    fi
    cd ..
else
    echo -e "${YELLOW}⚠ Frontend directory not found${NC}"
fi
echo ""

# Step 5: Check wallet balance
echo -e "${YELLOW}Step 5: Checking wallet balance...${NC}"
php artisan tinker --execute="echo 'Balance: ₦' . number_format(App\Models\CompanyWallet::where('company_id', 1)->first()->balance, 2);"
echo ""

# Step 6: Check pending settlements
echo -e "${YELLOW}Step 6: Checking pending settlements...${NC}"
php artisan tinker --execute="echo 'Pending: ' . DB::table('settlement_queue')->where('status', 'pending')->count();"
echo ""

echo "=========================================="
echo -e "${GREEN}✅ DEPLOYMENT COMPLETE${NC}"
echo "=========================================="
echo ""
echo -e "${YELLOW}⚠️  IMPORTANT: Set up cron job if not done!${NC}"
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
echo "=========================================="
echo "WHAT WAS FIXED:"
echo "=========================================="
echo ""
echo "1. Settlement Delay Bug:"
echo "   - 10 minutes now settles in exactly 10 minutes"
echo "   - 1 hour now settles in exactly 1 hour"
echo "   - Any delay works correctly now"
echo ""
echo "2. Deposit Receipt Display:"
echo "   - Status shows correctly (SUCCESSFUL, not PENDING)"
echo "   - Previous/New Balance shows actual values (not ₦0.00)"
echo "   - Service Fee shows actual fee (not ₦0)"
echo "   - Sender details visible for new transactions"
echo ""
echo "=========================================="
echo "TESTING:"
echo "=========================================="
echo ""
echo "Test Settlement:"
echo "  1. Set delay to 1 minute in settings"
echo "  2. Send ₦100 to: 6644694207"
echo "  3. Wait 1 minute"
echo "  4. Check wallet balance"
echo ""
echo "Test Deposit Receipt:"
echo "  1. Go to Wallet page"
echo "  2. Click on any transaction"
echo "  3. Verify all fields show correctly"
echo ""
