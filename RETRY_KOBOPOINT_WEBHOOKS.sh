#!/bin/bash

echo "================================================================================"
echo "RETRYING FAILED KOBOPOINT WEBHOOKS"
echo "================================================================================"
echo ""
echo "This will retry all failed webhooks for Kobopoint."
echo "Their endpoint is now fixed and should accept webhooks with HTTP 200."
echo ""

# Option 1: Use the PHP script (more control, shows details)
echo "Option 1: Using PHP script (recommended)"
echo "Command: php retry_kobopoint_webhooks.php"
echo ""

# Option 2: Use artisan command (automatic retry of due webhooks)
echo "Option 2: Using artisan command (retries all due webhooks)"
echo "Command: php artisan webhooks:retry"
echo ""

read -p "Which option? (1/2): " option

if [ "$option" = "1" ]; then
    echo ""
    echo "Running PHP script..."
    echo ""
    php retry_kobopoint_webhooks.php
elif [ "$option" = "2" ]; then
    echo ""
    echo "Running artisan command..."
    echo ""
    php artisan webhooks:retry
else
    echo ""
    echo "Invalid option. Exiting."
    echo ""
    exit 1
fi

echo ""
echo "================================================================================"
echo "DONE"
echo "================================================================================"
echo ""
echo "Next steps:"
echo "1. Check the webhook logs in your admin panel"
echo "2. Ask Kobopoint to check their logs for incoming webhooks"
echo "3. Verify customer balances are updating correctly"
echo ""
