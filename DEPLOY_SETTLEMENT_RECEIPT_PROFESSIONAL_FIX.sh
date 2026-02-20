#!/bin/bash

echo "=========================================="
echo "Deploy Settlement Receipt Professional Fix"
echo "=========================================="
echo ""
echo "PROFESSIONAL STANDARD IMPLEMENTED:"
echo "- SENDER: Company Virtual Account (master wallet where money is held)"
echo "- RECIPIENT: Settlement account OR external transfer account (where money goes)"
echo ""
echo "This follows the money flow: FROM company wallet TO destination account"
echo ""

echo "1. Pulling latest code from GitHub..."
git pull origin main

echo ""
echo "2. Clearing Laravel caches..."
php artisan cache:clear
php artisan config:clear
php artisan view:clear

echo ""
echo "=========================================="
echo "✅ Backend Deployment Complete!"
echo "=========================================="
echo ""
echo "NEXT STEPS:"
echo "1. Build frontend locally: cd frontend && npm install --legacy-peer-deps && npm run build"
echo "2. Upload frontend/build folder to server"
echo "3. Test settlement withdrawal receipt"
echo ""
echo "EXPECTED RESULT:"
echo "- SENDER: Company Virtual Account (e.g., 6690945661 on PalmPay)"
echo "- RECIPIENT: Settlement Account (e.g., 7040540018 on OPay) OR External Account"
echo ""
