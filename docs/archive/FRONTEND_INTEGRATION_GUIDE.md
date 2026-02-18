# Frontend Integration Guide - Customer Details Page

## ⚠️ Issue: Virtual Account Details Not Showing

**Problem**: The Reserved Accounts table shows the Account ID but Bank Name, Account Number, and Account Name columns are empty.

**Root Cause**: Frontend is not correctly mapping the API response data to the table columns.

---

## Backend is Correct ✅

The API endpoint `/api/system/customer/detail/{customer_id}/{id}/secure` returns:

```json
{
  "status": "success",
  "customer": {
    "id": 1,
    "customer_id": "1efdfc4845a7327bc9271ff0daafdae551d07524",
    "email": "habukhan001@gmail.com",
    "phone": "08078889419",
    "first_name": "Jamil",
    "last_name": "Abubakar Bashir",
    "name": "Jamil Abubakar Bashir"
  },
  "reserved_accounts": [
    {
      "id": 1,
      "account_id": "BONITA-1771363432_100033",
      "bank_name": "PalmPay",
      "account_number": "6690945661",
      "account_name": "PointWave Business-Jamil Abubakar Bashir(PointWave)",
      "status": "active",
      "created_at": "17 Feb 2026, 09:23 PM"
    }
  ],
  "transactions": [],
  "cards": []
}
```

---

## Frontend Fix Required

### Current Table (What's Showing)

| Reserved Account ID | Customer Email | Bank Name | Account Number | Account Name | Status | Date | Action |
|---------------------|----------------|-----------|----------------|--------------|--------|------|--------|
| BONITA-1771363432_100033 | | **(EMPTY)** | **(EMPTY)** | **(EMPTY)** | active | | 👁️ |

### Expected Table (What Should Show)

| Reserved Account ID | Customer Email | Bank Name | Account Number | Account Name | Status | Date | Action |
|---------------------|----------------|-----------|----------------|--------------|--------|------|--------|
| BONITA-1771363432_100033 | habukhan001@gmail.com | **PalmPay** | **6690945661** | **PointWave Business-Jamil Abubakar Bashir(PointWave)** | active | 17 Feb 2026 | 👁️ |

---

## Frontend Code Fix

### React/Vue Component

```javascript
// Fetch customer details
const response = await fetch(`/api/system/customer/detail/${customerId}/${userId}/secure`, {
  headers: {
    'Origin': window.location.origin,
    'Authorization': `Bearer ${token}`
  }
});

const data = await response.json();

// Map reserved accounts to table
const reservedAccounts = data.reserved_accounts.map(account => ({
  // These fields MUST match the table column data keys
  reserved_account_id: account.account_id,           // ✅ Already showing
  customer_email: data.customer.email,               // ❌ Not showing - ADD THIS
  bank_name: account.bank_name,                      // ❌ Not showing - ADD THIS
  account_number: account.account_number,            // ❌ Not showing - ADD THIS
  account_name: account.account_name,                // ❌ Not showing - ADD THIS
  status: account.status,                            // ✅ Already showing
  date: account.created_at,                          // ✅ Already showing
  id: account.id                                     // For actions
}));
```

### Table Component

```jsx
<Table>
  <TableHeader>
    <TableRow>
      <TableHead>Reserved Account ID</TableHead>
      <TableHead>Customer Email</TableHead>
      <TableHead>Bank Name</TableHead>
      <TableHead>Account Number</TableHead>
      <TableHead>Account Name</TableHead>
      <TableHead>Status</TableHead>
      <TableHead>Date</TableHead>
      <TableHead>Action</TableHead>
    </TableRow>
  </TableHeader>
  <TableBody>
    {reservedAccounts.map((account) => (
      <TableRow key={account.id}>
        <TableCell>{account.reserved_account_id}</TableCell>
        <TableCell>{account.customer_email}</TableCell>
        <TableCell>{account.bank_name}</TableCell>           {/* FIX: Map this field */}
        <TableCell>{account.account_number}</TableCell>      {/* FIX: Map this field */}
        <TableCell>{account.account_name}</TableCell>        {/* FIX: Map this field */}
        <TableCell>{account.status}</TableCell>
        <TableCell>{account.date}</TableCell>
        <TableCell>
          <Button onClick={() => viewAccount(account.id)}>👁️</Button>
        </TableCell>
      </TableRow>
    ))}
  </TableBody>
</Table>
```

---

## Debugging Steps

### 1. Check API Response
Open browser DevTools → Network tab → Find the API call → Check response:

```javascript
// Should see this in response
{
  "reserved_accounts": [
    {
      "bank_name": "PalmPay",           // ← This exists!
      "account_number": "6690945661",   // ← This exists!
      "account_name": "PointWave Business-Jamil Abubakar Bashir(PointWave)" // ← This exists!
    }
  ]
}
```

### 2. Check Component State
Add console.log to see if data is received:

```javascript
console.log('Reserved Accounts:', data.reserved_accounts);
// Should print:
// [{ bank_name: "PalmPay", account_number: "6690945661", ... }]
```

### 3. Check Table Mapping
Verify the table is reading the correct field names:

```javascript
// WRONG ❌
<TableCell>{account.bankName}</TableCell>  // camelCase won't work

// CORRECT ✅
<TableCell>{account.bank_name}</TableCell>  // snake_case matches API
```

---

## Common Frontend Mistakes

### Mistake 1: Wrong Field Names
```javascript
// API returns: bank_name (snake_case)
// Frontend uses: bankName (camelCase)
// Result: Empty column ❌
```

**Fix**: Use exact field names from API
```javascript
account.bank_name  // ✅ Correct
```

### Mistake 2: Not Mapping Customer Email
```javascript
// Customer email is in data.customer.email, not in account object
<TableCell>{account.customer_email}</TableCell>  // ❌ Undefined

// Fix: Get from customer object
<TableCell>{data.customer.email}</TableCell>  // ✅ Correct
```

### Mistake 3: Conditional Rendering
```javascript
// If table only renders when data exists
{reservedAccounts.length > 0 && (
  <Table>...</Table>
)}

// But data might be undefined initially
// Fix: Add null check
{reservedAccounts && reservedAccounts.length > 0 && (
  <Table>...</Table>
)}
```

---

## Test Data

Use this customer for testing:

**Customer**: Jamil Abubakar Bashir (Bonita)
- Customer ID: 1
- Email: habukhan001@gmail.com
- Phone: 08078889419

**Virtual Account**:
- Account ID: BONITA-1771363432_100033
- Bank Name: PalmPay
- Account Number: 6690945661
- Account Name: PointWave Business-Jamil Abubakar Bashir(PointWave)
- Status: active

---

## Quick Fix Checklist

- [ ] API endpoint is correct: `/api/system/customer/detail/{customer_id}/{id}/secure`
- [ ] Response includes `reserved_accounts` array
- [ ] Each account has `bank_name`, `account_number`, `account_name`
- [ ] Table columns map to correct field names (snake_case)
- [ ] Customer email comes from `data.customer.email`
- [ ] Console.log shows data is received
- [ ] No JavaScript errors in console

---

## Expected Result

After fixing the frontend mapping, the table should show:

```
Reserved Account ID: BONITA-1771363432_100033
Customer Email: habukhan001@gmail.com
Bank Name: PalmPay
Account Number: 6690945661
Account Name: PointWave Business-Jamil Abubakar Bashir(PointWave)
Status: active
Date: 17 Feb 2026, 09:23 PM
```

**The backend is working correctly. The frontend just needs to map the data properly!**
