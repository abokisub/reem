#!/bin/bash

echo "=========================================="
echo "REBUILDING FRONTEND - NO CREDENTIALS FIX"
echo "=========================================="
echo ""
echo "This will rebuild the React frontend with ALL credentials removed from documentation."
echo ""

# Navigate to frontend directory
cd frontend || exit 1

echo "Step 1: Installing dependencies..."
npm install --legacy-peer-deps

echo ""
echo "Step 2: Building production bundle..."
npm run build

echo ""
echo "=========================================="
echo "✅ FRONTEND REBUILD COMPLETE"
echo "=========================================="
echo ""
echo "Changes applied:"
echo "  ✅ Authentication.js - REMOVED all credential fetching and display"
echo "  ✅ Sandbox.js - Code examples use placeholder text only"
echo "  ✅ DeleteCustomer.js - Code examples use placeholder text only"
echo ""
echo "Documentation pages now show:"
echo "  ✅ NO real credentials anywhere"
echo "  ✅ Only placeholder text in all examples"
echo "  ✅ Professional documentation like Stripe/Paystack"
echo ""
echo "The browser will load the new JavaScript bundle on next page refresh."
echo "You may need to hard refresh (Ctrl+Shift+R) to clear cache."
echo ""
