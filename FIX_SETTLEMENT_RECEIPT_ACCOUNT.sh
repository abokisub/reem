#!/bin/bash

echo "========================================="
echo "Settlement Receipt Account Fix"
echo "========================================="
echo ""

# Pull latest code
echo "1. Pulling latest code from GitHub..."
git pull origin main
echo ""

# Run diagnostic to see current data
echo "2. Running diagnostic to check data..."
php check_settlement_account_data.php
echo ""

# Clear all Laravel caches
echo "3. Clearing Laravel caches..."
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
echo ""

# Generate a new receipt to test
echo "4. Testing receipt generation..."
echo "Visit the receipt URL to see if account number now shows correctly"
echo ""

echo "✅ Done! Check the settlement withdrawal receipt now."
echo "The sender account should show: 7040540018"
