# API Credential Management Guide

## Overview

This guide explains how API credentials work in PointWave and how to prevent common integration issues.

---

## How Credentials Work

### Credential Storage

API credentials are stored in the `companies` table:

```sql
- business_id (unique identifier for the company)
- api_key (for live/production use)
- api_secret_key (for live/production use)
- test_api_key (for sandbox/testing)
- test_secret_key (for sandbox/testing)
```

### Authentication Flow

When a developer makes an API request:

1. **Headers Required:**
   - `Authorization: Bearer {SECRET_KEY}`
   - `X-API-Key: {API_KEY}`
   - `X-Business-ID: {BUSINESS_ID}`

2. **Middleware Validation** (`app/Http/Middleware/GatewayAuth.php`):
   - Extracts credentials from headers
   - Queries database for matching company:
     ```php
     Company::where('business_id', $businessId)
         ->where('api_secret_key', $secretKey)
         ->where('api_key', $apiKey)
         ->first();
     ```
   - If not found in live credentials, checks sandbox credentials
   - Returns 401 "Invalid credentials" if no match

3. **Direct Comparison:**
   - Credentials are compared EXACTLY as stored in database
   - No encryption/decryption during authentication
   - All three values must match: Business ID, API Key, Secret Key

---

## Credential Regeneration

### What Happens When User Clicks "Generate New Credentials"

1. **Frontend** (`frontend/src/pages/dashboard/DeveloperAPI.js`):
   - User clicks "Generate New Credentials" button
   - Sends POST request to `/api/company/credentials/regenerate`

2. **Backend** (`app/Http/Controllers/API/CompanyController.php`):
   - Generates new random API key (40 characters)
   - Generates new random Secret key (128 characters)
   - Updates database with new credentials
   - **Old credentials are immediately invalidated**

3. **Result:**
   - ✅ New credentials saved to database
   - ❌ Old credentials no longer work
   - ⚠️ Any applications using old credentials will get "Invalid credentials" error

---

## Common Issue: Developer Using Old Credentials

### Scenario

1. Company regenerates credentials on Day 1
2. Developer integrates using credentials from Day 1
3. Company regenerates credentials again on Day 4
4. Developer's integration breaks with "Invalid credentials" error

### Why This Happens

- Developer saved credentials in their code/config
- Company regenerated credentials in dashboard
- Developer's saved credentials no longer match database
- Authentication middleware rejects the old credentials

### Solution

Developer must update their integration with the NEW credentials from the dashboard.

---

## Real Example: Kobopoint Integration Issue

### Problem

Developer reported "Invalid credentials" error after base URL update.

### Investigation

```bash
$ php check_company_api_credentials.php

=== CHECKING COMPANY API CREDENTIALS ===

Company ID: 2
Name: PointWave Business
Business ID: 3450968aa027e86e3ff5b0169dc17edd7694a846

=== LIVE CREDENTIALS ===
API Key: 2aa89c1398c330d6ed16198dc1e872f572c02d07
Secret Key: d8a3151a8993c157c1a4ee5ecda8983107004b1fbdec3f55011f1b7ee4e63f517b7403e021e96995e4c5c90aab834ea70845672dbe6ccf7910dda45c

=== CREDENTIAL MATCH CHECK ===
Developer's API Key matches LIVE: ❌ NO
Developer's Secret Key matches LIVE: ✅ YES
```

### Root Cause

- Developer was using: `7db8dbb3991382487a1fc388a05d96a7139d92ba` (OLD API Key)
- Database has: `2aa89c1398c330d6ed16198dc1e872f572c02d07` (CURRENT API Key)
- Secret Key matched ✅
- Business ID matched ✅
- But API Key didn't match ❌

### Resolution

Send developer the CURRENT credentials from database:

```
Business ID: 3450968aa027e86e3ff5b0169dc17edd7694a846
API Key: 2aa89c1398c330d6ed16198dc1e872f572c02d07
Secret Key: d8a3151a8993c157c1a4ee5ecda8983107004b1fbdec3f55011f1b7ee4e63f517b7403e021e96995e4c5c90aab834ea70845672dbe6ccf7910dda45c
```

---

## Email Template for Developers

### When Credentials Are Outdated

```
Subject: API Credentials Update Required

Dear [Developer Name],

The API credentials you're using are outdated. We regenerated our API keys on [DATE] for security purposes.

Please use these CURRENT credentials:

Base URL: https://app.pointwave.ng/api/gateway
Business ID: 3450968aa027e86e3ff5b0169dc17edd7694a846
API Key: 2aa89c1398c330d6ed16198dc1e872f572c02d07
Secret Key: d8a3151a8993c157c1a4ee5ecda8983107004b1fbdec3f55011f1b7ee4e63f517b7403e021e96995e4c5c90aab834ea70845672dbe6ccf7910dda45c

Authentication Headers:
Authorization: Bearer d8a3151a8993c157c1a4ee5ecda8983107004b1fbdec3f55011f1b7ee4e63f517b7403e021e96995e4c5c90aab834ea70845672dbe6ccf7910dda45c
X-API-Key: 2aa89c1398c330d6ed16198dc1e872f572c02d07
X-Business-ID: 3450968aa027e86e3ff5b0169dc17edd7694a846
Content-Type: application/json
Accept: application/json

Test Command:
curl -X GET "https://app.pointwave.ng/api/gateway/banks" \
  -H "Authorization: Bearer d8a3151a8993c157c1a4ee5ecda8983107004b1fbdec3f55011f1b7ee4e63f517b7403e021e96995e4c5c90aab834ea70845672dbe6ccf7910dda45c" \
  -H "X-API-Key: 2aa89c1398c330d6ed16198dc1e872f572c02d07" \
  -H "X-Business-ID: 3450968aa027e86e3ff5b0169dc17edd7694a846" \
  -H "Accept: application/json"

Where to Find Latest Credentials:
1. Log in to https://app.pointwave.ng
2. Go to "Developer API" in the sidebar
3. Copy the credentials shown there

The credentials in the dashboard are always current and pulled directly from our database.

Best regards,
PointWave Technical Team
```

