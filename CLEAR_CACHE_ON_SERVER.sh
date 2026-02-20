#!/bin/bash

# ============================================
# CLEAR CACHE ON SERVER
# ============================================
# This fixes the PUT route issues

echo "========================================="
echo "CLEARING CACHE ON SERVER"
echo "========================================="
echo ""

echo "Step 1: Clear route cache..."
php artisan route:clear

echo ""
echo "Step 2: Clear config cache..."
php artisan config:clear

echo ""
echo "Step 3: Clear application cache..."
php artisan cache:clear

echo ""
echo "Step 4: Regenerate composer autoload..."
composer dump-autoload

echo ""
echo "========================================="
echo "✅ CACHE CLEARED SUCCESSFULLY!"
echo "========================================="
echo ""
echo "Now test the PUT endpoints:"
echo ""
echo "1. Update Customer:"
echo "   curl -X PUT 'https://app.pointwave.ng/api/v1/customers/{id}' \\"
echo "     -H 'Authorization: Bearer SECRET_KEY' \\"
echo "     -H 'x-api-key: API_KEY' \\"
echo "     -H 'x-business-id: BUSINESS_ID' \\"
echo "     -H 'Content-Type: application/json' \\"
echo "     -d '{\"phone_number\": \"08012345678\"}'"
echo ""
echo "2. Update Virtual Account:"
echo "   curl -X PUT 'https://app.pointwave.ng/api/v1/virtual-accounts/{id}' \\"
echo "     -H 'Authorization: Bearer SECRET_KEY' \\"
echo "     -H 'x-api-key: API_KEY' \\"
echo "     -H 'x-business-id: BUSINESS_ID' \\"
echo "     -H 'Content-Type: application/json' \\"
echo "     -d '{\"status\": \"deactivated\"}'"
echo ""
