# Fractional Settlement Hours - Implementation Summary

## Problem
You wanted to set settlement delay to 1 minute, but the system only accepted whole hours (integers). Entering `0.30` or `0.0167` showed validation errors.

## Solution
Changed the system to support fractional hours (decimals) from 0.0167 hours (1 minute) to 168 hours (1 week).

## What Was Changed

### 1. Backend (PUSHED TO GITHUB ✅)

#### Database Migration
- **File**: `database/migrations/2026_02_19_000000_change_settlement_delay_to_decimal.php`
- **Change**: Column type from `integer` to `decimal(8,4)`
- **Impact**: Database can now store values like 0.0167, 0.5, 1.5, etc.

#### Controller Validation
- **File**: `app/Http/Controllers/API/AdminController.php`
- **Line**: ~3898
- **Change**: 
  ```php
  // OLD:
  max(1, min(168, (int) $request->settlement_delay_hours))
  
  // NEW:
  max(0.0167, min(168, (float) $request->settlement_delay_hours))
  ```
- **Impact**: Backend accepts decimal values with minimum 1 minute

### 2. Frontend (NOT PUSHED - YOU BUILD MANUALLY ⚠️)

#### Validation Schema
- **File**: `frontend/src/sections/admin/TransferChargesInt.js`
- **Line**: 32
- **Change**:
  ```javascript
  // OLD:
  settlement_delay_hours: Yup.number().min(1).max(168),
  
  // NEW:
  settlement_delay_hours: Yup.number().min(0.0167).max(168),
  ```

#### Input Field
- **File**: `frontend/src/sections/admin/TransferChargesInt.js`
- **Line**: ~150
- **Change**: Added `inputProps={{ step: "0.0001" }}` and updated help text

## Deployment Steps

### On Production Server

```bash
# 1. Pull backend changes
cd /home/aboksdfs/app.pointwave.ng
git pull origin main

# 2. Run migration
php artisan migrate --force

# 3. Build frontend manually
cd frontend
npm run build
cd ..
```

Or use the script:
```bash
./DEPLOY_BACKEND_SETTLEMENT_FIX.sh
# Then manually: cd frontend && npm run build
```

## How to Use

### Set 1 Minute Settlement

1. Login as admin at https://app.pointwave.ng/admin
2. Go to: **Admin > Discount/Charges > Bank Transfer Charges**
3. Scroll to **Settlement Rules** section
4. Enter `0.0167` in **Settlement Delay (Hours)** field
5. Uncheck **Skip Weekends** and **Skip Holidays**
6. Click **Save All Charges**

### Common Values

| Delay | Hours Value | Calculation |
|-------|-------------|-------------|
| 1 minute | 0.0167 | 1 ÷ 60 |
| 5 minutes | 0.0833 | 5 ÷ 60 |
| 10 minutes | 0.1667 | 10 ÷ 60 |
| 30 minutes | 0.5 | 30 ÷ 60 |
| 1 hour | 1 | - |
| 2 hours | 2 | - |
| 24 hours | 24 | - |

### Formula
```
Hours = Minutes ÷ 60
```

## Testing

### Test 1-Minute Settlement

```bash
# 1. Set delay to 0.0167 via admin dashboard

# 2. Send test payment
# Transfer ₦250 to PalmPay account: 6644694207

# 3. Wait 1 minute

# 4. Process settlements
php artisan settlements:process

# 5. Check wallet balance
php artisan tinker --execute="
\$wallet = \App\Models\CompanyWallet::where('company_id', 2)->first();
echo 'Balance: ₦' . number_format(\$wallet->balance, 2) . '\n';
"
```

### Verify Settings

```bash
php artisan tinker --execute="
\$s = DB::table('settings')->first();
echo 'Settlement Delay: ' . \$s->settlement_delay_hours . ' hours\n';
echo 'Auto Settlement: ' . (\$s->auto_settlement_enabled ? 'Enabled' : 'Disabled') . '\n';
echo 'Skip Weekends: ' . (\$s->settlement_skip_weekends ? 'Yes' : 'No') . '\n';
"
```

## Important Notes

1. **Minimum**: 0.0167 hours (1 minute)
2. **Maximum**: 168 hours (1 week)
3. **Precision**: Up to 4 decimal places
4. **Skip Options**: For delays < 1 hour, disable "Skip Weekends" and "Skip Holidays"
5. **Settlement Time**: Only applies to delays ≥ 24 hours

## Files Modified

### Backend (Pushed to GitHub)
- `app/Http/Controllers/API/AdminController.php`
- `database/migrations/2026_02_19_000000_change_settlement_delay_to_decimal.php`
- `DEPLOY_BACKEND_SETTLEMENT_FIX.sh`
- `FRONTEND_BUILD_INSTRUCTIONS.md`

### Frontend (NOT Pushed - Build Manually)
- `frontend/src/sections/admin/TransferChargesInt.js`

## Troubleshooting

### Frontend still shows validation error
- Clear browser cache (Ctrl+Shift+R)
- Verify frontend was built: `ls -la frontend/build/`
- Check browser console for errors

### Backend rejects value
- Verify migration ran: `php artisan migrate:status`
- Check column type: 
  ```bash
  php artisan tinker --execute="
  DB::select('DESCRIBE settings');
  "
  ```

### Settlement not processing
- Check auto settlement is enabled
- Verify cron job: `php artisan settlements:process`
- Check logs: `tail -f storage/logs/laravel.log`

## Next Steps

1. ✅ Backend deployed to GitHub
2. ⏳ Pull changes on production server
3. ⏳ Run migration
4. ⏳ Build frontend manually
5. ⏳ Test with 1-minute settlement
6. ⏳ Adjust to your preferred delay
