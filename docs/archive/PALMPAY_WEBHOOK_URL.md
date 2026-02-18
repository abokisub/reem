# PalmPay Webhook Configuration

## 🔗 Your Webhook URL for PalmPay Dashboard

### Production Webhook URL:
```
https://app.pointwave.ng/api/webhooks/palmpay
```

### Alternative Webhook URL (if first doesn't work):
```
https://app.pointwave.ng/api/v1/webhook/palmpay
```

---

## 📋 How to Set Up in PalmPay Dashboard

### Step 1: Login to PalmPay Merchant Dashboard
1. Go to: https://merchant.palmpay.com (or your PalmPay portal)
2. Login with your merchant credentials

### Step 2: Navigate to Webhook Settings
1. Look for: **Settings** or **Developer Settings**
2. Find: **Webhook Configuration** or **Notification URL**

### Step 3: Enter Your Webhook URL
```
Webhook URL: https://app.pointwave.ng/api/webhooks/palmpay
```

### Step 4: Select Events to Receive
Enable these events:
- ✅ **Payment Success** (when customer deposits to virtual account)
- ✅ **Payment Failed**
- ✅ **Transfer Success** (when you send money)
- ✅ **Transfer Failed**
- ✅ **Account Created** (virtual account creation)
- ✅ **KYC Status Update** (BVN/NIN verification)

### Step 5: Save Configuration
Click **Save** or **Update**

---

## 🔐 Webhook Security

### IP Whitelist (Already Configured)
Your server IPs are whitelisted with PalmPay:
- Local/Dev: `105.112.30.197`
- Production: `66.29.153.81`

### Webhook Signature Verification
Your webhook handler automatically verifies PalmPay signatures for security.

---

## 🧪 Test Your Webhook

### Method 1: PalmPay Dashboard Test
Most PalmPay dashboards have a "Test Webhook" button:
1. Go to webhook settings
2. Click "Test Webhook" or "Send Test Event"
3. Check your webhook logs at: https://app.pointwave.ng/dashboard/webhook-logs

### Method 2: Manual Test (Using curl)
```bash
curl -X POST https://app.pointwave.ng/api/webhooks/palmpay \
  -H "Content-Type: application/json" \
  -d '{
    "event": "payment.success",
    "data": {
      "amount": "1000.00",
      "reference": "TEST123",
      "account_number": "6644694207",
      "sender_name": "Test User"
    }
  }'
```

### Method 3: Check Webhook Logs
1. Login to your dashboard
2. Go to: **Webhook Logs** (in sidebar under MERCHANT)
3. You should see webhook delivery attempts

---

## 📊 Webhook Events You'll Receive

### 1. Payment Success (Deposit to Virtual Account)
```json
{
  "event": "payment.success",
  "data": {
    "amount": "10000.00",
    "reference": "REF123456",
    "account_number": "6644694207",
    "sender_name": "John Doe",
    "sender_account": "0123456789",
    "sender_bank": "GTBank",
    "transaction_date": "2026-02-18T10:00:00Z"
  }
}
```

### 2. Transfer Success
```json
{
  "event": "transfer.success",
  "data": {
    "reference": "TXN123456",
    "amount": "5000.00",
    "recipient_account": "0123456789",
    "recipient_bank": "Access Bank",
    "status": "success"
  }
}
```

### 3. Transfer Failed
```json
{
  "event": "transfer.failed",
  "data": {
    "reference": "TXN123456",
    "amount": "5000.00",
    "recipient_account": "0123456789",
    "error": "Insufficient balance"
  }
}
```

### 4. KYC Status Update
```json
{
  "event": "kyc.verified",
  "data": {
    "account_number": "6644694207",
    "kyc_level": "tier_3",
    "bvn": "22490148602",
    "status": "verified"
  }
}
```

---

## 🔍 Verify Webhook is Working

### Check 1: Webhook Logs Page
1. Login to dashboard
2. Go to: https://app.pointwave.ng/dashboard/webhook-logs
3. Look for recent webhook deliveries

### Check 2: Database
```sql
SELECT * FROM webhook_logs 
WHERE created_at > NOW() - INTERVAL 1 DAY 
ORDER BY created_at DESC 
LIMIT 10;
```

