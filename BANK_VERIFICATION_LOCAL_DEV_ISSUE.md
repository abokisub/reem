# Bank Verification - Local Development Issue

## Issue
Bank account verification fails on local development with error:
```
"request ip not in ip white list"
```

## Root Cause
PalmPay API requires all requests to come from whitelisted IP addresses. Your local development IP (`105.112.199.137`) is not whitelisted in PalmPay's system.

## Solution

### Option 1: Whitelist Local IP (Recommended for Development)
Contact PalmPay support and request to whitelist your local IP:
- **IP to Whitelist**: `105.112.199.137`
- **Purpose**: Development and testing

### Option 2: Test on Production Server
The production server IP is already whitelisted, so bank verification works there.

## Current Status
- ✅ Banks dropdown loads successfully
- ✅ Company update works
- ❌ Bank account verification fails (IP not whitelisted)

## Error Message Updated
Changed error message from:
- ❌ "Service configuration error"

To:
- ✅ "IP not whitelisted - Please contact support to whitelist your server IP"

## Files Modified
- `app/Services/PalmPay/AccountVerificationService.php` - Updated error message for code `OPEN_GW_000012`

## Testing
Once your IP is whitelisted, verification will work like this:

1. Select bank from dropdown
2. Enter 10-digit account number
3. Click "Verify" button
4. Account name auto-fills
5. Save changes

## Production Deployment
No issues expected on production - the server IP is already whitelisted.