---

## Prevention Strategies

### 1. Update Documentation

Make sure all documentation emphasizes:

- ✅ "Always get credentials from your dashboard"
- ✅ "Do NOT hardcode credentials from documentation"
- ✅ "Credentials shown in examples are placeholders"

### 2. Add Warning in Dashboard

When user clicks "Generate New Credentials", show a prominent warning:

```
⚠️ WARNING: Credential Regeneration

Old credentials will stop working IMMEDIATELY.

Before proceeding:
☐ Notify any developers who have access to your API
☐ Update all applications using the old credentials
☐ Test with new credentials before going live

Are you sure you want to regenerate credentials?

[Cancel] [Yes, Regenerate]
```

### 3. Show Last Regeneration Date

In the Developer API page, show when credentials were last regenerated:

```
API Credentials
Last regenerated: 3 days ago (February 17, 2026)

[Generate New Credentials]
```

This helps developers know if their saved credentials might be outdated.

### 4. Add Credential History (Optional)

Track credential regeneration in a separate table:

```sql
CREATE TABLE credential_history (
    id BIGINT PRIMARY KEY,
    company_id BIGINT,
    action VARCHAR(50), -- 'generated', 'regenerated'
    old_api_key VARCHAR(255),
    new_api_key VARCHAR(255),
    created_at TIMESTAMP
);
```

This allows support to see when credentials were changed and help debug issues.

### 5. Add API Key Prefix

Consider adding a prefix to API keys to make them identifiable:

```
Live: live_2aa89c1398c330d6ed16198dc1e872f572c02d07
Test: test_fd77db28ec380711d71f23e61ae6afeef0ede396
```

This makes it obvious which environment the key is for.

---

## Troubleshooting Script

Use this script to verify credentials for any company:

```bash
$ php check_company_api_credentials.php
```

**Script location:** `check_company_api_credentials.php`

**What it checks:**
- ✅ Company exists in database
- ✅ Current live credentials
- ✅ Current test credentials
- ✅ Matches developer's credentials
- ✅ User details

---

## Best Practices for Developers

### 1. Store Credentials Securely

```php
// ✅ GOOD: Use environment variables
'pointwave' => [
    'api_key' => env('POINTWAVE_API_KEY'),
    'secret_key' => env('POINTWAVE_SECRET_KEY'),
    'business_id' => env('POINTWAVE_BUSINESS_ID'),
],

// ❌ BAD: Hardcode in code
$apiKey = '2aa89c1398c330d6ed16198dc1e872f572c02d07';
```

### 2. Always Get from Dashboard

```
✅ Log in to dashboard → Developer API → Copy credentials
❌ Copy from documentation examples
❌ Copy from old screenshots
❌ Copy from old emails
```

### 3. Test After Credential Update

```bash
# Test authentication immediately after updating credentials
curl -X GET "https://app.pointwave.ng/api/gateway/banks" \
  -H "Authorization: Bearer {NEW_SECRET_KEY}" \
  -H "X-API-Key: {NEW_API_KEY}" \
  -H "X-Business-ID: {BUSINESS_ID}" \
  -H "Accept: application/json"
```

### 4. Handle 401 Errors Gracefully

```php
if ($response->status() === 401) {
    // Log the error
    Log::error('PointWave authentication failed - credentials may be outdated');
    
    // Notify admin
    Mail::to('admin@example.com')->send(new CredentialsExpiredNotification());
    
    // Return user-friendly error
    return response()->json([
        'error' => 'Payment service authentication failed. Please contact support.'
    ], 500);
}
```

---

## Summary

### Key Points

1. **Credentials are stored in database** - No encryption during authentication
2. **Regeneration invalidates old credentials immediately** - No grace period
3. **All three values must match** - Business ID, API Key, Secret Key
4. **Always get credentials from dashboard** - Not from documentation
5. **Test after updating credentials** - Verify authentication works

### Common Mistakes

- ❌ Using credentials from documentation examples
- ❌ Using credentials from old screenshots
- ❌ Not updating after regeneration
- ❌ Hardcoding credentials in code
- ❌ Not testing after credential update

### Quick Fix for "Invalid Credentials"

1. Log in to https://app.pointwave.ng
2. Go to Developer API page
3. Copy the current credentials
4. Update your integration
5. Test with curl command
6. Deploy updated credentials

---

## Files Referenced

- `app/Http/Middleware/GatewayAuth.php` - Authentication middleware
- `app/Http/Controllers/API/CompanyController.php` - Credential management
- `frontend/src/pages/dashboard/DeveloperAPI.js` - Credential display
- `check_company_api_credentials.php` - Verification script
- `DEVELOPER_CREDENTIALS_FIX.md` - Real-world example
- `DEVELOPER_FINAL_SOLUTION.md` - Email templates

---

**Last Updated:** February 20, 2026
