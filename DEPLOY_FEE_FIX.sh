#!/bin/bash

# KoboPoint Transfer Fee Fix Deployment Script
# This script deploys the fix for correct ₦30 transfer fee

echo "=========================================="
echo "KoboPoint Transfer Fee Fix Deployment"
echo "=========================================="
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Step 1: Commit changes
echo -e "${YELLOW}Step 1: Committing changes...${NC}"
git add app/Http/Controllers/API/V1/MerchantApiController.php
git add MESSAGE_TO_KOBOPOINT.md
git add KOBOPOINT_FEE_FIX_SUMMARY.md
git add DEPLOY_FEE_FIX.sh
git commit -m "Fix: Use correct payout_bank_charge settings for API transfers (₦30 fee)

- Changed MerchantApiController to use payout_bank_charge_* settings
- This matches the dashboard fee calculation (₦30 for external transfers)
- Previously was using payout_palmpay_charge_* (₦15 for settlements)
- Fixes KoboPoint API integration fee discrepancy"

echo -e "${GREEN}✓ Changes committed${NC}"
echo ""

# Step 2: Push to GitHub
echo -e "${YELLOW}Step 2: Pushing to GitHub...${NC}"
git push
echo -e "${GREEN}✓ Pushed to GitHub${NC}"
echo ""

# Step 3: Instructions for server deployment
echo -e "${YELLOW}Step 3: Deploy on production server${NC}"
echo ""
echo "Run these commands on the server:"
echo ""
echo -e "${GREEN}cd /home/aboksdfs/app.pointwave.ng${NC}"
echo -e "${GREEN}git pull${NC}"
echo -e "${GREEN}php artisan config:clear${NC}"
echo -e "${GREEN}php artisan cache:clear${NC}"
echo ""

# Step 4: Clear OPcache
echo -e "${YELLOW}Step 4: Clear OPcache${NC}"
echo ""
echo "Generate today's secret:"
echo -e "${GREEN}echo -n \"pointwave_opcache_clear_\$(date +%Y-%m-%d)\" | md5sum | cut -d' ' -f1${NC}"
echo ""
echo "Then visit:"
echo -e "${GREEN}https://app.pointwave.ng/clear-opcache.php?secret=YOUR_SECRET${NC}"
echo ""

# Step 5: Verify
echo -e "${YELLOW}Step 5: Verify the fix${NC}"
echo ""
echo "Test the API endpoint to confirm ₦30 fee:"
echo ""
echo -e "${GREEN}curl -X POST https://app.pointwave.ng/api/v1/banks/transfer \\
  -H \"Authorization: Bearer SECRET\" \\
  -H \"x-business-id: BUSINESS_ID\" \\
  -H \"x-api-key: API_KEY\" \\
  -H \"Content-Type: application/json\" \\
  -d '{
    \"amount\": 100,
    \"bank_code\": \"000004\",
    \"account_number\": \"7040540018\",
    \"account_name\": \"Test Account\"
  }'${NC}"
echo ""
echo "Expected response should show:"
echo "  - fee: 30"
echo "  - total_deducted: 130"
echo ""

echo -e "${GREEN}=========================================="
echo "Deployment script completed!"
echo "==========================================${NC}"
echo ""
echo "Summary of changes:"
echo "  ✓ API now uses payout_bank_charge_* settings (₦30)"
echo "  ✓ Matches dashboard fee calculation"
echo "  ✓ KoboPoint will see correct ₦30 fee"
echo ""
echo "Next: Deploy on server and clear OPcache"
