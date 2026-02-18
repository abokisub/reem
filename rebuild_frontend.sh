#!/bin/bash

echo "╔══════════════════════════════════════════════════════════════════════╗"
echo "║                                                                      ║"
echo "║              🔄 REBUILDING FRONTEND WITH CACHE BUST                  ║"
echo "║                                                                      ║"
echo "╚══════════════════════════════════════════════════════════════════════╝"
echo ""

# Step 1: Navigate to frontend directory
echo "📂 Step 1: Navigating to frontend directory..."
cd frontend || exit 1
echo "✅ In frontend directory"
echo ""

# Step 2: Clean old build
echo "🧹 Step 2: Cleaning old build..."
rm -rf build
echo "✅ Old build removed"
echo ""

# Step 3: Build new version
echo "🔨 Step 3: Building new version (this may take a few minutes)..."
npm run build
if [ $? -eq 0 ]; then
    echo "✅ Build completed successfully"
else
    echo "❌ Build failed"
    exit 1
fi
echo ""

# Step 4: Copy to public directory
echo "📦 Step 4: Deploying to public directory..."
cd ..
rm -rf public/static
cp -r frontend/build/* public/
echo "✅ Files deployed"
echo ""

# Step 5: Clear Laravel caches
echo "🗑️  Step 5: Clearing Laravel caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
echo "✅ Caches cleared"
echo ""

echo "╔══════════════════════════════════════════════════════════════════════╗"
echo "║                                                                      ║"
echo "║                    ✅ REBUILD COMPLETE!                              ║"
echo "║                                                                      ║"
echo "║  Next Steps:                                                         ║"
echo "║  1. Clear your browser cache (Ctrl+Shift+Delete)                     ║"
echo "║  2. Or do a hard refresh (Ctrl+Shift+R or Cmd+Shift+R)               ║"
echo "║  3. Visit: https://app.pointwave.ng                                  ║"
echo "║                                                                      ║"
echo "╚══════════════════════════════════════════════════════════════════════╝"
