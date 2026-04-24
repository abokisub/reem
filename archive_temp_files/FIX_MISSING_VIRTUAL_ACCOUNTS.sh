#!/bin/bash

# ============================================
# FIX MISSING VIRTUAL ACCOUNTS SCRIPT
# ============================================
# This script regenerates missing virtual accounts for company users
# and optionally assigns fresh KYC from the global pool
#
# Usage:
#   ./FIX_MISSING_VIRTUAL_ACCOUNTS.sh
#
# Author: Kiro AI Assistant
# Date: 2026-04-23
# ============================================

set -e  # Exit on error

echo "============================================"
echo "FIX MISSING VIRTUAL ACCOUNTS"
echo "============================================"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}Error: artisan file not found. Please run this script from the Laravel root directory.${NC}"
    exit 1
fi

echo -e "${YELLOW}This script will:${NC}"
echo "1. Check the global KYC pool status"
echo "2. Find companies with users missing virtual accounts"
echo "3. Optionally assign fresh KYC from the pool"
echo "4. Regenerate missing virtual accounts"
echo ""

# Step 1: Check Global KYC Pool
echo -e "${GREEN}Step 1: Checking Global KYC Pool Status...${NC}"
php artisan tinker --execute="
\$stats = (new \App\Services\GlobalKycService())->getUsageStats();
echo '=== GLOBAL KYC POOL STATUS ===' . PHP_EOL;
echo 'Total KYC: ' . \$stats['pool_stats']['total_kyc'] . PHP_EOL;
echo 'Available KYC: ' . \$stats['pool_stats']['available_kyc'] . PHP_EOL;
echo 'Blacklisted KYC: ' . \$stats['pool_stats']['blacklisted_kyc'] . PHP_EOL;
echo 'Overall Success Rate: ' . \$stats['usage_stats']['overall_success_rate'] . '%' . PHP_EOL;
echo PHP_EOL;

\$available = \App\Models\GlobalKycPool::available()->get();
foreach (\$available as \$kyc) {
    echo '- ID: ' . \$kyc->id . ', Type: ' . strtoupper(\$kyc->kyc_type) . ', Number: ' . substr(\$kyc->kyc_number, 0, 5) . '***, Usage: ' . \$kyc->usage_count . '/' . (\$kyc->max_usage ?? 'unlimited') . ', Success Rate: ' . round(\$kyc->success_rate, 2) . '%' . PHP_EOL;
}
"
echo ""

# Step 2: Find companies with missing virtual accounts
echo -e "${GREEN}Step 2: Finding companies with missing virtual accounts...${NC}"
php artisan tinker --execute="
\$companies = \App\Models\Company::whereHas('virtualAccounts', function(\$q) {
    // Companies that have virtual accounts
})->get()->filter(function(\$company) {
    // But have users without virtual accounts
    \$usersWithoutVA = \App\Models\CompanyUser::where('company_id', \$company->id)
        ->whereDoesntHave('virtualAccounts')
        ->count();
    return \$usersWithoutVA > 0;
});

echo '=== COMPANIES WITH MISSING VIRTUAL ACCOUNTS ===' . PHP_EOL;
echo 'Total Companies: ' . \$companies->count() . PHP_EOL;
echo PHP_EOL;

foreach (\$companies as \$company) {
    \$missingCount = \App\Models\CompanyUser::where('company_id', \$company->id)
        ->whereDoesntHave('virtualAccounts')
        ->count();
    
    echo '- Company ID: ' . \$company->id . ', Name: ' . \$company->name . ', Missing VAs: ' . \$missingCount . PHP_EOL;
}
"
echo ""

# Step 3: Interactive mode
echo -e "${YELLOW}Choose an option:${NC}"
echo "1. Fix specific company (Oyitipay)"
echo "2. Fix specific company by name (interactive)"
echo "3. Fix specific company by ID (interactive)"
echo "4. Dry run for specific company"
echo "5. Exit"
echo ""
read -p "Enter your choice (1-5): " choice

case $choice in
    1)
        echo -e "${GREEN}Fixing Oyitipay...${NC}"
        echo ""
        echo -e "${YELLOW}Do you want to assign fresh KYC from the pool? (y/n)${NC}"
        read -p "> " assign_kyc
        
        if [ "$assign_kyc" = "y" ] || [ "$assign_kyc" = "Y" ]; then
            php artisan kyc:regenerate-missing-accounts --company-name="Oyitipay" --assign-fresh-kyc
        else
            php artisan kyc:regenerate-missing-accounts --company-name="Oyitipay"
        fi
        ;;
    2)
        echo -e "${GREEN}Enter company name to search:${NC}"
        read -p "> " company_name
        
        echo -e "${YELLOW}Do you want to assign fresh KYC from the pool? (y/n)${NC}"
        read -p "> " assign_kyc
        
        if [ "$assign_kyc" = "y" ] || [ "$assign_kyc" = "Y" ]; then
            php artisan kyc:regenerate-missing-accounts --company-name="$company_name" --assign-fresh-kyc
        else
            php artisan kyc:regenerate-missing-accounts --company-name="$company_name"
        fi
        ;;
    3)
        echo -e "${GREEN}Enter company ID:${NC}"
        read -p "> " company_id
        
        echo -e "${YELLOW}Do you want to assign fresh KYC from the pool? (y/n)${NC}"
        read -p "> " assign_kyc
        
        if [ "$assign_kyc" = "y" ] || [ "$assign_kyc" = "Y" ]; then
            php artisan kyc:regenerate-missing-accounts --company-id="$company_id" --assign-fresh-kyc
        else
            php artisan kyc:regenerate-missing-accounts --company-id="$company_id"
        fi
        ;;
    4)
        echo -e "${GREEN}Enter company name for dry run:${NC}"
        read -p "> " company_name
        
        php artisan kyc:regenerate-missing-accounts --company-name="$company_name" --dry-run
        ;;
    5)
        echo -e "${YELLOW}Exiting...${NC}"
        exit 0
        ;;
    *)
        echo -e "${RED}Invalid choice. Exiting...${NC}"
        exit 1
        ;;
esac

echo ""
echo -e "${GREEN}============================================${NC}"
echo -e "${GREEN}OPERATION COMPLETED${NC}"
echo -e "${GREEN}============================================${NC}"
echo ""
echo "Check the logs for detailed information:"
echo "  tail -f storage/logs/laravel.log"
echo ""
