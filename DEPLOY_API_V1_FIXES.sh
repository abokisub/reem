#!/bin/bash

echo "========================================="
echo "DEPLOY API V1 FIXES"
echo "========================================="
echo ""

echo "Changes:"
echo "1. Fixed KYC routes to use MerchantAuth middleware"
echo "2. Fixed KYC controller to use request attributes instead of Auth::user()"
echo "3. Fixed response format (status instead of success)"
echo ""

echo "Step 1: Push to GitHub..."
git add routes/api.php app/Http/Controllers/API/V1/KycController.php
git commit -m "Fix: API V1 KYC endpoints - use MerchantAuth middleware and request attributes"
git push origin main

echo ""
echo "Step 2: Deploy on server..."
echo "Run these commands on server:"
echo ""
echo "cd /home/aboksdfs/app.pointwave.ng"
echo "git pull origin main"
echo "composer dump-autoload"
echo "php artisan route:clear"
echo "php artisan config:clear"
echo "php artisan cache:clear"
echo ""
echo "Step 3: Test all endpoints..."
echo "php test_all_api_v1_endpoints.php"
echo ""
echo "========================================="
echo "DEPLOYMENT SCRIPT READY"
echo "========================================="
