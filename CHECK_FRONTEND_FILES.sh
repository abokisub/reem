#!/bin/bash

echo "=========================================="
echo "Frontend Files Check"
echo "=========================================="
echo ""

echo "📁 Checking public directory structure..."
echo ""

# Check if public directory exists
if [ -d "public" ]; then
    echo "✅ public/ directory exists"
    echo ""
    
    # List contents
    echo "Contents of public/:"
    ls -lah public/ | head -20
    echo ""
    
    # Check for dashboard files
    if [ -d "public/dashboard" ]; then
        echo "✅ public/dashboard/ exists"
        echo ""
        echo "Dashboard files:"
        ls -lah public/dashboard/ | head -10
        echo ""
        
        # Check for index.html
        if [ -f "public/dashboard/index.html" ]; then
            echo "✅ public/dashboard/index.html exists"
            echo ""
            echo "Checking for JavaScript bundles..."
            find public/dashboard -name "*.js" -type f | head -5
            echo ""
        else
            echo "❌ public/dashboard/index.html NOT found!"
        fi
    else
        echo "❌ public/dashboard/ directory NOT found!"
        echo ""
        echo "Looking for other frontend directories..."
        find public -maxdepth 2 -type d
    fi
    
    # Check for admin dashboard
    if [ -d "public/admin" ]; then
        echo ""
        echo "✅ public/admin/ exists"
        ls -lah public/admin/ | head -10
    fi
    
else
    echo "❌ public/ directory NOT found!"
fi

echo ""
echo "=========================================="
echo "📝 Frontend Deployment Info"
echo "=========================================="
echo ""
echo "Your frontend build files should be in:"
echo "  - public/dashboard/ (company dashboard)"
echo "  - public/admin/ (admin dashboard)"
echo ""
echo "To deploy new frontend:"
echo "1. Build locally: cd frontend && npm run build"
echo "2. Upload build/ contents to public/dashboard/"
echo "3. Clear browser cache"
echo ""
echo "🔍 If files exist but dashboard is empty:"
echo "   The issue is in the frontend JavaScript code"
echo "   - Check browser console (F12) for errors"
echo "   - Check Network tab for failed API calls"
echo "   - Verify API endpoints are being called"
