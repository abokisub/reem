# Settlement Delay Configuration Guide

## Problem
The settlement delay field was only accepting whole hours (integers), preventing you from setting delays like 1 minute or 30 minutes.

## Solution Applied
Changed the system to support fractional hours (decimals) so you can set any delay from 1 minute to 168 hours.

## Changes Made

### 1. Database Migration
- **File**: `database/migrations/2026_02_19_000000_change_settlement_delay_to_decimal.php`
- **Change**: Column type changed from `integer` to `decimal(8,4)`
- **Impact**: Can now store values like 0.0167, 0.5, 1.5, etc.

### 2. Backend Validation
- **File**: `app/Http/Controllers/API/AdminController.php`
- **Change**: Line 3898 - Changed from `(int)` to `(float)` and minimum from 1 to 0.0167
- **Impact**: Backend now accepts and validates decimal values

### 3. Frontend Validation
- **File**: `frontend/src/sections/admin/TransferChargesInt.js`
- **Changes**:
  - Line 32: Yup validation minimum changed from 1 to 0.0167
  - Added `inputProps={{ step: "0.0001" }}` to allow decimal input
  - Updated help text with examples
- **Impact**: Frontend form now accepts decimal values

## How to Deploy

Run the deployment script:
```bash
./ENABLE_FRACTIONAL_SETTLEMENT_HOURS.sh
```

Or manually:
```bash
# 1. Run migration
php artisan migrate --force

# 2. Build frontend
cd frontend
npm run build
cd ..
```

## How to Use

### Via Admin Dashboard
1. Login as admin
2. Go to: **Admin > Discount/Charges > Bank Transfer Charges**
3. Scroll to **Settlement Rules** section
4. Enter decimal value in **Settlement Delay (Hours)** field
5. Click **Save All Charges**

### Common Values

| Delay | Hours Value | Description |
|-------|-------------|-------------|
| 1 minute | 0.0167 | Fastest settlement |
| 5 minutes | 0.0833 | Very fast |
| 10 minutes | 0.1667 | Fast |
| 30 minutes | 0.5 | Half hour |
| 1 hour | 1 | One hour |
| 2 hours | 2 | Two hours |
| 6 hours | 6 | Quarter day |
| 12 hours | 12 | Half day |
| 24 hours | 24 | One day (default) |
| 48 hours | 48 | Two days |
| 168 hours | 168 | One week (maximum) |

### Calculation Formula
```
Hours = Minutes ÷ 60

Examples:
- 1 minute = 1 ÷ 60 = 0.0167
- 5 minutes = 5 ÷ 60 = 0.0833
- 10 minutes = 10 ÷ 60 = 0.1667
- 30 minutes = 30 ÷ 60 = 0.5
- 90 minutes = 90 ÷ 60 = 1.5
```

## Testing

### Test 1-Minute Settlement
1. Set settlement delay to `0.0167` hours
2. Uncheck "Skip Weekends" and "Skip Holidays"
3. Save changes
4. Send ₦250 to PalmPay account 6644694207
5. Wait 1 minute
6. Run: `php artisan settlements:process`
7. Check company wallet - balance should be updated

### Verify Current Settings
```bash
php artisan tinker --execute="
\$settings = DB::table('settings')->first();
echo 'Settlement Delay: ' . \$settings->settlement_delay_hours . ' hours\n';
echo 'Auto Settlement: ' . (\$settings->auto_settlement_enabled ? 'Enabled' : 'Disabled') . '\n';
echo 'Skip Weekends: ' . (\$settings->settlement_skip_weekends ? 'Yes' : 'No') . '\n';
"
```

## Important Notes

1. **Minimum Value**: 0.0167 hours (1 minute)
2. **Maximum Value**: 168 hours (1 week)
3. **Precision**: Up to 4 decimal places (0.0001)
4. **Skip Weekends/Holidays**: For very short delays (< 1 hour), you should disable these options
5. **Settlement Time**: Only applies to delays of 24+ hours. For shorter delays, exact time is preserved.

## Troubleshooting

### Frontend shows validation error
- Make sure you entered a value between 0.0167 and 168
- Check that you're using decimal point (.) not comma (,)
- Clear browser cache and reload page

### Backend rejects the value
- Verify migration ran successfully: `php artisan migrate:status`
- Check column type: `php artisan tinker --execute="DB::select('DESCRIBE settings')"`

### Settlement not processing
- Check if auto settlement is enabled
- Verify cron job is running: `php artisan settlements:process`
- Check logs: `tail -f storage/logs/laravel.log`

## Files Modified

1. `database/migrations/2026_02_19_000000_change_settlement_delay_to_decimal.php` (NEW)
2. `app/Http/Controllers/API/AdminController.php` (MODIFIED)
3. `frontend/src/sections/admin/TransferChargesInt.js` (MODIFIED)
4. `ENABLE_FRACTIONAL_SETTLEMENT_HOURS.sh` (NEW)
5. `SETTLEMENT_DELAY_GUIDE.md` (NEW)

## Next Steps

1. Run the deployment script
2. Test with 1-minute settlement
3. Adjust to your preferred delay
4. Monitor settlement queue: `SELECT * FROM settlement_queue WHERE status='pending'`
