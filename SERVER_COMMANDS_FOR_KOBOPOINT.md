# Server Commands to Check KoboPoint Balance

## Step 1: SSH to Server
```bash
ssh aboksdfs@your-server-ip
```

## Step 2: Navigate to Application Directory
```bash
cd /home/aboksdfs/app.pointwave.ng
```

## Step 3: Check KoboPoint's Balance
```bash
php artisan tinker --execute="
\$kobopoint = DB::table('companies')->where('name', 'LIKE', '%kobo%')->first();
if (\$kobopoint) {
    echo 'Company: ' . \$kobopoint->name . PHP_EOL;
    echo 'Company ID: ' . \$kobopoint->id . PHP_EOL;
    \$wallet = DB::table('company_wallets')->where('company_id', \$kobopoint->id)->where('currency', 'NGN')->first();
    if (\$wallet) {
        echo 'Balance: ₦' . number_format(\$wallet->balance, 2) . PHP_EOL;
        echo 'Ledger Balance: ₦' . number_format(\$wallet->ledger_balance, 2) . PHP_EOL;
        echo PHP_EOL;
        echo 'For ₦100 transfer:' . PHP_EOL;
        echo '  Amount: ₦100' . PHP_EOL;
        echo '  Fee: ₦30' . PHP_EOL;
        echo '  Total Required: ₦130' . PHP_EOL;
        echo '  Current Balance: ₦' . number_format(\$wallet->balance, 2) . PHP_EOL;
        if (\$wallet->balance >= 130) {
            echo '  ✅ SUFFICIENT' . PHP_EOL;
        } else {
            echo '  ❌ INSUFFICIENT - Need ₦' . number_format(130 - \$wallet->balance, 2) . ' more' . PHP_EOL;
        }
    } else {
        echo 'No wallet found' . PHP_EOL;
    }
} else {
    echo 'KoboPoint company not found' . PHP_EOL;
}
"
```

## Alternative: Use the Check Script
```bash
cd /home/aboksdfs/app.pointwave.ng
php check_kobopoint_balance.php
```

## Step 4: Check Recent Transfer Attempts
```bash
php artisan tinker --execute="
\$kobopoint = DB::table('companies')->where('name', 'LIKE', '%kobo%')->first();
if (\$kobopoint) {
    echo 'Recent transfer attempts for ' . \$kobopoint->name . ':' . PHP_EOL . PHP_EOL;
    \$transactions = DB::table('transactions')
        ->where('company_id', \$kobopoint->id)
        ->where('category', 'transfer_out')
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get();
    foreach (\$transactions as \$txn) {
        echo 'Reference: ' . \$txn->reference . PHP_EOL;
        echo 'Amount: ₦' . \$txn->amount . PHP_EOL;
        echo 'Fee: ₦' . \$txn->fee . PHP_EOL;
        echo 'Total: ₦' . \$txn->total_amount . PHP_EOL;
        echo 'Status: ' . \$txn->status . PHP_EOL;
        echo 'Date: ' . \$txn->created_at . PHP_EOL;
        if (\$txn->error_message) {
            echo 'Error: ' . \$txn->error_message . PHP_EOL;
        }
        echo '---' . PHP_EOL;
    }
}
"
```

## Step 5: Check Latest Logs
```bash
tail -n 50 storage/logs/laravel.log | grep -i "transfer\|kobo\|insufficient"
```

## What to Look For

### If Balance is Sufficient (≥ ₦130)
- ✅ The issue is NOT balance
- Check if KoboPoint is using correct API credentials
- Check if they're calling the right endpoint

### If Balance is Insufficient (< ₦130)
- ❌ KoboPoint needs to fund their PointWave business wallet
- They can fund via:
  1. Bank transfer to their PointWave virtual account
  2. Dashboard wallet funding
  3. Contact PointWave support

## Understanding the Balances

**PointWave has TWO separate balances:**

1. **KoboPoint's Business Wallet on PointWave** (company_wallets table)
   - This is what we're checking
   - This is what gets debited when KoboPoint makes transfers
   - Needs to have ≥ ₦130 for a ₦100 transfer

2. **KoboPoint End User's Wallet in KoboPoint App**
   - This is managed by KoboPoint's own system
   - Not visible to PointWave
   - KoboPoint deducts from this first, then calls PointWave API

## The Flow

```
User has ₦268 in KoboPoint app
    ↓
User tries to send ₦100 to bank
    ↓
KoboPoint deducts ₦25 from user (their fee)
    ↓
KoboPoint calls PointWave API to transfer ₦100
    ↓
PointWave checks: Does KoboPoint business wallet have ≥ ₦130?
    ↓
If YES: Transfer proceeds
If NO: Error "Insufficient balance. Required: 130"
```

## Quick Fix Commands

### If you need to manually add balance for testing:
```bash
php artisan tinker --execute="
\$kobopoint = DB::table('companies')->where('name', 'LIKE', '%kobo%')->first();
if (\$kobopoint) {
    DB::table('company_wallets')
        ->where('company_id', \$kobopoint->id)
        ->where('currency', 'NGN')
        ->increment('balance', 500);
    echo 'Added ₦500 to KoboPoint wallet' . PHP_EOL;
    \$wallet = DB::table('company_wallets')->where('company_id', \$kobopoint->id)->where('currency', 'NGN')->first();
    echo 'New Balance: ₦' . number_format(\$wallet->balance, 2) . PHP_EOL;
}
"
```

---

**Note:** Run these commands on the production server at `/home/aboksdfs/app.pointwave.ng`
