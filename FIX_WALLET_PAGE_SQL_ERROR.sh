#!/bin/bash

# Fix Wallet Page SQL UNION Error - Deployment Script
# This script pushes the fix to GitHub and provides server deployment instructions

echo "=========================================="
echo "FIX WALLET PAGE SQL UNION ERROR"
echo "=========================================="
echo ""

# Step 1: Add changes to git
echo "Step 1: Adding changes to git..."
git add app/Http/Controllers/API/Trans.php
echo "✅ Changes staged"
echo ""

# Step 2: Commit changes
echo "Step 2: Committing changes..."
git commit -m "Fix Wallet page SQL UNION error - match column counts in AllHistoryUser method"
echo "✅ Changes committed"
echo ""

# Step 3: Push to GitHub
echo "Step 3: Pushing to GitHub..."
git push origin main
echo "✅ Pushed to GitHub"
echo ""

echo "=========================================="
echo "GITHUB PUSH COMPLETE!"
echo "=========================================="
echo ""
echo "Now run these commands on the server:"
echo ""
echo "ssh aboksdfs@app.pointwave.ng"
echo "cd /home/aboksdfs/app.pointwave.ng"
echo "git pull origin main"
echo "php artisan cache:clear"
echo "php artisan view:clear"
echo "php artisan config:clear"
echo "php artisan route:clear"
echo ""
echo "Then test the Wallet page in your browser"
echo ""
