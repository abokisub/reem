# ✅ YOUR ACTUAL API - SIMPLE EXPLANATION

## What Your API Actually Uses

Based on your code in `app/Http/Middleware/V1/MerchantAuth.php`, your API requires **3 headers**:

```bash
curl -X POST "https://app.pointwave.ng/api/v1/customers" \
  -H "Authorization: Bearer YOUR_SECRET_KEY" \
  -H "x-api-key: YOUR_API_KEY" \
  -H "x-business-id: YOUR_BUSINESS_ID" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "first_name": "Test",
    "last_name": "User",
    "phone": "08012345678",
    "bvn": "22222222222"
  }'
```

## The 3 Required Headers

1. **Authorization: Bearer YOUR_SECRET_KEY**
   - Must start with "Bearer " (with space)
   - Example: `Authorization: Bearer sk_live_abc123xyz456`

2. **x-api-key: YOUR_API_KEY**
   - Your public API key
   - Example: `x-api-key: pk_live_def789ghi012`

3. **x-business-id: YOUR_BUSINESS_ID**
   - Your business ID (40 characters)
   - Example: `x-business-id: 1234567890abcdef1234567890abcdef12345678`

## Where Companies Get These Values

Companies get these 3 values from their dashboard:
- Go to **Settings** → **API Keys**
- Copy all 3 values

## Your API Endpoints

- Base URL: `https://app.pointwave.ng/api/v1`
- Create Customer: `POST /api/v1/customers`
- Create Virtual Account: `POST /api/v1/virtual-accounts`
- Bank Transfer: `POST /api/v1/transfers`
- Get Transactions: `GET /api/v1/transactions`

## Summary

✅ Your API uses: **Bearer** authentication + 2 extra headers  
✅ Your React documentation page is CORRECT  
❌ The `SEND_THIS_TO_DEVELOPERS.md` file I created is WRONG (uses "Token" instead of "Bearer" and missing 2 headers)

