#!/bin/bash

# PointPay Production Deployment Script
# This script commits and pushes your backend changes to GitHub

echo "=========================================="
echo "PointPay Production Deployment"
echo "=========================================="
echo ""

# Check if we're in the right directory
if [ ! -f "artisan" ]; then
    echo "❌ Error: Not in Laravel project root"
    echo "   Please run this script from your project directory"
    exit 1
fi

echo "✅ Laravel project detected"
echo ""

# Show current git status
echo "📊 Current Git Status:"
echo "=========================================="
git status --short
echo ""

# Ask for confirmation
read -p "Do you want to commit and push these changes? (y/n): " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "❌ Deployment cancelled"
    exit 1
fi

echo ""
echo "🔄 Starting deployment process..."
echo ""

# Step 1: Add all changes
echo "1️⃣  Adding all changes to git..."
git add .
echo "✅ Changes added"
echo ""

# Step 2: Commit
echo "2️⃣  Committing changes..."
COMMIT_MSG="Production deployment: Settlement rules, logs pages, PalmPay docs, icon fixes"
git commit -m "$COMMIT_MSG"

if [ $? -eq 0 ]; then
    echo "✅ Changes committed"
else
    echo "⚠️  Nothing to commit or commit failed"
fi
echo ""

# Step 3: Push to GitHub
echo "3️⃣  Pushing to GitHub..."
git push origin main

if [ $? -eq 0 ]; then
    echo "✅ Successfully pushed to GitHub!"
else
    echo "❌ Push failed. Please check your connection and try again."
    exit 1
fi

echo ""
echo "=========================================="
echo "✅ Backend Deployment Complete!"
echo "=========================================="
echo ""
echo "📝 Next Steps:"
echo ""
echo "1. SSH into your production server:"
echo "   ssh username@app.pointwave.ng"
echo ""
echo "2. Navigate to your project:"
echo "   cd /home/abokisub/app.pointwave.ng"
echo ""
echo "3. Pull the latest changes:"
echo "   git pull origin main"
echo ""
echo "4. Run migrations:"
echo "   php artisan migrate --force"
echo ""
echo "5. Clear and optimize caches:"
echo "   php artisan optimize:clear"
echo "   php artisan optimize"
echo ""
echo "6. Test your site:"
echo "   https://app.pointwave.ng"
echo ""
echo "📖 For detailed instructions, see: DEPLOY_TO_PRODUCTION.md"
echo ""
