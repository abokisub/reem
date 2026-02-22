#!/bin/bash

# Delete Test Virtual Accounts for a Company
# This script helps clean up test accounts so companies can start fresh

echo "=========================================="
echo "Delete Company Test Accounts"
echo "=========================================="
echo ""
echo "This script will delete ALL virtual accounts for a company."
echo "Use this to clean up test accounts and start fresh."
echo ""
echo "⚠️  WARNING: This action cannot be undone!"
echo ""

# Default company ID (PointWave Business / KoboPoint)
COMPANY_ID=2

echo "Company ID to clean: $COMPANY_ID (PointWave Business / KoboPoint)"
echo ""
echo "Options:"
echo "1. Dry run (see what would be deleted)"
echo "2. Delete all test accounts"
echo "3. Cancel"
echo ""
read -p "Choose option (1-3): " choice

case $choice in
    1)
        echo ""
        echo "Running dry run..."
        php cleanup_company_test_accounts.php
        ;;
    2)
        echo ""
        read -p "Are you ABSOLUTELY sure? Type 'DELETE' to confirm: " confirm
        if [ "$confirm" = "DELETE" ]; then
            echo ""
            echo "Deleting test accounts..."
            # Edit the script to set dryRun = false
            sed -i 's/\$dryRun = true;/\$dryRun = false;/g' cleanup_company_test_accounts.php
            php cleanup_company_test_accounts.php
            # Restore dryRun = true
            sed -i 's/\$dryRun = false;/\$dryRun = true;/g' cleanup_company_test_accounts.php
        else
            echo "❌ Confirmation failed. Aborting."
        fi
        ;;
    3)
        echo "Cancelled."
        exit 0
        ;;
    *)
        echo "Invalid option. Aborting."
        exit 1
        ;;
esac

echo ""
