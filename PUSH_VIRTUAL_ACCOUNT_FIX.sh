#!/bin/bash

# ============================================
# PUSH VIRTUAL ACCOUNT FIX TO GITHUB
# ============================================

set -e

echo "============================================"
echo "PUSHING VIRTUAL ACCOUNT FIX TO GITHUB"
echo "============================================"
echo ""

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

echo -e "${YELLOW}Files to be committed:${NC}"
echo "1. app/Console/Commands/RegenerateMissingVirtualAccounts.php"
echo "2. FIX_MISSING_VIRTUAL_ACCOUNTS.sh"
echo "3. VIRTUAL_ACCOUNT_FIX_INSTRUCTIONS.md"
echo "4. QUICK_FIX_REFERENCE.md"
echo "5. PUSH_VIRTUAL_ACCOUNT_FIX.sh"
echo ""

# Check git status
echo -e "${GREEN}Checking git status...${NC}"
git status

echo ""
read -p "Continue with commit and push? (y/n): " confirm

if [ "$confirm" != "y" ] && [ "$confirm" != "Y" ]; then
    echo "Aborted."
    exit 0
fi

# Add files
echo -e "${GREEN}Adding files...${NC}"
git add app/Console/Commands/RegenerateMissingVirtualAccounts.php
git add FIX_MISSING_VIRTUAL_ACCOUNTS.sh
git add VIRTUAL_ACCOUNT_FIX_INSTRUCTIONS.md
git add QUICK_FIX_REFERENCE.md
git add PUSH_VIRTUAL_ACCOUNT_FIX.sh

# Commit
echo -e "${GREEN}Committing...${NC}"
git commit -m "Add command to regenerate missing virtual accounts with fresh KYC assignment

- New artisan command: kyc:regenerate-missing-accounts
- Handles users missing virtual accounts due to KYC limits
- Automatically assigns fresh KYC from global pool
- Includes dry-run mode for safety
- Interactive script for easy deployment
- Comprehensive logging and error handling

Fixes: PalmPay Error AC100009 (licenseNumber duplicate)"

# Push
echo -e "${GREEN}Pushing to GitHub...${NC}"
git push origin main

echo ""
echo -e "${GREEN}============================================${NC}"
echo -e "${GREEN}SUCCESSFULLY PUSHED TO GITHUB${NC}"
echo -e "${GREEN}============================================${NC}"
echo ""
echo "Next steps on live server:"
echo "1. cd /path/to/your/app"
echo "2. git pull origin main"
echo "3. chmod +x FIX_MISSING_VIRTUAL_ACCOUNTS.sh"
echo "4. ./FIX_MISSING_VIRTUAL_ACCOUNTS.sh"
echo ""
