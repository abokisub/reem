#!/bin/bash

# Setup cron job for Laravel scheduler
# This will process settlements automatically every minute

CURRENT_DIR=$(pwd)

echo "=== CRON JOB SETUP FOR LARAVEL SCHEDULER ==="
echo ""
echo "Current directory: $CURRENT_DIR"
echo ""

# Check if crontab exists
if crontab -l > /dev/null 2>&1; then
    echo "Existing crontab found. Checking for Laravel scheduler..."
    
    if crontab -l | grep -q "artisan schedule:run"; then
        echo "✅ Laravel scheduler already configured in crontab"
        echo ""
        echo "Current cron jobs:"
        crontab -l | grep "artisan schedule:run"
    else
        echo "⚠️  Laravel scheduler NOT found in crontab"
        echo ""
        echo "Adding Laravel scheduler to crontab..."
        
        # Backup current crontab
        crontab -l > /tmp/crontab_backup_$(date +%Y%m%d_%H%M%S)
        
        # Add Laravel scheduler
        (crontab -l 2>/dev/null; echo "* * * * * cd $CURRENT_DIR && php artisan schedule:run >> /dev/null 2>&1") | crontab -
        
        echo "✅ Laravel scheduler added to crontab"
    fi
else
    echo "No existing crontab found. Creating new one..."
    echo "* * * * * cd $CURRENT_DIR && php artisan schedule:run >> /dev/null 2>&1" | crontab -
    echo "✅ Crontab created with Laravel scheduler"
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "📋 CURRENT CRONTAB:"
crontab -l
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "✅ SETUP COMPLETE!"
echo ""
echo "The Laravel scheduler will now run every minute and process:"
echo "  - Pending settlements (every minute)"
echo "  - Any other scheduled tasks"
echo ""
echo "To verify it's working:"
echo "  1. Wait 1-2 minutes"
echo "  2. Run: php check_settlement_history.php"
echo "  3. Check for recent settlement activity"
echo ""
