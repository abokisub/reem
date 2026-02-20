#!/bin/bash

# Complete Frontend Deployment Script
# Deploys all 3 updated transaction components

echo "=========================================="
echo "Complete Frontend Deployment"
echo "=========================================="
echo ""

# Pull latest changes
echo "Step 1: Pulling latest frontend changes..."
git pull origin main
echo "✓ Frontend changes pulled"
echo ""

# Navigate to frontend
echo "Step 2: Building frontend..."
cd frontend

# Install dependencies (if needed)
if [ ! -d "node_modules" ]; then
    echo "Installing dependencies..."
    npm install
fi

# Build for production
npm run build

if [ $? -eq 0 ]; then
    echo "✓ Frontend build successful"
else
    echo "✗ Frontend build failed"
    exit 1
fi
echo ""

# Deploy to public directory
echo "Step 3: Deploying to public directory..."
rsync -av --delete build/ ../public/
echo "✓ Build deployed"
echo ""

# Clear Laravel caches
echo "Step 4: Clearing Laravel caches..."
cd ..
php artisan cache:clear
php artisan view:clear
php artisan config:clear
echo "✓ Caches cleared"
echo ""

echo "=========================================="
echo "Frontend Deployment Complete!"
echo "=========================================="
echo ""
echo "Updated Components:"
echo "1. RA Transactions (Company View) - 11 columns"
echo "2. Admin Statement (Admin View) - 12 columns"
echo "3. Wallet Summary (Company View) - 10 columns"
echo ""
echo "New Features:"
echo "✓ Transaction reference with copy button"
echo "✓ Session ID with copy button"
echo "✓ Transaction type labels"
echo "✓ Fee and net amount columns"
echo "✓ Normalized settlement status (no N/A)"
echo ""
echo "Next Steps:"
echo "1. Clear browser cache (Ctrl+F5 or Cmd+Shift+R)"
echo "2. Test RA Transactions: /dashboard/ra-transactions"
echo "3. Test Admin Statement: /admin/statement"
echo "4. Test Wallet Summary: /dashboard/wallet"
echo "5. Verify no N/A values appear"
echo "6. Test copy buttons for transaction_ref and session_id"
echo ""
