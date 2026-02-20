# PalmPay IP Whitelist Guide

## Issue Identified

**Error**: `OPEN_GW_000012: request ip not in ip white list`

**Root Cause**: Your server's IP address is not whitelisted in your PalmPay merchant account.

---

## Your Server's IP Address

**IP to Whitelist**: `105.115.5.6`

**Also consider**: `66.29.153.81` (DNS points here, might be load balancer)

---

## Solution: Add IP to PalmPay Whitelist

### Option 1: PalmPay Merchant Portal (Fastest)

1. **Login to PalmPay Merchant Portal**:
   - URL: https://merchant.palmpay.com (or your merchant portal URL)
   - Use your merchant credentials

2. **Navigate to API Settings**:
   - Look for: Settings → API Configuration → IP Whitelist
   - Or: Security → IP Whitelist
   - Or: Developer → API Settings

3. **Add Your IP**:
   - Click "Add IP" or "Whitelist IP"
   - Enter: `105.115.5.6`
   - Description: "Production Server - app.pointwave.ng"
   - Save changes

4. **Optional: Add Backup IP**:
   - Also add: `66.29.153.81`
   - Description: "Production Server - DNS IP"

5. **Wait 5-10 minutes** for changes to propagate

6. **Test**:
   ```bash
   php diagnose_kobopoint_issue.php
   ```

---

### Option 2: Contact PalmPay Support (If no portal access)

**Email Template**:

```
To: business@palmpay.com, tech-support@palmpay.com
Subject: IP Whitelist Request - Merchant ID 126020209274801

Dear PalmPay Support Team,

I need to add my production server IP address to the API whitelist for my merchant account.

MERCHANT DETAILS:
- Merchant ID: 126020209274801
- App ID: L260202154361881198161
- Business Name: PointWave
- Domain: app.pointwave.ng

IP ADDRESSES TO WHITELIST:
- Primary IP: 105.115.5.6
- Secondary IP: 66.29.153.81 (optional, DNS IP)

REASON:
Production server IP for API access (Virtual Accounts, Transfers, Webhooks)

CURRENT ERROR:
OPEN_GW_000012: request ip not in ip white list

Please whitelist these IPs as soon as possible. We have customers waiting to use the service.

Thank you,
[Your Name]
[Your Contact]
```

**Expected Response Time**: 1-4 hours during business hours

---

## How to Check Your IP in cPanel

If you're using cPanel (shared hosting):

1. **Login to cPanel**:
   - URL: https://your-domain.com:2083
   - Or through your hosting provider's portal

2. **Check Server Information**:
   - Go to: General Information → Server Information
   - Look for: "Shared IP Address" or "Server IP"
   - This should show: `66.29.153.81` or similar

3. **Alternative - Terminal Access**:
   ```bash
   curl https://api.ipify.org
   ```
   This shows: `105.115.5.6`

---

## Why Two Different IPs?

You have two IPs because:

1. **105.115.5.6** - Your actual outgoing IP (what PalmPay sees)
   - This is your ISP/network's public IP
   - This is what shows when you make API calls
   - **MUST whitelist this one**

2. **66.29.153.81** - Your server's assigned IP (DNS)
   - This is where your domain points
   - This is for incoming traffic
   - Good to whitelist as backup

**Recommendation**: Whitelist BOTH IPs to be safe.

---

## After Whitelisting

### Test the Connection

```bash
# Run diagnostic
php diagnose_kobopoint_issue.php
```

**Expected Result**:
```
✅ VIRTUAL ACCOUNT CREATED SUCCESSFULLY!
------------------------------------------------------------
Account Number: 6644694207
Account Name: PointWave Business-Test Customer Diagnostic
Bank: PalmPay
Status: active
```

### If Still Failing

1. **Wait longer** (up to 30 minutes for DNS/cache)

2. **Clear Laravel cache**:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

3. **Check if IP changed**:
   ```bash
   bash check_server_ip.sh
   ```

4. **Contact PalmPay** to verify whitelist was applied

---

## For Shared Hosting Users

### Important Notes:

1. **IP Can Change**: Shared hosting IPs can change if:
   - Server is migrated
   - Hosting plan is upgraded
   - Provider changes infrastructure

2. **Solution**: Use IP range or wildcard (if PalmPay supports):
   - Ask PalmPay if they support: `105.115.5.*`
   - Or whitelist entire subnet

3. **Monitor**: Set up monitoring to alert if IP changes:
   ```bash
   # Add to cron (daily check)
   0 0 * * * /path/to/check_server_ip.sh | mail -s "Server IP Check" your@email.com
   ```

---

## Quick Commands Reference

```bash
# Check your current IP
bash check_server_ip.sh

# Test PalmPay connection
php diagnose_kobopoint_issue.php

# Test after whitelisting
php test_palmpay_connection.php

# Check from terminal
curl https://api.ipify.org
```

---

## Email to Kobopoint Developer

After whitelisting is complete, send this to the developer:

```
Subject: PalmPay Integration Issue Resolved

Dear Abubakar,

Good news! We've identified and resolved the PalmPay integration issue.

ISSUE:
The error "OPEN_GW_000008: sign error" was actually caused by IP whitelist 
restrictions (error code OPEN_GW_000012 in our logs).

SOLUTION:
We've whitelisted our server IP (105.115.5.6) in our PalmPay merchant account.

STATUS:
✅ Virtual account creation is now working
✅ All API endpoints are functional
✅ Your integration is ready to use

NEXT STEPS:
You can now test virtual account creation with your integration. Everything 
should work as expected.

If you encounter any issues, please let us know immediately.

Best regards,
PointWave Team
```

---

## Troubleshooting

### Error persists after whitelisting

**Check 1**: Verify IP hasn't changed
```bash
bash check_server_ip.sh
```

**Check 2**: Verify whitelist was applied
- Contact PalmPay support
- Ask them to confirm IP is whitelisted

**Check 3**: Check for multiple IPs
- Your server might use different IPs for different requests
- Whitelist all IPs shown in check_server_ip.sh

**Check 4**: Firewall/Proxy
- Your hosting might use a proxy
- Contact hosting support to confirm outgoing IP

---

## Summary

✅ **Your Server IP**: 105.115.5.6  
✅ **Backup IP**: 66.29.153.81  
✅ **Merchant ID**: 126020209274801  
✅ **Action Required**: Whitelist IPs in PalmPay  
✅ **Test Command**: `php diagnose_kobopoint_issue.php`  

**Timeline**: 5-10 minutes (portal) or 1-4 hours (email support)
