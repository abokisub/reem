#!/bin/bash

echo "╔════════════════════════════════════════════════════════════╗"
echo "║    DEPLOY ALL PENDING FIXES TO PRODUCTION                  ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Change to app directory
cd /home/aboksdfs/app.pointwave.ng || exit 1

echo -e "${YELLOW}📥 Step 1: Pull latest code from GitHub${NC}"
git pull origin main
if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Git pull failed!${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Code updated${NC}"
echo ""

echo -e "${YELLOW}🗄️  Step 2: Run database migrations${NC}"
php artisan migrate --force
if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Migration failed!${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Migrations completed${NC}"
echo ""

echo -e "${YELLOW}🧹 Step 3: Clear all caches${NC}"
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
echo -e "${GREEN}✅ Caches cleared${NC}"
echo ""

echo -e "${YELLOW}🔍 Step 4: Verify VA deposit fee configuration${NC}"
php verify_va_deposit_fee_update.php
echo ""

echo "╔════════════════════════════════════════════════════════════╗"
echo "║    DEPLOYMENT COMPLETE                                      ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""
echo -e "${GREEN}✅ All fixes deployed successfully!${NC}"
echo ""
echo "📋 WHAT WAS FIXED:"
echo "  1. TransferService dependency injection error"
echo "  2. VA deposit fee configuration (virtual_funding_* columns added)"
echo "  3. All caches cleared"
echo ""
echo "🧪 NEXT STEPS:"
echo "  1. Test a transfer to confirm no more constructor errors"
echo "  2. Update VA deposit fee in admin panel: /secure/discount/banks"
echo "  3. Test a VA deposit to confirm fee matches admin panel setting"
echo ""
echo "📊 To monitor VA deposits:"
echo "  tail -f storage/logs/laravel.log | grep 'Virtual Account Credited'"
echo ""
