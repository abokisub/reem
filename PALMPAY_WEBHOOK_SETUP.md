# PalmPay Webhook Setup Guide

## Current Status

✅ **System is Ready**: The webhook processing system is fully functional and tested
✅ **Master Wallet Created**: Account 6644694207 for PointWave Business
✅ **Transaction Received**: PalmPay shows the deposit in their dashboard
❌ **Webhook Not Configured**: PalmPay cannot send webhooks to local IP (192.168.1.160:8000)

## The Problem

PalmPay needs to send webhook notifications to your server when transactions occur. However, your current setup uses a local IP address (`http://192.168.1.160:8000`) which PalmPay cannot reach from the internet.

## Solutions

### Option 1: Use ngrok (Recommended for Testing)

1. Install ngrok: https://ngrok.com/download

2. Start ngrok tunnel:
```bash
ngrok http 8000
```

3. Copy the HTTPS URL (e.g., `https://abc123.ngrok.io`)

4. Configure in PalmPay Dashboard:
   - Login to: https://business.palmpay.com
   - Go to: Settings → Webhook Configuration
   - Set Webhook URL to: `https://abc123.ngrok.io/api/webhooks/palmpay`
   - Save

5. Test by sending money to: 6644694207

### Option 2: Deploy to Production (Recommended for Live)

1. Deploy your application to a server with a public domain

2. Update `.env`:
```env
APP_URL=https://app.pointwave.ng
```

3. Configure in PalmPay Dashboard:
   - Webhook URL: `https://app.pointwave.ng/api/webhooks/palmpay`

### Option 3: Manual Sync (Temporary Workaround)

If you can't configure webhooks immediately, use the manual sync command:

```bash
php artisan palmpay:sync-transactions --company_id=2
```

This will fetch transactions from PalmPay API and process them manually.

## Webhook URL Format

The webhook endpoint is already configured in your system:

```
POST /api/webhooks/palmpay
```

Full URL examples:
- Local (ngrok): `https://abc123.ngrok.io/api/webhooks/palmpay`
- Production: `https://app.pointwave.ng/api/webhooks/palmpay`

## How to Configure in PalmPay Dashboard

1. Login to PalmPay Business Dashboard: https://business.palmpay.com
2. Navigate to: **Settings** → **API Configuration** → **Webhook Settings**
3. Enter your webhook URL
4. Select events to receive:
   - ✅ Virtual Account Credit (Collection/Virtual Account)
   - ✅ Transfer Success (Payout)
   - ✅ Transfer Failed (Payout)
5. Save configuration

## Testing the Webhook

After configuration, test by:

1. Transfer money to master wallet: **6644694207** (PalmPay)
2. Check logs: `tail -f storage/logs/laravel.log | grep -i palmpay`
3. Check dashboard: `/dashboard/wallet` should show the transaction
4. Check admin: `/secure/transactions` should show the deposit

## Webhook Payload Example

PalmPay sends this format for deposits:

```json
{
    "virtualAccountNo": "6644694207",
    "orderAmount": 18000,
    "orderNo": "PP1234567890",
    "accountReference": "REF123",
    "senderName": "John Doe",
    "senderAccount": "1234567890",
    "narration": "Payment description",
    "transactionTime": "2026-02-18 10:00:00"
}
```

## Verification

To verify the system is working:

1. **Check Webhook Logs**:
```bash
tail -f storage/logs/laravel.log | grep "PalmPay Webhook"
```

2. **Check Database**:
```bash
php artisan tinker
>>> \App\Models\PalmPayWebhook::latest()->first()
>>> \App\Models\Transaction::where('category', 'virtual_account_credit')->latest()->first()
```

3. **Check Company Balance**:
```bash
php artisan tinker
>>> $company = \App\Models\Company::find(2)
>>> $company->wallet->balance
```

## Troubleshooting

### Webhook Not Received

1. Check PalmPay dashboard configuration
2. Verify URL is publicly accessible
3. Check firewall/security settings
4. Review Laravel logs for errors

### Transaction Not Showing

1. Check `palmpay_webhooks` table for received webhooks
2. Check `transactions` table for processed transactions
3. Review `processing_error` column in `palmpay_webhooks` table
4. Check Laravel logs for processing errors

### Balance Not Updated

1. Verify transaction status is 'success'
2. Check `company_wallets` table for balance
3. Review `balance_before` and `balance_after` in transaction record

## Support

For PalmPay webhook configuration issues:
- Email: support@palmpay.com
- Documentation: https://docs.palmpay.com/
- Dashboard: https://business.palmpay.com

## Next Steps

1. ✅ Configure webhook URL in PalmPay dashboard
2. ✅ Test with real transaction
3. ✅ Verify transaction appears in dashboard
4. ✅ Verify balance is updated
5. ✅ Test company webhook forwarding (if configured)
