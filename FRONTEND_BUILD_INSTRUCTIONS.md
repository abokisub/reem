# Frontend Build Instructions

## After Backend Deployment

Once you've deployed the backend changes, you need to manually build the frontend to enable fractional settlement hours in the UI.

## Frontend Changes Made

**File**: `frontend/src/sections/admin/TransferChargesInt.js`

### Change 1: Validation (Line 32)
```javascript
// OLD:
settlement_delay_hours: Yup.number().min(1).max(168),

// NEW:
settlement_delay_hours: Yup.number().min(0.0167).max(168), // Allow minimum 1 minute
```

### Change 2: Input Field (Around Line 150)
```javascript
// OLD:
<RHFTextField 
    name="settlement_delay_hours" 
    label="Settlement Delay (Hours)" 
    type="number"
    helperText="Hours to delay settlement (1-168). Common: 1h, 7h, 24h"
/>

// NEW:
<RHFTextField 
    name="settlement_delay_hours" 
    label="Settlement Delay (Hours)" 
    type="number"
    inputProps={{ step: "0.0001" }}
    helperText="Hours to delay settlement (0.0167-168). Examples: 0.0167 = 1min, 1 = 1h, 24 = 1day"
/>
```

## How to Build Frontend

### On Production Server

```bash
cd /home/aboksdfs/app.pointwave.ng/frontend
npm run build
```

### On Local Machine (then upload)

```bash
cd frontend
npm run build

# Then upload the build folder to server
scp -r build/* user@server:/home/aboksdfs/app.pointwave.ng/frontend/build/
```

## Verify Changes

After building, test in browser:

1. Login as admin
2. Go to: Admin > Discount/Charges > Bank Transfer Charges
3. Scroll to Settlement Rules section
4. Try entering `0.0167` in Settlement Delay field
5. Should accept without validation error
6. Save and verify in database:

```bash
php artisan tinker --execute="
\$s = DB::table('settings')->first();
echo 'Settlement Delay: ' . \$s->settlement_delay_hours . ' hours\n';
"
```

## Common Values Reference

| Delay | Hours Value |
|-------|-------------|
| 1 minute | 0.0167 |
| 5 minutes | 0.0833 |
| 10 minutes | 0.1667 |
| 30 minutes | 0.5 |
| 1 hour | 1 |
| 24 hours | 24 |

## Troubleshooting

### Validation still shows error
- Clear browser cache (Ctrl+Shift+R)
- Check browser console for errors
- Verify build completed successfully

### Changes not visible
- Make sure you're looking at the correct URL
- Check that build files were copied to correct location
- Restart web server if needed
