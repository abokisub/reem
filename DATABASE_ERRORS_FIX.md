# 🔧 Database Errors Fix - PointWave

## 🐛 Issues Found

From the production logs (2026-02-18 17:28:41), two critical database errors were identified:

### 1. Missing Columns in Transactions Table
```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'net_amount' in 'INSERT INTO'
```

**Impact:** PalmPay webhooks are failing. Payments are being received but not recorded in the database.

**Cause:** The webhook handler is trying to insert `net_amount` and `total_amount` columns that don't exist yet.

### 2. Missing 'data' Table
```
SQLSTATE[42S02]: Base table or view not found: 1146 Table 'aboksdfs_pointwave.data' doesn't exist
```

**Impact:** Dashboard errors when loading certain pages.

**Cause:** Old PointPay code in `SecureController.php` line 2625 trying to query a `data` table (for data purchases) that doesn't exist in PointWave.

---

## ✅ Fixes Applied

### Fix 1: Add Missing Columns to Transactions Table

**Migration:** `database/migrations/2026_02_18_173000_add_net_amount_to_transactions.php`

Adds two columns:
- `net_amount` - Amount after deducting fees (e.g., ₦248.75 from ₦250 payment)
- `total_amount` - Total transaction amount (e.g., ₦250)

**Safe to run:** Uses `Schema::hasColumn()` checks to avoid errors if columns already exist.

### Fix 2: Fixed DataPurchased Function

**File:** `app/Http/Controllers/API/SecureController.php`

**Change:** Removed queries to non-existent `data` table. Now returns:
```json
{
  "status": "success",
  "data_purchased_amount": 0,
  "data_purchased_volume": "0GB"
}
```

**Reason:** PointWave is a payment gateway, not a data reseller. This endpoint is kept for backward compatibility but returns 0.

---

## 🚀 Deployment Steps

### Option 1: Automated Script (Recommended)

```bash
# Make script executable
chmod +x FIX_DATABASE_ERRORS.sh

# Run the script
./FIX_DATABASE_ERRORS.sh
```

### Option 2: Manual Steps

```bash
# 1. Run the migration
php artisan migrate --path=database/migrations/2026_02_18_173000_add_net_amount_to_transactions.php

# 2. Verify columns were added
php artisan tinker
>>> Schema::hasColumn('transactions', 'net_amount')
=> true
>>> Schema::hasColumn('transactions', 'total_amount')
=> true
>>> exit

# 3. Clear cache
php artisan config:clear
php artisan cache:clear
```

---

## 🧪 Testing

### Test 1: Webhook Processing

1. Send ₦250 to PalmPay account: **6644694207**
2. Check webhook logs:
   ```bash
   tail -f storage/logs/laravel.log | grep "PalmPay Webhook"
   ```
3. Verify transaction is created:
   ```bash
   php artisan tinker
   >>> \App\Models\Transaction::latest()->first()
   ```
4. Should see `net_amount` and `total_amount` populated

### Test 2: Dashboard Access

1. Login to company dashboard: https://app.pointwave.ng/auth/login
2. Navigate through all pages
3. Check for errors in browser console
4. Verify no database errors in logs

---

## 📊 Expected Results

### Before Fix
```
[2026-02-18 17:28:41] production.ERROR: Failed to Process Virtual Account Credit
{"error":"SQLSTATE[42S22]: Column not found: 1054 Unknown column 'net_amount'..."}
```

### After Fix
```
[2026-02-18 17:XX:XX] production.INFO: Virtual Account Credit Processed Successfully
{"transaction_id":"txn_xxx","amount":250,"fee":1.25,"net_amount":248.75}
```

---

## 🔍 Verification Checklist

- [ ] Migration ran successfully
- [ ] `net_amount` column exists in transactions table
- [ ] `total_amount` column exists in transactions table
- [ ] Test webhook processes successfully
- [ ] Transaction is created with correct amounts
- [ ] Dashboard loads without errors
- [ ] No database errors in logs
- [ ] Company wallet balance updates correctly

---

## 📝 Database Schema Changes

### Transactions Table (After Migration)

| Column | Type | Description |
|--------|------|-------------|
| amount | decimal(15,2) | Original transaction amount |
| fee | decimal(15,2) | Fee charged |
| **net_amount** | **decimal(15,2)** | **Amount after fees (NEW)** |
| **total_amount** | **decimal(15,2)** | **Total amount (NEW)** |

### Example Transaction

```
amount: 250.00
fee: 1.25
net_amount: 248.75  (250 - 1.25)
total_amount: 250.00
```

---

## 🚨 Rollback (If Needed)

If something goes wrong, you can rollback:

```bash
# Rollback the migration
php artisan migrate:rollback --step=1

# This will remove the net_amount and total_amount columns
```

**Note:** Only rollback if absolutely necessary. The webhook will fail again without these columns.

---

## 📞 Support

If you encounter any issues:

1. Check logs: `tail -f storage/logs/laravel.log`
2. Verify database: `php artisan tinker`
3. Contact support: support@pointwave.ng

---

## 📅 Timeline

- **Issue Discovered:** 2026-02-18 17:28:41
- **Fix Created:** 2026-02-18 17:30:00
- **Status:** ✅ Ready to Deploy

---

**Version:** 1.0.0  
**Last Updated:** February 18, 2026  
**Priority:** 🔴 Critical (Webhooks failing)
