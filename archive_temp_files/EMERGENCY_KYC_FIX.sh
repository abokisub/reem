#!/bin/bash

# Emergency KYC Fix Deployment Script
# This script checks for companies with KYC issues and fixes them

set -e

echo "╔════════════════════════════════════════════════════════════╗"
echo "║         EMERGENCY KYC FIX - DEPLOYMENT SCRIPT             ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Step 1: Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}Error: artisan file not found. Please run this script from the Laravel root directory.${NC}"
    exit 1
fi

echo -e "${GREEN}✓ Found Laravel project${NC}"
echo ""

# Step 2: Pull latest changes (optional)
echo "Do you want to pull latest changes from git? (y/n)"
read -r pull_response

if [[ "$pull_response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
    echo "Pulling latest changes..."
    git pull origin main || git pull origin master
    echo -e "${GREEN}✓ Git pull complete${NC}"
    echo ""
fi

# Step 3: Run composer install (if needed)
if [ -f "composer.json" ]; then
    echo "Do you want to run composer install? (y/n)"
    read -r composer_response
    
    if [[ "$composer_response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
        echo "Running composer install..."
        composer install --no-dev --optimize-autoloader
        echo -e "${GREEN}✓ Composer install complete${NC}"
        echo ""
    fi
fi

# Step 4: Clear cache
echo "Clearing Laravel cache..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo -e "${GREEN}✓ Cache cleared${NC}"
echo ""

# Step 5: Run the emergency check (check-only mode first)
echo "════════════════════════════════════════════════════════════"
echo "STEP 1: CHECKING SYSTEM STATUS (Read-only)"
echo "════════════════════════════════════════════════════════════"
echo ""

php artisan emergency:kyc-fix --check-only

echo ""
echo "════════════════════════════════════════════════════════════"
echo "STEP 2: APPLY FIXES"
echo "════════════════════════════════════════════════════════════"
echo ""

# Step 6: Ask if user wants to apply fixes
echo -e "${YELLOW}Do you want to apply fixes now? (y/n)${NC}"
read -r fix_response

if [[ "$fix_response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
    echo ""
    echo "Applying fixes..."
    php artisan emergency:kyc-fix --auto-fix
    
    echo ""
    echo -e "${GREEN}✓ Fixes applied successfully!${NC}"
else
    echo ""
    echo -e "${YELLOW}No fixes applied. You can run fixes manually with:${NC}"
    echo "php artisan emergency:kyc-fix"
fi

echo ""
echo "════════════════════════════════════════════════════════════"
echo "MANUAL COMMANDS (if needed)"
echo "════════════════════════════════════════════════════════════"
echo ""
echo "Check specific company:"
echo "  php artisan emergency:kyc-fix --company=4 --check-only"
echo ""
echo "Fix specific company:"
echo "  php artisan emergency:kyc-fix --company=4"
echo ""
echo "Assign fresh KYC to specific company:"
echo "  php artisan company:assign-fresh-kyc 4 --type=nin --regenerate"
echo ""
echo "Check Kobopoint status:"
echo "  php check_kobopoint_kyc.php"
echo ""
echo "════════════════════════════════════════════════════════════"
echo -e "${GREEN}DEPLOYMENT COMPLETE!${NC}"
echo "════════════════════════════════════════════════════════════"
