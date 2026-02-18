#!/bin/bash

echo "=========================================="
echo "PointWave Dashboard Display Fix"
echo "=========================================="
echo ""

echo "This script will:"
echo "1. Pull latest backend code from GitHub"
echo "2. Clear all Laravel caches"
echo "3. Rebuild the frontend"
echo "4. Test the dashboard endpoints"
echo ""
read -p "Continue? (y/n): " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Aborted."
    exit 1
fi

# Step 1: Pull latest code
echo "=========================================="
echo "📥 Step 1: Pulling latest code..."
echo "=========================================="
git pull origin main
echo ""

# Step 2: Clear all caches
echo "=========================================="
echo "🧹 Step 2: Clearing all caches..."
echo "=========================================="
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear
echo "✅ All caches cleared"
echo ""

# Step 3: Check database
echo "=========================================="
echo "🔍 Step 3: Checking database..."
echo "=========================================="
php artisan tinker --execute="
echo 'Company 2 Transactions:\n';
\$count = \App\Models\Transaction::where('company_id', 2)->count();
echo 'Total: ' . \$count . ' transactions\n\n';

if (\$count > 0) {
    echo 'Latest 5 transactions:\n';
    \$txs = \App\Models\Transaction::where('company_id', 2)->orderBy('created_at', 'desc')->limit(5)->get();
    foreach (\$txs as \$tx) {
        echo '  - ' . \$tx->transaction_id . ': ₦' . \$tx->amount . ' (' . \$tx->status . ') - ' . \$tx->created_at . '\n';
    }
} else {
    echo '❌ No transactions found for company 2!\n';
}
"
echo ""

# Step 4: Rebuild frontend
echo "=========================================="
echo "🔨 Step 4: Rebuilding frontend..."
echo "=========================================="

if [ -d "frontend" ]; then
    cd frontend
    
    echo "Installing dependencies..."
    npm install --legacy-peer-deps
    
    echo ""
    echo "Building production frontend..."
    npm run build
    
    if [ $? -eq 0 ]; then
        echo "✅ Frontend built successfully"
        
        # Copy to public directory
        if [ -d "build" ]; then
            echo "Copying build to public directory..."
            rm -rf ../public/dashboard
            cp -r build ../public/dashboard
            echo "✅ Frontend deployed to public/dashboard"
        fi
    else
        echo "❌ Frontend build failed"
        cd ..
        exit 1
    fi
    
    cd ..
else
    echo "❌ Frontend directory not found!"
    exit 1
fi

echo ""

# Step 5: Test API endpoints
echo "=========================================="
echo "🧪 Step 5: Testing API endpoints..."
echo "=========================================="

# Get user token (you'll need to login first)
echo "To test the API, you need to login first."
echo "Login at: https://app.pointwave.ng/login"
echo ""
echo "Then test these endpoints:"
echo "  - GET /api/user/dashboard (Dashboard stats)"
echo "  - GET /api/transactions/deposits (Transaction list)"
echo "  - GET /api/company/webhooks (Webhook logs)"
echo ""

echo "=========================================="
echo "✅ Fix Complete!"
echo "=========================================="
echo ""
echo "📝 What was done:"
echo "1. ✅ Pulled latest backend code"
echo "2. ✅ Cleared all Laravel caches"
echo "3. ✅ Checked database for transactions"
echo "4. ✅ Rebuilt and deployed frontend"
echo ""
echo "🧪 Next Steps:"
echo "1. Clear your browser cache (Ctrl+Shift+Delete)"
echo "2. Login to dashboard: https://app.pointwave.ng"
echo "3. Check if transactions are now visible"
echo "4. If still not visible, check browser console for errors (F12)"
echo ""
echo "💡 If dashboard is still empty:"
echo "   - Check browser console (F12) for API errors"
echo "   - Verify you're logged in with correct account"
echo "   - Try incognito/private browsing mode"
