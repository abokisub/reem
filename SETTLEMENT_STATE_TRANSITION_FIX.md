# Settlement State Transition Fix

## Issue
Settlement transfers were failing with error:
```
Invalid state transition: [pending] → [processing] for txn txn_699733ba04e3871390
```

## Root Cause
The PalmPay TransferService was creating transactions with status 'pending', but then trying to transition them to 'processing'. According to the FinancialStateService state machine, 'pending' can only transition to 'successful' or 'failed', not 'processing'.

The valid state transitions are:
- **Withdrawal lifecycle**: initiated → debited → processing → successful/failed → settled
- **Deposit lifecycle**: pending → successful/failed

## Solution
Changed the initial transaction status from 'pending' to 'debited' in the TransferService. This allows the proper state transition flow:
- debited → processing → successful/failed

## Files Changed
- `app/Services/PalmPay/TransferService.php` - Line 129: Changed initial status from 'pending' to 'debited'

## Testing
After deployment:
1. Initiate a settlement transfer
2. Check logs - should no longer see "Invalid state transition" errors
3. Verify settlement completes successfully
4. Check company wallet balance is credited correctly

## Deployment
```bash
# On production server
cd /home/aboksdfs/app.pointwave.ng
git pull origin main
php artisan config:clear
php artisan cache:clear
```

## Status
✅ Fixed and ready to push to GitHub
