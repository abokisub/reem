#!/bin/bash

echo "=========================================="
echo "Deploy Backend: Fractional Settlement Hours"
echo "=========================================="
echo ""
echo "📋 This will:"
echo "   1. Pull backend changes from GitHub"
echo "   2. Run migration to change settlement_delay_hours to decimal"
echo "   3. Backend will accept decimal values (0.0167 - 168 hours)"
echo ""
echo "⚠️  NOTE: Frontend changes are NOT included"
echo "   You need to manually build frontend after this"
echo ""
read -p "Continue? (y/n): " confirm

if [ "$confirm" != "y" ]; then
    echo "Cancelled."
    exit 0
fi

echo ""
echo "📥 Step 1: Pulling backend changes from GitHub..."
git pull origin main

if [ $? -ne 0 ]; then
    echo "❌ Git pull failed!"
    exit 1
fi

echo ""
echo "🔧 Step 2: Running database migration..."
php artisan migrate --force

if [ $? -ne 0 ]; then
    echo "❌ Migration failed!"
    exit 1
fi

echo ""
echo "=========================================="
echo "✅ Backend deployed successfully!"
echo "=========================================="
echo ""
echo "📝 What changed:"
echo "   - Database column now supports decimal hours"
echo "   - Backend accepts decimal values (0.0167 - 168)"
echo ""
echo "⚠️  NEXT STEP: Build Frontend Manually"
echo "   cd frontend"
echo "   npm run build"
echo ""
echo "🧪 After frontend build, you can:"
echo "   1. Go to Admin > Discount/Charges > Bank Transfer Charges"
echo "   2. Enter 0.0167 in 'Settlement Delay (Hours)' field"
echo "   3. Save changes"
echo ""
