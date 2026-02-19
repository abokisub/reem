# Settlement Configuration Complete Guide

## Understanding Settlement Delay

### How to Set Different Delays

| Desired Delay | Settlement Delay (Hours) | Settlement Time | Skip Weekends | Skip Holidays |
|---------------|-------------------------|-----------------|---------------|---------------|
| **1 minute** | 0.0167 | 00:00:00 | ❌ No | ❌ No |
| **5 minutes** | 0.0833 | 00:00:00 | ❌ No | ❌ No |
| **10 minutes** | 0.1667 | 00:00:00 | ❌ No | ❌ No |
| **30 minutes** | 0.5 | 00:00:00 | ❌ No | ❌ No |
| **1 hour** | 1 | 00:00:00 | ❌ No | ❌ No |
| **2 hours** | 2 | 00:00:00 | ❌ No | ❌ No |
| **6 hours** | 6 | 00:00:00 | ❌ No | ❌ No |
| **12 hours** | 12 | 00:00:00 | ❌ No | ❌ No |
| **24 hours (1 day)** | 24 | 02:00:00 | ✅ Yes | ✅ Yes |
| **48 hours (2 days)** | 48 | 02:00:00 | ✅ Yes | ✅ Yes |

### Important Rules

1. **For delays < 24 hours**: 
   - Settlement Time is IGNORED (exact time is preserved)
   - Skip Weekends should be OFF
   - Skip Holidays should be OFF

2. **For delays ≥ 24 hours**:
   - Settlement Time is USED (e.g., 02:00:00 = 2am)
   - Skip Weekends can be ON
   - Skip Holidays can be ON

## Calculation Formula

```
Hours = Minutes ÷ 60

Examples:
- 1 minute = 1 ÷ 60 = 0.0167
- 5 minutes = 5 ÷ 60 = 0.0833
- 10 minutes = 10 ÷ 60 = 0.1667
- 30 minutes = 30 ÷ 60 = 0.5
- 90 minutes = 90 ÷ 60 = 1.5
```

## Testing on cPanel Terminal

### Test 1: Verify Current Settings

```bash
php artisan tinker --execute="
\$s = DB::table('settings')->first();
echo '=== Current Settlement Settings ===\n';
echo 'Auto Settlement: ' . (\$s->auto_settlement_enabled ? 'ENABLED' : 'DISABLED') . '\n';
echo 'Delay Hours: ' . \$s->settlement_delay_hours . ' hours\n';
echo 'Skip Weekends: ' . (\$s->settlement_skip_weekends ? 'YES' : 'NO') . '\n';
echo 'Skip Holidays: ' . (\$s->settlement_skip_holidays ? 'YES' : 'NO') . '\n';
echo 'Settlement Time: ' . \$s->settlement_time . '\n';
echo 'Minimum Amount: ₦' . number_format(\$s->settlement_minimum_amount, 2) . '\n';
"
```

### Test 2: Check Pending Settlements

```bash
php artisan tinker --execute="
\$pending = DB::table('settlement_queue')
    ->where('status', 'pending')
    ->orderBy('scheduled_settlement_date')
    ->get();

echo '=== Pending Settlements ===\n';
echo 'Total: ' . \$pending->count() . '\n\n';

foreach (\$pending as \$s) {
    echo 'Company ID: ' . \$s->company_id . '\n';
    echo 'Amount: ₦' . number_format(\$s->amount, 2) . '\n';
    echo 'Scheduled: ' . \$s->scheduled_settlement_date . '\n';
    echo '---\n';
}
"
```

### Test 3: Process Settlements Manually

```bash
php artisan settlements:process
```

### Test 4: Check Company Wallet Balance

```bash
php artisan tinker --execute="
\$wallet = \App\Models\CompanyWallet::where('company_id', 2)->first();
echo '=== Company Wallet (ID: 2) ===\n';
echo 'Balance: ₦' . number_format(\$wallet->balance, 2) . '\n';
echo 'Pending: ₦' . number_format(\$wallet->pending_balance, 2) . '\n';
echo 'Total: ₦' . number_format(\$wallet->balance + \$wallet->pending_balance, 2) . '\n';
"
```

### Test 5: Check Recent Transactions

```bash
php artisan tinker --execute="
\$txns = \App\Models\Transaction::where('company_id', 2)
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get();

echo '=== Recent Transactions (Company ID: 2) ===\n';
foreach (\$txns as \$t) {
    echo 'ID: ' . \$t->transaction_id . '\n';
    echo 'Amount: ₦' . number_format(\$t->amount, 2) . '\n';
    echo 'Status: ' . \$t->status . '\n';
    echo 'Created: ' . \$t->created_at . '\n';
    echo '---\n';
}
"
```

## Company Self-Funding vs Client Payments

### The Problem
Currently, when a company funds their own master virtual account, it goes through the settlement queue. This is wrong because:
- Company wallet should be credited instantly
- It shouldn't count as "pending settlement"
- It shouldn't count in "Total Transactions" or "Total Revenue" metrics
- These metrics are for tracking CLIENT payments, not company self-funding

### The Solution
We need to detect if the payment is:
1. **Company Self-Funding**: `company_user_id` is NULL → Credit wallet INSTANTLY, skip settlement queue
2. **Client Payment**: `company_user_id` has value → Use normal settlement flow

### How to Identify

```php
// In VirtualAccount model:
// - company_user_id = NULL → Master account (company's own account)
// - company_user_id = value → Client account (end user's account)

if ($virtualAccount->company_user_id === null) {
    // This is company funding their own account
    // → Credit wallet immediately
    // → Don't add to settlement queue
    // → Don't count in revenue metrics
} else {
    // This is a client payment
    // → Use settlement queue
    // → Count in revenue metrics
}
```

## Next Steps

I will now fix:
1. ✅ Toggle switches (Enable Auto Settlement, Skip Weekends, Skip Holidays)
2. ✅ Company self-funding bypass logic
3. ✅ Proper separation of company vs client transactions
