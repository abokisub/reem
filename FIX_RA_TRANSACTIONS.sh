#!/bin/bash

echo "=========================================="
echo "Fix RA Transactions - Complete Setup"
echo "=========================================="
echo ""

# Step 1: Run migration
echo "📦 Step 1: Running migration..."
php artisan migrate --force

if [ $? -ne 0 ]; then
    echo "❌ Migration failed!"
    exit 1
fi

echo "✅ Migration complete"
echo ""

# Step 2: Clear caches
echo "🧹 Step 2: Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear

echo "✅ Caches cleared"
echo ""

# Step 3: Verify setup
echo "🔍 Step 3: Verifying setup..."
php check_ra_transaction_api.php

echo ""
echo "=========================================="
echo "✅ Setup Complete"
echo "=========================================="
echo ""
echo "📝 Next Steps:"
echo "1. Clear browser cache (Ctrl+Shift+Delete)"
echo "2. Refresh the RA Transactions page"
echo "3. Test refund and notification buttons"
echo ""
echo "⚠️  Note: Old transactions may still show 'N/A' for sender details"
echo "   because they don't have metadata. New transactions from PalmPay"
echo "   will include sender information automatically."
echo ""
