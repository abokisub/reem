# Transfer Fee Configuration & Error Message Fix

## Issues Fixed

### 1. External Transfer Fee Using Wrong Settings Columns
**Problem**: External transfers were reading from `transfer_charge_*` columns (which are for "Funding with Bank Transfer") instead of `payout_bank_*` columns (which are for "External Transfer (Other Banks)").

**Root Cause**: 
- Admin dashboard saves "External Transfer (Other Banks)" settings to: `payout_bank_charge_type`, `payout_bank_charge_value`, `payout_bank_charge_cap`
- But the code was reading from: `transfer_charge_type`, `transfer_charge_value`, `transfer_charge_cap`
- These `transfer_charge_*` columns are actually for deposit fees, not transfer fees

**Fix Applied**:
- Updated `TransferPurchase.php` line 424-426 to read from correct columns:
```php
// External Transfer (Other Banks) - use payout_bank_* columns
$type = $settings->payout_bank_charge_type ?? 'FLAT';
$value = $settings->payout_bank_charge_value ?? 0;
$cap = $settings->payout_bank_charge_cap ?? 0;
```

**Expected Result After Fix**:
- External transfers will now use the ₦30 flat fee configured in admin dashboard
- Logs will show: `"type":"FLAT","flat_fee":"30.00","final_charge":"30.00"`

---

### 2. Misleading "Insufficient Funds" Error for Provider Balance Issues
**Problem**: When PalmPay has insufficient balance, the frontend shows "Your current wallet balance is ₦717. Please fund your wallet" even though the user has enough money.

**Root Cause**:
- Backend correctly converts PalmPay's "Insufficient Funds" error to "Service temporarily unavailable (Low Balance)"
- But frontend was checking if error message contains "insufficient" OR "balance" (too broad)
- This caught provider balance errors and showed the wrong dialog

**Fix Applied**:
- Updated `TransferFunds.js` to be more specific about user wallet balance errors:
```javascript
// Check if it's a USER wallet balance issue (not provider balance issue)
const isUserBalanceIssue = (
    errorMessage.toLowerCase().includes('insufficient') && 
    errorMessage.toLowerCase().includes('wallet')
) || (
    errorMessage.toLowerCase().includes('balance') && 
    errorMessage.toLowerCase().includes('fund your wallet')
);
```

**Expected Result After Fix**:
- Provider balance errors will show as a snackbar: "Service temporarily unavailable (Low Balance). Please try again later."
- User wallet balance errors will still show the "Insufficient Funds" dialog with "Fund Wallet" button

---

## Files Changed

### Backend (Pushed to GitHub - commit be225a0)
- `app/Http/Controllers/Purchase/TransferPurchase.php`

### Frontend (NOT pushed - manual update required)
- `frontend/src/pages/dashboard/TransferFunds.js`

---

## Deployment Steps

### 1. Pull Backend Changes
```bash
cd ~/Documents/pointpay
git pull origin main
```

### 2. Update Frontend File Manually
Copy the updated `TransferFunds.js` to your live server:
```bash
# On your local machine, copy the file to live server
scp frontend/src/pages/dashboard/TransferFunds.js user@server:/path/to/pointpay/frontend/src/pages/dashboard/
```

### 3. Rebuild Frontend
```bash
cd frontend
npm run build
```

### 4. Test External Transfer
- Try transferring ₦100 to an external bank account
- Check logs: Should show `"type":"FLAT","flat_fee":"30.00"`
- Total deduction should be ₦130 (₦100 + ₦30 fee)

### 5. Test Provider Balance Error
- When PalmPay has no balance, you should see:
  - Snackbar message: "Service temporarily unavailable (Low Balance). Please try again later."
  - NOT the "Insufficient Funds" dialog with "Fund Wallet" button

---

## Settings Column Reference

| Fee Type | Admin Dashboard Label | Database Columns |
|----------|----------------------|------------------|
| Funding with Bank Transfer (Deposits) | Pay with Transfer | `transfer_charge_type`, `transfer_charge_value`, `transfer_charge_cap` |
| Internal Transfer (Wallet) | Pay with Wallet | `wallet_charge_type`, `wallet_charge_value`, `wallet_charge_cap` |
| External Transfer (Other Banks) | Payout to Bank | `payout_bank_charge_type`, `payout_bank_charge_value`, `payout_bank_charge_cap` |
| Settlement Withdrawal | Payout to PalmPay | `payout_palmpay_charge_type`, `payout_palmpay_charge_value`, `payout_palmpay_charge_cap` |

---

## Testing Checklist

- [ ] Pull latest backend changes from GitHub
- [ ] Update frontend TransferFunds.js file
- [ ] Rebuild frontend
- [ ] Test external transfer - verify ₦30 flat fee is charged
- [ ] Check logs - should show correct fee type and amount
- [ ] Test with PalmPay out of balance - should show provider error, not wallet error
- [ ] Test with actual low wallet balance - should show "Insufficient Funds" dialog

---

## Notes

- The old logs you showed still had PERCENTAGE 0.50% because you hadn't pulled the fix yet
- After pulling and testing, the logs should show FLAT ₦30.00 for external transfers
- Settlement withdrawals will continue to use the ₦15 flat fee (payout_palmpay_* columns)
