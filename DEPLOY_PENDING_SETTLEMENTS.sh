#!/bin/bash

echo "=========================================="
echo "DEPLOYING PENDING SETTLEMENTS FEATURE"
echo "=========================================="
echo ""

# Step 1: Push to GitHub
echo "Step 1: Pushing changes to GitHub..."
git add .
git commit -m "Add manual pending settlements feature for admin"
git push origin main

if [ $? -ne 0 ]; then
    echo "❌ Failed to push to GitHub"
    exit 1
fi

echo "✅ Changes pushed to GitHub"
echo ""

# Step 2: SSH to server and deploy
echo "Step 2: Deploying to production server..."
echo ""

ssh root@167.172.232.130 << 'ENDSSH'
cd /var/www/html

echo "Pulling latest changes from GitHub..."
git pull origin main

echo ""
echo "Clearing Laravel caches..."
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

echo ""
echo "Installing frontend dependencies..."
cd frontend
npm install

echo ""
echo "Building frontend..."
npm run build

echo ""
echo "✅ Deployment complete!"
echo ""
echo "The Pending Settlements feature is now live at:"
echo "https://kobopoint.com/secure/pending-settlements"
ENDSSH

echo ""
echo "=========================================="
echo "DEPLOYMENT COMPLETE"
echo "=========================================="
echo ""
echo "Feature URL: https://kobopoint.com/secure/pending-settlements"
echo ""
echo "What was deployed:"
echo "  ✅ Backend controller for pending settlements"
echo "  ✅ API routes for fetching and processing settlements"
echo "  ✅ Frontend admin page with professional UI"
echo "  ✅ Admin sidebar menu item"
echo "  ✅ Path definitions"
echo ""
echo "Features:"
echo "  • Filter by Yesterday (24+ hours) or Today"
echo "  • View summary cards with totals"
echo "  • Company-grouped settlement summary"
echo "  • Detailed transaction list"
echo "  • Confirmation dialog before processing"
echo "  • Automatic balance crediting"
echo "  • Transaction-safe processing with rollback"
echo ""
