#!/bin/bash

echo "=== DEPLOYING WEBHOOK DATABASE FIX ==="
echo ""
echo "This will add missing columns to palmpay_webhooks table:"
echo "  - order_no (extracted from JSON payload)"
echo "  - order_amount (extracted from JSON payload)"
echo "  - account_reference (extracted from JSON payload)"
echo ""

# Push to GitHub
echo "Step 1: Pushing migration to GitHub..."
git add database/migrations/2026_02_20_100000_add_extracted_fields_to_palmpay_webhooks.php
git commit -m "Add extracted fields to palmpay_webhooks table for webhook logs display"
git push origin main

echo ""
echo "Step 2: On the server, run these commands:"
echo ""
echo "cd app.pointwave.ng"
echo "git pull origin main"
echo "php artisan migrate --path=database/migrations/2026_02_20_100000_add_extracted_fields_to_palmpay_webhooks.php --force"
echo ""
echo "Step 3: Verify the fix:"
echo "php check_webhook_data.php"
echo ""
echo "Step 4: Test the webhook logs page in the browser"
echo "  - Admin: https://app.pointwave.ng/secure/webhooks"
echo "  - Company: https://app.pointwave.ng/dashboard/webhooks"
echo ""
