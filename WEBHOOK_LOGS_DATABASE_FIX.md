# Webhook Logs Database Fix

## Problem
The webhook logs pages were showing empty because the backend API was failing with:
```
Column not found: 1054 Unknown column 'order_no' in 'field list'
```

The `palmpay_webhooks` table was missing columns that the backend code expected:
- `order_no`
- `order_amount`
- `account_reference`

These fields exist in the JSON `payload` column but weren't extracted for easy querying.

## Solution
Created migration `2026_02_20_100000_add_extracted_fields_to_palmpay_webhooks.php` that:
1. Adds the missing columns to the table
2. Extracts data from existing webhook records' JSON payload
3. Converts `order_amount` from kobo to naira (divides by 100)
4. Adds index on `order_no` for performance

## Deployment Steps

### On Your Local Machine (Already Done)
```bash
git pull origin main  # Get the latest code
```

### On Production Server
```bash
cd app.pointwave.ng
git pull origin main
php artisan migrate --path=database/migrations/2026_02_20_100000_add_extracted_fields_to_palmpay_webhooks.php --force
```

### Verify the Fix
```bash
php check_webhook_data.php
```

You should now see:
- Event type displayed correctly
- Order numbers shown
- Amounts displayed in naira

### Test in Browser
- Admin webhook logs: https://app.pointwave.ng/secure/webhooks
- Company webhook logs: https://app.pointwave.ng/dashboard/webhooks

Both pages should now display incoming webhooks with proper data.

## What Changed
- Added 3 new columns to `palmpay_webhooks` table
- Existing 5 incoming webhooks will have their data extracted from JSON
- Future webhooks will need to populate these columns when created

## Note
The webhook handler code (`app/Services/PalmPay/WebhookHandler.php`) should be updated to populate these new columns when storing incoming webhooks, so we don't have to extract from JSON every time.
