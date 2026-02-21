# EaseID Support Request - Signature Error

## Issue Summary

We are experiencing signature validation errors when calling the EaseID API. All requests return:
```json
{
  "respCode": "OPEN_GW_000008",
  "respMsg": "unknown sign error"
}
```

## Account Details

- **App ID:** K8865857536
- **Merchant ID:** K8865857536
- **Environment:** Production (https://open-api.easeid.ai)
- **Country:** Nigeria (NG)

## Current Implementation

### Signature Generation Algorithm

We are using the following signature generation process:

1. **Sort parameters** by key (ASCII order)
2. **Concatenate** as `key=value&key=value` format
3. **Sign** with RSA-SHA256 using private key
4. **Base64 encode** the signature

### Example Request

**Endpoint:** `/api/validator-service/open/nin/inquire`

**Request Body:**
```json
{
  "appId": "K8865857536",
  "nin": "35257106066",
  "requestTime": 1771663969094,
  "version": "V1.1",
  "nonceStr": "911b8031c030157d5ea9e8787f3b274e"
}
```

**Sign String:**
```
appId=K8865857536&nin=35257106066&nonceStr=911b8031c030157d5ea9e8787f3b274e&requestTime=1771663969094&version=V1.1
```

**Headers:**
```
Authorization: Bearer K8865857536
Signature: [Base64 encoded RSA-SHA256 signature]
CountryCode: NG
Content-Type: application/json
```

**Response:**
```json
{
  "respCode": "OPEN_GW_000008",
  "respMsg": "unknown sign error"
}
```

## Questions for EaseID Support

1. **Is our signature generation algorithm correct?**
   - Should we use `key=value&key=value` format or `key=valuekey=value` (no delimiter)?
   - Should we sign the string directly or sign an MD5 hash of the string?
   - Should we use RSA-SHA256 or a different algorithm?

2. **Is the private key we have correct?**
   - Can you verify the private key on file matches what we're using?
   - Should the private key have PEM headers or be raw?

3. **Is our account properly activated for production?**
   - Is the account approved and active?
   - Are there any IP restrictions?
   - Does the account have sufficient balance?

4. **Request format verification:**
   - Should `appId` be in the request body or only in the Authorization header?
   - Are we using the correct endpoint URLs?
   - Is the `CountryCode: NG` header required?

5. **Can you provide:**
   - A working code example in PHP for signature generation?
   - The exact signature algorithm specification?
   - Test credentials we can use to verify our implementation?

## Private Key Format

Our current private key (first 100 characters):
```
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDLJkOOAwZwrS6rznRr4OuEc1H714EFL2qVP2Dvi8RJetTl...
```

We format it with PEM headers:
```
-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDLJkOO...
-----END PRIVATE KEY-----
```

## Technical Details

- **Programming Language:** PHP 8.3
- **HTTP Client:** cURL / Guzzle
- **OpenSSL Version:** OpenSSL 3.x
- **Server Location:** Nigeria

## Urgency

This is blocking our production KYC verification system. We need to resolve this as soon as possible to serve our customers.

## Contact Information

Please respond to this support request with:
1. Confirmation of the correct signature algorithm
2. Verification that our credentials are active
3. Any corrections needed to our implementation

Thank you for your assistance!
