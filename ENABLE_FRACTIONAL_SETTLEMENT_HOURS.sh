#!/bin/bash

echo "=========================================="
echo "Enable Fractional Settlement Hours"
echo "=========================================="
echo ""
echo "📋 This will:"
echo "   1. Run migration to change settlement_delay_hours to decimal"
echo "   2. Rebuild frontend with updated validation"
echo "   3. Allow values like 0.0167 (1 minute), 0.5 (30 min), etc."
echo ""
read -p "Continue? (y/n): " confirm

if [ "$confirm" != "y" ]; then
    echo "Cancelled."
    exit 0
fi

echo ""
echo "🔧 Step 1: Running database migration..."
php artisan migrate --force

if [ $? -ne 0 ]; then
    echo "❌ Migration failed!"
    exit 1
fi

echo ""
echo "✅ Migration completed!"
echo ""
echo "🔨 Step 2: Building frontend..."
cd frontend
npm run build

if [ $? -ne 0 ]; then
    echo "❌ Frontend build failed!"
    exit 1
fi

cd ..

echo ""
echo "=========================================="
echo "✅ All changes applied successfully!"
echo "=========================================="
echo ""
echo "📝 What changed:"
echo "   - Database column now supports decimal hours"
echo "   - Frontend validation accepts 0.0167 - 168 hours"
echo "   - Backend accepts decimal values"
echo ""
echo "🧪 How to set 1 minute settlement:"
echo "   1. Go to Admin > Discount/Charges > Bank Transfer Charges"
echo "   2. Scroll to 'Settlement Rules' section"
echo "   3. Enter 0.0167 in 'Settlement Delay (Hours)' field"
echo "   4. Uncheck 'Skip Weekends' and 'Skip Holidays'"
echo "   5. Click 'Save All Charges'"
echo ""
echo "⏱️  Common values:"
echo "   - 0.0167 hours = 1 minute"
echo "   - 0.0833 hours = 5 minutes"
echo "   - 0.1667 hours = 10 minutes"
echo "   - 0.5 hours = 30 minutes"
echo "   - 1 hour = 1 hour"
echo "   - 24 hours = 1 day"
echo ""
