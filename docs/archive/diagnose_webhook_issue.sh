#!/bin/bash

echo "=========================================="
echo "PalmPay Webhook Diagnostic"
echo "=========================================="
echo ""

# Check 1: Test webhook endpoint accessibility
echo "1. Testing webhook endpoint from external..."
curl -X POST https://app.pointwave.ng/api/webhooks/palmpay \
  -H "Content-Type: application/json" \
  -d '{"event":"test","data":{"test":"data"}}' \
  -w "\nHTTP Status: %{http_code}\n" \
  -s

echo ""
echo "✅ If you see 'success' and HTTP Status: 200, your endpoint is accessible"
echo ""

# Check 2: Check Laravel logs for any webhook attempts
echo "2. Checking Laravel logs for webhook attempts..."
echo "Last 20 lines of laravel.log:"
tail -20 storage/logs/laravel.log | grep -i webhook || echo "No webhook entries found in logs"

echo ""

# Check 3: Check database for webhook logs
echo "3. Checking database for webhook logs..."
php artisan tinker --execute="echo 'Webhook logs count: ' . DB::table('webhook_logs')->count();"

echo ""

# Check 4: Check if route exists
echo "4. Checking if webhook route is registered..."
php artisan route:list | grep -i palmpay || echo "No PalmPay routes found"

echo ""
echo "=========================================="
echo "DIAGNOSIS SUMMARY"
echo "=========================================="
echo ""
echo "Possible Issues:"
echo ""
echo "1. PalmPay hasn't enabled webhooks on their end"
echo "   Solution: Contact PalmPay support to activate webhooks"
echo ""
echo "2. Your server IP not whitelisted with PalmPay"
echo "   Your IP: 66.29.153.81"
echo "   Solution: Ask PalmPay to whitelist this IP"
echo ""
echo "3. PalmPay is sending to wrong URL"
echo "   Correct URL: https://app.pointwave.ng/api/webhooks/palmpay"
echo "   Solution: Verify URL in PalmPay dashboard"
echo ""
echo "4. Webhook secret mismatch"
echo "   Solution: Check PALMPAY_WEBHOOK_SECRET in .env"
echo ""
