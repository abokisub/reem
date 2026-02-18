# Admin Customers List Update

## ✅ Correct Format Implemented

**Date**: February 17, 2026  
**Page**: `/secure/customers` (Admin Panel)

---

## What Admin Sees Now

### Customers Table Columns

| Customer ID | Name | Email | Phone | Merchant (Company) | KYC Status | Joined | Actions |
|-------------|------|-------|-------|-------------------|------------|--------|---------|
| 1efdfc4845a7327bc9271ff0daafdae551d07524 | Jamil Abubakar Bashir | habukhan001@gmail.com | 08078889419 | PointWave Business | verified | 2/17/2026 | 👁️ 🗑️ |

---

## API Response Format

### GET `/api/admin/customers`

```json
{
  "status": "success",
  "data": {
    "customers": [
      {
        "id": 1,
        "customer_id": "1efdfc4845a7327bc9271ff0daafdae551d07524",
        "name": "Jamil Abubakar Bashir",
        "first_name": "Jamil",
        "last_name": "Abubakar Bashir",
        "email": "habukhan001@gmail.com",
        "phone": "08078889419",
        "merchant_company": "PointWave Business",
        "company_id": 2,
        "kyc_status": "verified",
        "status": "active",
        "joined_date": "2026-02-17",
        "created_at": "2026-02-17T21:16:15Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 1,
      "total_records": 1,
      "per_page": 20
    }
  }
}
```

---

## Key Changes

### Before ❌
- Showed raw customer data
- No company name visible
- KYC status showed "unverified"
- No formatted name
- No customer ID (UUID) displayed

### After ✅
- **Customer ID**: Full UUID (1efdfc4845a7327bc9271ff0daafdae551d07524)
- **Name**: First + Last name combined (Jamil Abubakar Bashir)
- **Email**: habukhan001@gmail.com
- **Phone**: 08078889419
- **Merchant (Company)**: PointWave Business (not customer email!)
- **KYC Status**: verified (auto-verified since under company)
- **Joined Date**: 2026-02-17 (formatted)
- **Actions**: View, Delete available

---

## Search & Filter Features

### Search
Searches across:
- ✅ Customer ID (UUID)
- ✅ First Name
- ✅ Last Name
- ✅ Email
- ✅ Phone
- ✅ Company Name

### Filters
- ✅ By Company ID
- ✅ By KYC Status

---

## Frontend Integration

### Display Customers Table

```javascript
// Fetch customers
const response = await fetch('/api/admin/customers?page=1', {
  headers: {
    'Authorization': `Bearer ${adminToken}`,
    'Content-Type': 'application/json'
  }
});

const { data } = await response.json();

// Display in table
data.customers.forEach(customer => {
  console.log(`
    Customer ID: ${customer.customer_id}
    Name: ${customer.name}
    Email: ${customer.email}
    Phone: ${customer.phone}
    Merchant: ${customer.merchant_company}
    KYC: ${customer.kyc_status}
    Joined: ${customer.joined_date}
  `);
});
```

### Search Customers

```javascript
const response = await fetch('/api/admin/customers?search=Jamil', {
  headers: {
    'Authorization': `Bearer ${adminToken}`,
    'Content-Type': 'application/json'
  }
});
```

### Filter by Company

```javascript
const response = await fetch('/api/admin/customers?company_id=2', {
  headers: {
    'Authorization': `Bearer ${adminToken}`,
    'Content-Type': 'application/json'
  }
});
```

---

## Actions Available

### 1. View Customer Details
**Endpoint**: `GET /api/admin/customers/{id}`

Shows:
- Full customer details
- All virtual accounts (with PalmPay account numbers)
- Transaction statistics
- Transaction history

### 2. Delete Customer
**Endpoint**: `DELETE /api/admin/customers/{id}`

Deletes:
- Customer record
- Associated data

---

## Example Data

### Customer: Bonita (Jamil Abubakar Bashir)

```
Customer ID: 1efdfc4845a7327bc9271ff0daafdae551d07524
Name: Jamil Abubakar Bashir
Email: habukhan001@gmail.com
Phone: 08078889419
Merchant (Company): PointWave Business
KYC Status: verified
Joined: February 17, 2026
Status: active

Virtual Accounts:
- PalmPay: 6690945661
```

---

## Why KYC is "verified"

Customers created under a company are automatically considered verified because:

1. ✅ The company (merchant) has completed KYC
2. ✅ The company is verified and active
3. ✅ The company takes responsibility for their customers
4. ✅ Customers inherit the company's verification status

This is the **aggregator model** - the company's KYC covers all their customers.

---

## Testing

### Test the Endpoint

```bash
# Get all customers
curl -X GET http://192.168.1.160:8000/api/admin/customers \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"

# Search for customer
curl -X GET "http://192.168.1.160:8000/api/admin/customers?search=Jamil" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"

# Filter by company
curl -X GET "http://192.168.1.160:8000/api/admin/customers?company_id=2" \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN"
```

---

## Summary

✅ **Admin customers list now shows:**
- Customer ID (UUID)
- Full Name
- Email
- Phone
- **Merchant Company Name** (not email!)
- KYC Status (verified)
- Joined Date
- Actions (View, Delete)

The frontend just needs to consume the API and display the data in the table. All the correct information is now in the response!
