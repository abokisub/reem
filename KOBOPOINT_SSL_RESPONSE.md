# Response to KoboPoint SSL Certificate Issue

**To**: KoboPoint Support Team  
**From**: PointWave Technical Team  
**Subject**: RE: SSL Certificate Error on app.pointwave.ng API  
**Date**: February 22, 2026

---

Dear KoboPoint Team,

Thank you for reporting the SSL certificate issue. We sincerely apologize for the inconvenience this has caused to your production integration.

## Issue Acknowledged

We've received your report about the SSL certificate error:
```
cURL error 60: SSL: no alternative certificate subject name matches target hostname 'app.pointwave.ng'
```

## Root Cause

The SSL certificate on our server was not properly configured to include `app.pointwave.ng` in the Subject Alternative Name (SAN) field, causing SSL verification to fail.

## Resolution

We are immediately taking the following steps:

### 1. SSL Certificate Installation (In Progress)
- Installing a valid SSL certificate from Let's Encrypt
- Ensuring `app.pointwave.ng` is included in the SAN field
- Configuring automatic certificate renewal

### 2. Verification
- Testing SSL validation from multiple sources
- Ensuring all API endpoints are accessible via HTTPS
- Verifying certificate chain is complete

### 3. Timeline
- **Expected completion**: Within 2-4 hours
- **Status updates**: We'll notify you immediately once fixed

## What You Can Do

Once we confirm the fix (we'll send you an email), please:

1. **Test the SSL certificate**:
```bash
curl -I https://app.pointwave.ng/api/v1/banks
```

2. **Re-enable SSL verification** in your production code:
```php
// Remove this temporary workaround
// curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

// Use proper SSL verification (default)
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
```

3. **Test your integration**:
   - Create customer API call
   - Create virtual account API call
   - Bank transfer API call

## Temporary Workaround (Current)

We understand you've disabled SSL verification temporarily. While this works, we strongly recommend:
- Only use this in development/testing
- Re-enable SSL verification once we confirm the fix
- Never deploy to production without SSL verification

## Your Integration Details

We have your credentials on file:
- **Business ID**: 3450968aa027e86e3ff5b0169dc17edd7694a846
- **Environment**: Production (app.kobopoint.com)
- **Status**: Active

## Compensation

As an apology for this disruption:
- We're waiving all API fees for the next 7 days
- Priority support for any integration issues
- Direct technical contact for urgent matters

## Next Steps

1. ✅ Issue acknowledged and prioritized
2. 🔄 SSL certificate installation in progress
3. ⏳ Testing and verification
4. 📧 Notification email once fixed
5. ✅ Your confirmation that integration works

## Contact

For urgent updates or questions:
- **Email**: support@pointwave.ng
- **Phone**: [Your support number]
- **Status Page**: [If you have one]

We'll send you another email within the next 2-4 hours with confirmation that the SSL certificate has been fixed.

Thank you for your patience and for choosing PointWave!

Best regards,

**PointWave Technical Team**  
support@pointwave.ng  
https://pointwave.ng

---

## Technical Details (For Your Reference)

### What We're Fixing

**Before** (Current - Broken):
```
Certificate Subject: CN=some-other-domain.com
Subject Alternative Names: some-other-domain.com
```

**After** (Fixed):
```
Certificate Subject: CN=app.pointwave.ng
Subject Alternative Names: app.pointwave.ng
Issuer: Let's Encrypt
Valid: Yes
```

### How to Verify (Once Fixed)

```bash
# Check certificate details
echo | openssl s_client -servername app.pointwave.ng -connect app.pointwave.ng:443 2>/dev/null | openssl x509 -noout -text | grep -A1 "Subject Alternative Name"

# Should show:
# X509v3 Subject Alternative Name:
#     DNS:app.pointwave.ng
```

### API Endpoints (All will work with proper SSL)

- `https://app.pointwave.ng/api/v1/auth` - Authentication
- `https://app.pointwave.ng/api/v1/customers` - Customer Management
- `https://app.pointwave.ng/api/v1/virtual-accounts` - Virtual Accounts
- `https://app.pointwave.ng/api/v1/banks` - Bank List
- `https://app.pointwave.ng/api/v1/banks/verify` - Account Verification
- `https://app.pointwave.ng/api/v1/banks/transfer` - Bank Transfers

All endpoints will have valid SSL certificates once the fix is deployed.

---

**P.S.** We take security very seriously. Thank you for not deploying the SSL verification bypass to production. We'll have this fixed shortly!
