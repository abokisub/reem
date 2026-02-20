#!/bin/bash

echo "=== DEPLOYING USERNAME COLUMN TO COMPANIES TABLE ==="
echo ""

# Pull latest code
echo "Step 1: Pulling latest code..."
git pull origin main

# Run migration
echo ""
echo "Step 2: Running migration..."
php artisan migrate --force

# Fix existing companies
echo ""
echo "Step 3: Setting username for existing companies..."
php fix_company_username.php

# Test receipt generation
echo ""
echo "Step 4: Testing receipt generation..."
php test_receipt_generation.php

echo ""
echo "=== DEPLOYMENT COMPLETE ==="
echo ""
echo "Next: Test the receipt page in your browser to verify all fields display correctly."
