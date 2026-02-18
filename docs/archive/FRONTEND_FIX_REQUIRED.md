# Frontend Fix Required - Admin Customers Table

## ❌ Current Issues

1. **Customer ID column shows "1"** instead of UUID
2. **Merchant (Company) column shows "N/A"** instead of company name

---

## ✅ Backend is Correct

The API `/api/system/all/virtual-accounts/habukhan/{id}/secure` returns:

```json
{
  "virtual_accounts": {
    "data": [
      {
        "id": 1,                                              // ← Database ID (internal use)
        "customer_id": "1efdfc4845a7327bc9271ff0daafdae551d07524",  // ← UUID (DISPLAY THIS!)
        "account_id": "1efdfc4845a7327bc9271ff0daafdae551d07524",
        "customer_name": "Jamil Abubakar Bashir",
        "customer_email": "habukhan001@gmail.com",
        "customer_phone": "08078889419",
        "email": "habukhan001@gmail.com",
        "phone": "08078889419",
        "merchant_company": "PointWave Business",            // ← Company name (DISPLAY THIS!)
        "date": "17 Feb 2026, 09:16 PM",
        "status": "active",
        "kyc_status": "verified"
      }
    ]
  }
}
```

---

## Frontend Fix

### Issue 1: Customer ID Shows "1" Instead of UUID

**Problem**: Table is displaying `customer.id` instead of `customer.customer_id`

**Fix**:
```jsx
// WRONG ❌
<TableCell>{customer.id}</TableCell>

// CORRECT ✅
<TableCell>{customer.customer_id}</TableCell>
```

### Issue 2: Merchant (Company) Shows "N/A"

**Problem**: Table is not reading `customer.merchant_company` field

**Fix**:
```jsx
// WRONG ❌
<TableCell>{customer.company || 'N/A'}</TableCell>

// CORRECT ✅
<TableCell>{customer.merchant_company}</TableCell>
```

---

## Complete Table Mapping

```jsx
<TableRow key={customer.id}>
  {/* Customer ID - Use customer_id (UUID), not id */}
  <TableCell>{customer.customer_id}</TableCell>
  
  {/* Name */}
  <TableCell>{customer.customer_name}</TableCell>
  
  {/* Email */}
  <TableCell>{customer.customer_email || customer.email}</TableCell>
  
  {/* Phone */}
  <TableCell>{customer.customer_phone || customer.phone}</TableCell>
  
  {/* Merchant (Company) - Use merchant_company */}
  <TableCell>{customer.merchant_company}</TableCell>
  
  {/* KYC Status */}
  <TableCell>
    <Badge variant="success">{customer.kyc_status}</Badge>
  </TableCell>
  
  {/* Joined Date */}
  <TableCell>{customer.date}</TableCell>
  
  {/* Actions */}
  <TableCell>
    <Button onClick={() => viewCustomer(customer.id)}>👁️</Button>
    <Button onClick={() => deleteCustomer(customer.id)}>🗑️</Button>
  </TableCell>
</TableRow>
```

---

## Field Mapping Reference

| Table Column | API Field | Example Value |
|--------------|-----------|---------------|
| Customer ID | `customer.customer_id` | 1efdfc4845a7327bc9271ff0daafdae551d07524 |
| Name | `customer.customer_name` | Jamil Abubakar Bashir |
| Email | `customer.customer_email` or `customer.email` | habukhan001@gmail.com |
| Phone | `customer.customer_phone` or `customer.phone` | 08078889419 |
| Merchant (Company) | `customer.merchant_company` | PointWave Business |
| KYC Status | `customer.kyc_status` | verified |
| Joined | `customer.date` | 17 Feb 2026, 09:16 PM |
| Status | `customer.status` | active |

---

## Expected Result

After fixing the frontend mapping:

| Customer ID | Name | Email | Phone | Merchant (Company) | KYC Status | Joined | Actions |
|-------------|------|-------|-------|-------------------|------------|--------|---------|
| 1efdfc4845...07524 | Jamil Abubakar Bashir | habukhan001@gmail.com | 08078889419 | **PointWave Business** | verified | 2/17/2026 | 👁️ 🗑️ |

---

## Debugging

### 1. Check API Response
```javascript
const response = await fetch('/api/system/all/virtual-accounts/habukhan/{id}/secure');
const data = await response.json();

console.log('First customer:', data.virtual_accounts.data[0]);
// Should show:
// {
//   customer_id: "1efdfc4845a7327bc9271ff0daafdae551d07524",
//   merchant_company: "PointWave Business",
//   ...
// }
```

### 2. Check Table Binding
```javascript
// Make sure you're using the correct field names
{customers.map(customer => (
  <tr key={customer.id}>
    <td>{customer.customer_id}</td>  {/* NOT customer.id */}
    <td>{customer.merchant_company}</td>  {/* NOT customer.company */}
  </tr>
))}
```

---

## Summary

**Backend**: ✅ Correct - Returns UUID as `customer_id` and company name as `merchant_company`

**Frontend**: ❌ Needs fix - Must display `customer.customer_id` and `customer.merchant_company`

The data is there, just needs to be mapped to the correct table columns!
