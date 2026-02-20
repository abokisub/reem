#!/bin/bash

echo "=========================================="
echo "DEPLOYING API V1 - SIMPLE CUSTOMER CREATION"
echo "=========================================="
echo ""

echo "📝 What's being deployed:"
echo "  - Simplified createCustomer() method (only name, email, phone required)"
echo "  - No BVN/NIN/address/files required for basic customer creation"
echo "  - KYC fields only needed for upgrades"
echo ""

# Step 1: Push to GitHub
echo "=========================================="
echo "STEP 1: PUSH TO GITHUB"
echo "=========================================="
echo ""

echo "Adding files to git..."
git add app/Http/Controllers/API/V1/MerchantApiController.php
git add test_v1_api_complete.php
git add SEND_THIS_TO_DEVELOPERS.md
git add API_V1_SIMPLE_CUSTOMER_CREATION.md
git add DEPLOY_API_V1_SIMPLE_CUSTOMER.sh

echo ""
echo "Files staged:"
git status --short
echo ""

read -p "Commit message (or press Enter for default): " commit_msg
if [ -z "$commit_msg" ]; then
    commit_msg="API V1: Simplify customer creation - only name, email, phone required"
fi

git commit -m "$commit_msg"
echo "✅ Committed"
echo ""

echo "Pushing to GitHub..."
git push origin main
echo "✅ Pushed to GitHub"
echo ""

# Step 2: Instructions for server
echo "=========================================="
echo "STEP 2: DEPLOY ON SERVER"
echo "=========================================="
echo ""
echo "Run these commands on the server:"
echo ""
echo "ssh aboksdfs@server350.web-hosting.com"
echo "cd /home/aboksdfs/app.pointwave.ng"
echo "git pull origin main"
echo "php artisan route:clear"
echo "php artisan config:clear"
echo "php artisan cache:clear"
echo ""

# Step 3: Test
echo "=========================================="
echo "STEP 3: TEST THE API"
echo "=========================================="
echo ""
echo "After deployment, test with:"
echo "php test_v1_api_complete.php"
echo ""

echo "=========================================="
echo "DEPLOYMENT READY!"
echo "=========================================="
echo ""
echo "Summary:"
echo "✅ Code pushed to GitHub"
echo "⏳ Waiting for you to pull on server"
echo "⏳ Waiting for cache clear on server"
echo "⏳ Waiting for API test"
echo ""
echo "API Credentials for testing:"
echo "  API Key: 7db8dbb3991382487a1fc388a05d96a7139d92ba"
echo "  Secret Key: d8a3151a8993c157c1a4ee5ecda8983107004b1fbdec3f55011f1b7ee4e63f517b7403e021e96995e4c5c90aab834ea70845672dbe6ccf7910dda45c"
echo "  Business ID: 3450968aa027e86e3ff5b0169dc17edd7694a846"
echo ""
