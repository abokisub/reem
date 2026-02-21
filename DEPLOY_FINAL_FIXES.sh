#!/bin/bash

echo "🚀 Deploying Final 2 Bug Fixes..."
echo ""

# Remove test file
rm -f fix_virtual_account_status.php

# Push to GitHub
echo "📤 Pushing to GitHub..."
git add .
git commit -m "Fix: DELETE VA enum value (inactive) & GET Banks TINYINT query (1)"
git push origin main

echo ""
echo "✅ Pushed to GitHub"
echo ""
echo "📋 Next Steps (Run on Server):"
echo ""
echo "ssh into server and run:"
echo ""
echo "cd /home/aboksdfs/app.pointwave.ng"
echo "git pull origin main"
echo "php artisan route:clear"
echo "php artisan config:clear"
echo "php artisan cache:clear"
echo "php artisan optimize"
echo ""
echo "✅ Then test both endpoints:"
echo "  1. DELETE Virtual Account"
echo "  2. GET Banks"
echo ""
echo "🎉 All 13 endpoints should now work perfectly!"
