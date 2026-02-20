#!/bin/bash

# Frontend Transaction Normalization Deployment Script
# Date: February 21, 2026
# Purpose: Deploy updated RA Transactions frontend component

set -e  # Exit on error

echo "=========================================="
echo "Frontend Transaction Normalization Deploy"
echo "=========================================="
echo ""

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Check if we're in the right directory
if [ ! -d "frontend" ]; then
    echo -e "${RED}Error: frontend directory not found${NC}"
    echo "Please run this script from the app root directory"
    exit 1
fi

echo -e "${YELLOW}Step 1: Pulling latest changes from GitHub${NC}"
git pull origin main
echo -e "${GREEN}✓ Git pull complete${NC}"
echo ""

echo -e "${YELLOW}Step 2: Installing frontend dependencies${NC}"
cd frontend
npm install
echo -e "${GREEN}✓ Dependencies installed${NC}"
echo ""

echo -e "${YELLOW}Step 3: Building frontend for production${NC}"
npm run build
echo -e "${GREEN}✓ Frontend build complete${NC}"
echo ""

echo -e "${YELLOW}Step 4: Deploying build to public directory${NC}"
rsync -av --delete build/ ../public/
echo -e "${GREEN}✓ Build deployed to public directory${NC}"
echo ""

echo -e "${YELLOW}Step 5: Clearing Laravel caches${NC}"
cd ..
php artisan cache:clear
php artisan view:clear
php artisan config:clear
echo -e "${GREEN}✓ Caches cleared${NC}"
echo ""

echo -e "${GREEN}=========================================="
echo "Frontend Deployment Complete!"
echo "==========================================${NC}"
echo ""
echo "Next steps:"
echo "1. Test the RA Transactions page in your browser"
echo "2. Verify new columns display correctly:"
echo "   - Transaction Ref"
echo "   - Session ID"
echo "   - Transaction Type"
echo "   - Net Amount"
echo "   - Settlement Status (no N/A values)"
echo "3. Test copy buttons for transaction_ref and session_id"
echo "4. Verify backward compatibility with legacy data"
echo "5. Check browser console for any errors"
echo ""
echo "If you see old layout, do a hard refresh:"
echo "  - Windows: Ctrl+F5"
echo "  - Mac: Cmd+Shift+R"
echo ""
echo -e "${GREEN}Deployment successful!${NC}"
