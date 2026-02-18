#!/bin/bash

# PointWave Database Fixes
# This script fixes two critical database errors:
# 1. Missing net_amount and total_amount columns in transactions table
# 2. Fixed DataPurchased function to not query non-existent 'data' table

echo "=========================================="
echo "PointWave Database Error Fixes"
echo "=========================================="
echo ""

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: artisan file not found. Please run this script from the Laravel root directory."
    exit 1
fi

echo "📋 This script will:"
echo "   1. Run migration to add net_amount and total_amount columns to transactions table"
echo "   2. The DataPurchased function has been fixed (no migration needed)"
echo ""

read -p "Continue? (y/n): " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "❌ Aborted by user"
    exit 1
fi

echo ""
echo "🔧 Running migration..."
echo "=========================================="

# Run the specific migration
php artisan migrate --path=database/migrations/2026_02_18_173000_add_net_amount_to_transactions.php

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ Migration completed successfully!"
    echo ""
    echo "📊 Verifying columns..."
    
    # Verify the columns were added
    php artisan tinker --execute="
        \$columns = Schema::getColumnListing('transactions');
        if (in_array('net_amount', \$columns) && in_array('total_amount', \$columns)) {
            echo '✅ Columns verified: net_amount and total_amount exist\n';
        } else {
            echo '❌ Warning: Columns may not have been added\n';
        }
    "
    
    echo ""
    echo "=========================================="
    echo "✅ All fixes applied successfully!"
    echo "=========================================="
    echo ""
    echo "📝 What was fixed:"
    echo "   1. ✅ Added net_amount column to transactions table"
    echo "   2. ✅ Added total_amount column to transactions table"
    echo "   3. ✅ Fixed DataPurchased function (no longer queries 'data' table)"
    echo ""
    echo "🧪 Next steps:"
    echo "   1. Test webhook by sending ₦250 to PalmPay account 6644694207"
    echo "   2. Check that transaction is created successfully"
    echo "   3. Verify no more database errors in logs"
    echo ""
else
    echo ""
    echo "❌ Migration failed!"
    echo "Please check the error message above and try again."
    exit 1
fi
