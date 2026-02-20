#!/bin/bash

# Deploy Table Responsive Width Fix to Production Server
# This script copies the built frontend to the server

echo "=========================================="
echo "Deploying Table Responsive Width Fix"
echo "=========================================="
echo ""

# Server details
SERVER="aboksdfs@server350.web-hosting.com"
SERVER_PATH="/home/aboksdfs/app.pointwave.ng/public"

echo "Step 1: Copying build files to server..."
echo "----------------------------------------"
scp -r frontend/build/* ${SERVER}:${SERVER_PATH}/

if [ $? -eq 0 ]; then
    echo "✅ Build files copied successfully"
else
    echo "❌ Failed to copy build files"
    exit 1
fi

echo ""
echo "Step 2: Clearing Laravel caches on server..."
echo "----------------------------------------"
ssh ${SERVER} << 'EOF'
cd app.pointwave.ng
php artisan cache:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear
echo "✅ Caches cleared"
EOF

echo ""
echo "=========================================="
echo "✅ DEPLOYMENT COMPLETE!"
echo "=========================================="
echo ""
echo "Next Steps:"
echo "1. Test Wallet page: https://app.pointwave.ng/dashboard/wallet"
echo "2. Test RA Transactions: https://app.pointwave.ng/dashboard/ra-transactions"
echo "3. Test Admin Statement: https://app.pointwave.ng/secure/statement"
echo ""
echo "Verify that:"
echo "  - All columns are visible (no cut-off)"
echo "  - Tables display in full width"
echo "  - Horizontal scroll works on smaller screens"
echo "  - All data is accessible"
echo ""
