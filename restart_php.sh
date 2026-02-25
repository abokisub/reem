#!/bin/bash

echo "=========================================="
echo "PHP Restart Script (No Sudo Required)"
echo "=========================================="
echo ""

# Method 1: Touch a file to trigger PHP-FPM reload (works on some hosts)
echo "Method 1: Touching restart trigger file..."
touch /home/aboksdfs/tmp/restart.txt 2>/dev/null && echo "✓ Trigger file created" || echo "✗ Failed"

# Method 2: Kill user's PHP processes (they will auto-restart)
echo ""
echo "Method 2: Restarting user PHP processes..."
pkill -u aboksdfs php-fpm 2>/dev/null && echo "✓ PHP processes restarted" || echo "✗ No PHP processes found or no permission"

# Method 3: Use hosting control panel command (if available)
echo ""
echo "Method 3: Checking for hosting control panel..."
if command -v cloudlinux-selector &> /dev/null; then
    cloudlinux-selector restart-php && echo "✓ PHP restarted via CloudLinux" || echo "✗ Failed"
elif [ -f ~/.cagefs/.cagefs.token ]; then
    echo "CloudLinux detected but no restart command available"
else
    echo "No control panel restart command found"
fi

echo ""
echo "=========================================="
echo "Alternative: Use Hosting Control Panel"
echo "=========================================="
echo ""
echo "If the above methods didn't work, please:"
echo "1. Login to your hosting control panel (cPanel/Plesk)"
echo "2. Look for 'PHP Selector' or 'PHP Version'"
echo "3. Click 'Restart' or 'Apply' to restart PHP"
echo ""
echo "OR contact your hosting provider to restart PHP-FPM"
