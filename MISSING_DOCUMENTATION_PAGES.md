# Missing Documentation Pages - Action Required

## Problem
Developers are using `https://app.pointwave.ng/documentation/*` (React app), but two critical endpoints are missing:
1. Get Banks List
2. Verify Account

## Current Status

### ✅ What Exists:
- `/docs/banks` - Blade template (public, has both endpoints) ✅
- `/documentation/transfers` - React page (logged-in users) ✅
- React dashboard "API Documentation" tab - Has all endpoints ✅

### ❌ What's Missing:
- `/documentation/banks` - React page (MISSING!)
- No route or component for Banks documentation

## Solution Required

You need to create a new React documentation page for Banks that includes BOTH endpoints.

### File to Create:
`frontend/src/pages/dashboard/Documentation/Banks.js`

### Content Should Include:

#### 1. Get Banks List
- **Endpoint**: `GET /api/gateway/banks`
- Request examples (cURL, JavaScript, PHP, Python)
- Response format
- Common banks table

#### 2. Verify Account
- **Endpoint**: `POST /api/gateway/banks/verify`
- Request parameters (accountNumber, bankCode)
- Request examples (cURL, JavaScript, PHP, Python)
- Response format
- Error handling

### Route to Add:
In `frontend/src/routes/index.js`, add:
```javascript
{
  path: 'banks',
  element: <Banks />
},
```

### Navigation to Update:
Add link to Banks page in the documentation navigation/sidebar.

---

## Quick Fix Option

Since the React dashboard already has the correct documentation in `ApiDocumentation.js`, you could:

1. **Option A**: Tell developers to use the dashboard "API Documentation" tab (already has everything)
2. **Option B**: Create the Banks.js page (copy structure from Transfers.js)
3. **Option C**: Redirect `/documentation/*` to use the dashboard API Documentation component

---

## For Now - Tell Developers:

"The Banks and Verify Account endpoints are documented in two places:

1. **Dashboard**: Login → API Documentation tab (has all endpoints including Banks and Verify Account)
2. **Public Docs**: https://app.pointwave.ng/docs/banks (public access)

We're adding them to `/documentation/banks` soon."

---

## Endpoints They Need:

### Get Banks List
```
GET /api/gateway/banks

Headers:
- Authorization: Bearer YOUR_SECRET_KEY
- x-api-key: YOUR_API_KEY
- x-business-id: YOUR_BUSINESS_ID

Response:
{
  "success": true,
  "data": [
    {
      "bankCode": "044",
      "bankName": "Access Bank",
      "supportsTransfers": true,
      "supportsVerification": true
    }
  ]
}
```

### Verify Account
```
POST /api/gateway/banks/verify

Headers:
- Authorization: Bearer YOUR_SECRET_KEY
- x-api-key: YOUR_API_KEY
- x-business-id: YOUR_BUSINESS_ID
- Content-Type: application/json

Body:
{
  "accountNumber": "0123456789",
  "bankCode": "058"
}

Response:
{
  "success": true,
  "data": {
    "accountNumber": "0123456789",
    "accountName": "JOHN DOE",
    "bankCode": "058"
  }
}
```

---

## Summary

**Current Situation**:
- ✅ Backend endpoints exist and work
- ✅ Blade docs have both endpoints
- ✅ Dashboard API docs have both endpoints
- ❌ `/documentation/banks` React page missing

**Action Required**:
Create `frontend/src/pages/dashboard/Documentation/Banks.js` with both endpoints documented.

**Temporary Solution**:
Direct developers to:
1. Dashboard → API Documentation tab, OR
2. https://app.pointwave.ng/docs/banks

---

**Last Updated**: 2026-02-22
**Priority**: High (developers are asking for it)
