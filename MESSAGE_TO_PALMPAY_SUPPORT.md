# Message to Send to PalmPay Support

## Subject: Enable Webhook Notifications for Merchant Account

---

Dear PalmPay Support Team,

I am writing to request activation of webhook notifications for my merchant account.

**Merchant Details:**
- Merchant ID: 3280202682744801
- Business Name: POINTWAVE DIGITAL SERVICES
- Contact Email: ajamilubashir@gmail.com

**Webhook Configuration:**
- Webhook URL: `https://app.pointwave.ng/api/webhooks/palmpay`
- Server IP Address: `66.29.153.81` (already whitelisted)
- Additional IP: `105.112.30.197` (development)

**Events to Enable:**
Please enable webhook notifications for the following events:
1. Payment Success (deposits to virtual accounts)
2. Payment Failed
3. Transfer Success
4. Transfer Failed  
5. Virtual Account Created
6. KYC Status Updates

**Current Issue:**
I have configured the webhook URL in my merchant dashboard, but I am not receiving any webhook notifications when transactions occur. I have verified that:
- ✅ My webhook endpoint is accessible (returns HTTP 200)
- ✅ The URL is correctly configured in the dashboard
- ✅ My server IP is whitelisted
- ✅ Transactions are being processed successfully

**Request:**
Please activate webhook notifications for my merchant account and confirm when this is complete so I can test the integration.

**Testing:**
Once activated, I will send a test transaction to verify webhook delivery.

Thank you for your assistance.

Best regards,
Bashir Ajamilu
PointWave Digital Services

---

## Alternative: Shorter Version

Subject: Activate Webhooks for Merchant 3280202682744801

Hi PalmPay Support,

Please activate webhook notifications for my merchant account:

- Merchant ID: 3280202682744801
- Webhook URL: https://app.pointwave.ng/api/webhooks/palmpay
- Server IP: 66.29.153.81

I've configured the webhook URL in my dashboard but not receiving notifications. Please enable and confirm.

Thanks!
Bashir

---

## How to Contact PalmPay Support

### Option 1: Email
- Email: support@palmpay.com
- CC: merchant@palmpay.com

### Option 2: PalmPay Dashboard
1. Login to: https://business.palmpay.com
2. Go to: Support or Help Center
3. Create a ticket with the message above

### Option 3: Phone/WhatsApp
- Check your PalmPay dashboard for support contact numbers
- Usually available in Settings → Support

---

## What to Expect

After sending the message:
1. PalmPay support will review your request (1-2 business days)
2. They will activate webhooks on their end
3. They will send you a confirmation email
4. You can then test by sending another payment

---

## Meanwhile: Manual Sync Option

While waiting for PalmPay to activate webhooks, you can use the manual sync command:

```bash
# Run this on your server to sync transactions from PalmPay
php artisan palmpay:sync-transactions
```

This will fetch recent transactions from PalmPay API and update your database.

You can also set up a cron job to run this every 5 minutes:
```
*/5 * * * * cd /home/aboksdfs/app.pointwave.ng && php artisan palmpay:sync-transactions >> /dev/null 2>&1
```

---

## Verification After Activation

Once PalmPay confirms activation:

1. Send a test payment to your PalmPay account
2. Check webhook logs: https://app.pointwave.ng/secure/webhooks
3. Verify transaction appears in dashboard
4. Check Laravel logs: `tail -f storage/logs/laravel.log`

---

## Important Notes

- Webhook activation is done by PalmPay support, not automatic
- It usually takes 1-2 business days
- Make sure your webhook URL is exactly: `https://app.pointwave.ng/api/webhooks/palmpay`
- No trailing slash, must use HTTPS

---

**Send this message to PalmPay support now to get webhooks activated!**
