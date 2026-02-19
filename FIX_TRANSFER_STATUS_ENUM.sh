#!/bin/bash

echo "=========================================="
echo "Fix Transfer Status Enum - Production"
echo "=========================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}This script will fix the transaction status enum issue${NC}"
echo ""

# Step 1: Pull latest code
echo -e "${YELLOW}Step 1: Pulling latest code from GitHub...${NC}"
git pull origin main
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Code pulled successfully${NC}"
else
    echo -e "${RED}✗ Failed to pull code${NC}"
    exit 1
fi
echo ""

# Step 2: Run the migration
echo -e "${YELLOW}Step 2: Running migration to expand status enum...${NC}"
php artisan migrate --force
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Migration completed successfully${NC}"
else
    echo -e "${RED}✗ Migration failed${NC}"
    exit 1
fi
echo ""

# Step 3: Clear caches
echo -e "${YELLOW}Step 3: Clearing caches...${NC}"
php artisan cache:clear
php artisan config:clear
php artisan route:clear
echo -e "${GREEN}✓ Caches cleared${NC}"
echo ""

# Step 4: Check for any failed transactions that need refunding
echo -e "${YELLOW}Step 4: Checking for transactions that may need attention...${NC}"
php artisan tinker --execute="
\$count = DB::table('transactions')
    ->where('status', 'debited')
    ->where('created_at', '>', now()->subHours(24))
    ->count();
echo \"Found \$count transactions in 'debited' status from last 24 hours\n\";
"
echo ""

echo -e "${GREEN}=========================================="
echo -e "Fix Applied Successfully!"
echo -e "==========================================${NC}"
echo ""
echo "Next steps:"
echo "1. Test a transfer to verify it works"
echo "2. Monitor logs: tail -f storage/logs/laravel.log"
echo ""
