#!/bin/bash

echo "╔══════════════════════════════════════════════════════════════════════╗"
echo "║                                                                      ║"
echo "║              🔍 CHECKING FRONTEND FILES                              ║"
echo "║                                                                      ║"
echo "╚══════════════════════════════════════════════════════════════════════╝"
echo ""

echo "1. CHECKING public/ FOLDER"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

if [ -f "public/index.html" ]; then
    echo "✅ public/index.html EXISTS"
    echo "   Size: $(du -h public/index.html | cut -f1)"
    echo "   Modified: $(stat -c %y public/index.html 2>/dev/null || stat -f %Sm public/index.html 2>/dev/null)"
    echo ""
    echo "   First 5 lines:"
    head -5 public/index.html
    echo ""
else
    echo "❌ public/index.html NOT FOUND"
    echo ""
fi

if [ -d "public/static" ]; then
    echo "✅ public/static/ folder EXISTS"
    echo "   JS files:"
    ls -lh public/static/js/*.js 2>/dev/null | head -5
    echo ""
    echo "   CSS files:"
    ls -lh public/static/css/*.css 2>/dev/null | head -5
    echo ""
else
    echo "❌ public/static/ folder NOT FOUND"
    echo ""
fi

echo ""
echo "2. CHECKING frontend/build/ FOLDER"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

if [ -d "frontend/build" ]; then
    echo "✅ frontend/build/ folder EXISTS"
    echo "   Contents:"
    ls -lh frontend/build/ | head -10
    echo ""
else
    echo "❌ frontend/build/ folder NOT FOUND"
    echo "   You need to run: cd frontend && npm run build"
    echo ""
fi

echo ""
echo "3. CHECKING resources/views/ FOLDER"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

if [ -f "resources/views/index.blade.php" ]; then
    echo "⚠️  resources/views/index.blade.php EXISTS"
    echo "   This might be serving instead of public/index.html"
    echo ""
elif [ -f "resources/views/app.blade.php" ]; then
    echo "⚠️  resources/views/app.blade.php EXISTS"
    echo "   This might be serving instead of public/index.html"
    echo ""
else
    echo "✅ No conflicting blade files found"
    echo ""
fi

echo ""
echo "4. CHECKING routes/web.php"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

if grep -q "view('index')" routes/web.php; then
    echo "⚠️  Found view('index') in routes/web.php"
    echo "   This serves a Blade template, not the React app"
    echo ""
elif grep -q "view('app')" routes/web.php; then
    echo "⚠️  Found view('app') in routes/web.php"
    echo "   This serves a Blade template, not the React app"
    echo ""
else
    echo "✅ Routes look correct (should serve React app)"
    echo ""
fi

echo ""
echo "5. SUMMARY"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

if [ -f "public/index.html" ] && [ -d "public/static" ]; then
    echo "✅ React build files are in place"
    echo ""
    echo "Next steps:"
    echo "1. Clear Laravel caches: php artisan cache:clear"
    echo "2. Clear config cache: php artisan config:clear"
    echo "3. Clear route cache: php artisan route:clear"
    echo "4. Check browser console for errors (F12)"
    echo ""
else
    echo "❌ React build files are MISSING"
    echo ""
    echo "You need to:"
    echo "1. Build: cd frontend && npm run build"
    echo "2. Copy: cp -r frontend/build/* public/"
    echo ""
fi

echo "╔══════════════════════════════════════════════════════════════════════╗"
echo "║                                                                      ║"
echo "║              📋 DIAGNOSTIC COMPLETE                                  ║"
echo "║                                                                      ║"
echo "╚══════════════════════════════════════════════════════════════════════╝"
