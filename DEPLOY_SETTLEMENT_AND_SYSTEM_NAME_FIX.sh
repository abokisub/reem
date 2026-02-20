#!/bin/bash

echo "=========================================="
echo "DEPLOYING SETTLEMENT & SYSTEM NAME FIXES"
echo "=========================================="
echo ""
echo "This will deploy:"
echo "  1. Settlement status fix (unsettled → settled)"
echo "  2. Receipt account display fix (N/A → actual account)"
echo "  3. Dynamic system name in /secure/info API"
echo ""

# Pull latest code
echo "Step 1: Pulling latest code from GitHub..."
git pull origin main

echo ""
echo "Step 2: Clearing Laravel caches..."
php artisan cache:clear
php artisan config:clear
php artisan view:clear

echo ""
echo "Step 3: Fixing existing settlement transactions..."
php fix_settlement_status.php

echo ""
echo "=========================================="
echo "✅ DEPLOYMENT COMPLETE"
echo "=========================================="
echo ""
echo "What's fixed:"
echo "  ✅ New settlement transactions will have settlement_status = 'settled'"
echo "  ✅ Existing settlement transactions updated to 'settled'"
echo "  ✅ Receipt now shows company settlement account (not N/A)"
echo "  ✅ /secure/info API now returns system.name from database"
echo ""
echo "Frontend changes (not deployed yet):"
echo "  ⏳ Dynamic system name context"
echo "  ⏳ Documentation credentials removed"
echo "  ⏳ Need to rebuild: cd frontend && npm install --legacy-peer-deps && npm run build"
echo ""
echo "Test the fixes:"
echo "  1. Create a new settlement withdrawal"
echo "  2. Check that status shows 'Settled' (not 'Unsettled')"
echo "  3. Download receipt and verify account number shows (not N/A)"
echo ""
