#!/bin/bash
# Deployment script for KoboPoint API fixes
# Run this on the production server

echo "=== Deploying KoboPoint API Fixes ==="
echo ""

# Navigate to application directory
cd /home/aboksdfs/app.pointwave.ng || exit 1

echo "1. Pulling latest changes from GitHub..."
git pull origin main

echo ""
echo "2. Showing recent commits..."
git log --oneline -3

echo ""
echo "3. Clearing Laravel caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear

echo ""
echo "4. Verifying deployment..."
php artisan --version

echo ""
echo "=== Deployment Complete ==="
echo ""
echo "Fixed Issues:"
echo "  ✓ GET /banks - Returns banks array directly in data"
echo "  ✓ POST /banks/verify - Proper error messages (no more 'success' error)"
echo "  ✓ Error codes: ACCOUNT_NOT_FOUND, BANK_NOT_SUPPORTED, etc."
echo ""
echo "Test Endpoints:"
echo "  curl -X GET https://app.pointwave.ng/api/v1/banks \\"
echo "    -H 'Authorization: Bearer YOUR_API_KEY'"
echo ""
echo "  curl -X POST https://app.pointwave.ng/api/v1/banks/verify \\"
echo "    -H 'Authorization: Bearer YOUR_API_KEY' \\"
echo "    -H 'Content-Type: application/json' \\"
echo "    -H 'Idempotency-Key: \$(uuidgen)' \\"
echo "    -d '{\"account_number\":\"2340000048\",\"bank_code\":\"090672\"}'"
echo ""
