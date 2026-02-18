#!/bin/bash

# Fix Document Root for app.pointwave.ng
# This script helps diagnose and fix the document root issue

echo "=========================================="
echo "PointWave Domain Configuration Checker"
echo "=========================================="
echo ""

# Check current directory
echo "1. Current directory:"
pwd
echo ""

# Check if public directory exists
echo "2. Checking public directory:"
if [ -d "public" ]; then
    echo "✅ public/ directory exists"
    ls -la public/index.php 2>/dev/null && echo "✅ public/index.php exists" || echo "❌ public/index.php missing"
    ls -la public/.htaccess 2>/dev/null && echo "✅ public/.htaccess exists" || echo "❌ public/.htaccess missing"
else
    echo "❌ public/ directory not found"
    echo "   Are you in the project root?"
fi
echo ""

# Check web server
echo "3. Detecting web server:"
if command -v apache2 &> /dev/null; then
    echo "✅ Apache detected"
    WEBSERVER="apache"
elif command -v nginx &> /dev/null; then
    echo "✅ Nginx detected"
    WEBSERVER="nginx"
else
    echo "⚠️  No web server detected (or no access)"
    WEBSERVER="unknown"
fi
echo ""

# Check Apache configuration
if [ "$WEBSERVER" = "apache" ]; then
    echo "4. Checking Apache configuration:"
    if [ -f "/etc/apache2/sites-available/app.pointwave.ng.conf" ]; then
        echo "✅ Found Apache config: /etc/apache2/sites-available/app.pointwave.ng.conf"
        echo ""
        echo "Current DocumentRoot:"
        grep -i "DocumentRoot" /etc/apache2/sites-available/app.pointwave.ng.conf 2>/dev/null || echo "   (Could not read - need sudo)"
    else
        echo "⚠️  Apache config not found at standard location"
        echo "   Check your hosting control panel"
    fi
fi
echo ""

# Check Nginx configuration
if [ "$WEBSERVER" = "nginx" ]; then
    echo "4. Checking Nginx configuration:"
    if [ -f "/etc/nginx/sites-available/app.pointwave.ng" ]; then
        echo "✅ Found Nginx config: /etc/nginx/sites-available/app.pointwave.ng"
        echo ""
        echo "Current root:"
        grep -i "root" /etc/nginx/sites-available/app.pointwave.ng 2>/dev/null || echo "   (Could not read - need sudo)"
    else
        echo "⚠️  Nginx config not found at standard location"
        echo "   Check your hosting control panel"
    fi
fi
echo ""

# Recommendations
echo "=========================================="
echo "RECOMMENDATIONS"
echo "=========================================="
echo ""

if [ -d "public" ]; then
    FULL_PATH=$(realpath public)
    echo "✅ Your document root should be:"
    echo "   $FULL_PATH"
    echo ""
    
    if [ "$WEBSERVER" = "apache" ]; then
        echo "📝 For Apache, update your virtual host:"
        echo "   DocumentRoot $FULL_PATH"
        echo ""
        echo "   Then run:"
        echo "   sudo systemctl restart apache2"
    elif [ "$WEBSERVER" = "nginx" ]; then
        echo "📝 For Nginx, update your server block:"
        echo "   root $FULL_PATH;"
        echo ""
        echo "   Then run:"
        echo "   sudo nginx -t"
        echo "   sudo systemctl restart nginx"
    else
        echo "📝 Contact your hosting provider and tell them:"
        echo "   'Please set document root to: $FULL_PATH'"
    fi
else
    echo "❌ Cannot find public directory"
    echo "   Make sure you're in the Laravel project root"
fi

echo ""
echo "=========================================="
echo "TESTING"
echo "=========================================="
echo ""
echo "After fixing, test your site:"
echo "1. Visit: https://app.pointwave.ng"
echo "2. Expected: Login page (not directory listing)"
echo "3. Test API: https://app.pointwave.ng/api/v1/..."
echo ""

echo "For detailed instructions, see: DOMAIN_CONFIGURATION_FIX.md"
echo ""