### Check 3: Laravel Logs
```bash
tail -f storage/logs/laravel.log | grep -i webhook
```

---

## 🆘 Troubleshooting

### Issue: Webhooks Not Received

**Check 1: URL is Correct**
```
✅ Correct: https://app.pointwave.ng/api/webhooks/palmpay
❌ Wrong: http://app.pointwave.ng/api/webhooks/palmpay (no https)
❌ Wrong: https://app.pointwave.ng/webhooks/palmpay (missing /api)
```

**Check 2: IP Whitelist**
Make sure PalmPay has your server IP:
- Production: `66.29.153.81`

**Check 3: Firewall**
Ensure your server allows incoming POST requests from PalmPay IPs.

**Check 4: SSL Certificate**
Your webhook URL must use HTTPS (SSL certificate must be valid).

### Issue: Webhook Returns Error

**Check Laravel Logs:**
```bash
tail -50 storage/logs/laravel.log
```

**Check Web Server Logs:**
```bash
# Apache
tail -50 /var/log/apache2/error.log

# Nginx
tail -50 /var/log/nginx/error.log
```

### Issue: Signature Verification Fails

**Check Webhook Secret:**
Make sure your `.env` has the correct PalmPay webhook secret:
```env
PALMPAY_WEBHOOK_SECRET=your_webhook_secret_from_palmpay
```

---

## 📝 Webhook Handler Details

### Location:
- Primary: `app/Http/Controllers/API/Gateway/PalmPayWebhookController.php`
- Alternative: `app/Http/Controllers/API/V1/WebhookController.php`

### What It Does:
1. ✅ Receives webhook from PalmPay
2. ✅ Verifies signature for security
3. ✅ Processes payment/transfer events
4. ✅ Updates database (transactions, balances)
5. ✅ Queues for settlement (T+1 schedule)
6. ✅ Logs webhook delivery
7. ✅ Sends notifications to customers

### Response:
Your webhook handler returns:
```json
{
  "status": "success",
  "message": "Webhook processed"
}
```

---

## 🔔 Webhook Retry Policy

If your webhook fails, PalmPay will retry:
- **Attempt 1:** Immediate
- **Attempt 2:** After 5 minutes
- **Attempt 3:** After 15 minutes
- **Attempt 4:** After 1 hour
- **Attempt 5:** After 6 hours

You can see retry attempts in your webhook logs.

---

## 📞 Contact PalmPay Support

If you need help setting up webhooks:

**Email:** support@palmpay.com
**Subject:** Webhook Configuration for Merchant [Your Merchant ID]

**Message Template:**
```
Hello PalmPay Support,

I need to configure webhooks for my merchant account.

Merchant ID: [Your Merchant ID]
Business Name: PointWave Business
Webhook URL: https://app.pointwave.ng/api/webhooks/palmpay

Please enable the following events:
- Payment Success
- Payment Failed
- Transfer Success
- Transfer Failed
- KYC Status Update

My server IP is already whitelisted: 66.29.153.81

Thank you!
```

---

## ✅ Quick Setup Checklist

- [ ] Login to PalmPay merchant dashboard
- [ ] Navigate to webhook settings
- [ ] Enter webhook URL: `https://app.pointwave.ng/api/webhooks/palmpay`
- [ ] Select events to receive
- [ ] Save configuration
- [ ] Test webhook (use test button or send test payment)
- [ ] Verify webhook logs show delivery
- [ ] Check Laravel logs for any errors
- [ ] Confirm transactions are being processed

---

## 🎯 Summary

**Your Webhook URL:**
```
https://app.pointwave.ng/api/webhooks/palmpay
```

**What to Do:**
1. Login to PalmPay dashboard
2. Go to webhook settings
3. Enter the URL above
4. Enable payment and transfer events
5. Save and test

**Verification:**
- Check webhook logs at: `/dashboard/webhook-logs`
- Monitor Laravel logs: `storage/logs/laravel.log`
- Test with a small deposit

---

## 📚 Related Documentation

- `PALMPAY_WEBHOOK_SETUP.md` - Detailed webhook setup
- `app/Services/PalmPay/WebhookHandler.php` - Webhook processing logic
- `SETTLEMENT_RULES_IMPLEMENTATION.md` - Settlement system details

---

**Your webhook is ready to receive PalmPay notifications!** 🎉
