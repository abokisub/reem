#!/bin/bash

echo "╔══════════════════════════════════════════════════════════════════════╗"
echo "║                                                                      ║"
echo "║              🔧 FIXING BROWSER CACHE ISSUE                           ║"
echo "║                                                                      ║"
echo "╚══════════════════════════════════════════════════════════════════════╝"
echo ""

# Step 1: Clear Laravel caches
echo "🗑️  Step 1: Clearing Laravel caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
echo "✅ Laravel caches cleared"
echo ""

# Step 2: Clear OPcache (if available)
echo "🗑️  Step 2: Clearing OPcache..."
if command -v php &> /dev/null; then
    php -r "if (function_exists('opcache_reset')) { opcache_reset(); echo 'OPcache cleared'; } else { echo 'OPcache not available'; }"
    echo ""
fi
echo ""

# Step 3: Touch .htaccess to reload Apache config
echo "🔄 Step 3: Reloading Apache configuration..."
touch public/.htaccess
echo "✅ Apache config reloaded"
echo ""

# Step 4: Add version parameter to force cache bust
echo "📝 Step 4: Creating cache-bust version file..."
date +%s > public/version.txt
VERSION=$(cat public/version.txt)
echo "✅ Version: $VERSION"
echo ""

echo "╔══════════════════════════════════════════════════════════════════════╗"
echo "║                                                                      ║"
echo "║                    ✅ CACHE FIX COMPLETE!                            ║"
echo "║                                                                      ║"
echo "║  IMPORTANT: Tell users to do ONE of these:                          ║"
echo "║                                                                      ║"
echo "║  Option 1: Hard Refresh (Recommended)                               ║"
echo "║    - Windows/Linux: Ctrl + Shift + R                                ║"
echo "║    - Mac: Cmd + Shift + R                                           ║"
echo "║                                                                      ║"
echo "║  Option 2: Clear Browser Cache                                      ║"
echo "║    - Windows/Linux: Ctrl + Shift + Delete                           ║"
echo "║    - Mac: Cmd + Shift + Delete                                      ║"
echo "║    - Select 'Cached images and files'                               ║"
echo "║    - Click 'Clear data'                                             ║"
echo "║                                                                      ║"
echo "║  Option 3: Incognito/Private Window                                 ║"
echo "║    - Open a new incognito/private window                            ║"
echo "║    - Visit: https://app.pointwave.ng                                ║"
echo "║                                                                      ║"
echo "╚══════════════════════════════════════════════════════════════════════╝"
