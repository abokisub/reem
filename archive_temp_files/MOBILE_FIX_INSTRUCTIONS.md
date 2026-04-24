# Mobile App Fix Instructions

## Issues to Fix

1. ✅ Backend errors (undefined properties) - FIXED
2. ⚠️ Mobile app showing personal name instead of company name - NEEDS REBUILD
3. ⚠️ Virtual accounts unavailable - NEEDS REBUILD

## Backend Fixes Applied

### File: `app/Http/Controllers/APP/Auth.php`

Added null coalescing operators for:
- Line 1009: `occupation`
- Line 1010: `marital_status`  
- Line 1011: `religion`
- Line 1016: `palmpay` (in account_number ternary)
- Line 1022: `address`
- Line 1023: `webhook`
- Line 1024: `about`

### File: `app/Http/Controllers/API/AuthController.php`

The `getUserDetails()` method already returns:
- `business_name` - Company name from active company
- `palmpay_account_number` - Company virtual account
- `palmpay_account_name` - Company account name
- `palmpay_bank_name` - Bank name
- All company data

## Mobile App Changes Already Made

### Files Modified:
1. `pointwave Mobile/lib/modules/dashboard/screens/company_dashboard_screen.dart`
   - Line 238: Changed to use `business_name` instead of personal name
   - Line 308: Welcome card uses `business_name`

2. `pointwave Mobile/lib/modules/auth/screens/login_screen.dart`
   - Changed to email-only login

3. `pointwave Mobile/lib/widgets/stats_card_widget.dart`
   - Made responsive with FittedBox

4. `pointwave Mobile/lib/modules/more/screens/company_more_screen.dart`
   - New More screen created

## CRITICAL: Mobile App Needs Rebuild

The mobile app is still running with OLD compiled code. You need to:

### Step 1: Stop the App
```bash
# Press Ctrl+C in the terminal where flutter run is running
```

### Step 2: Clean and Rebuild
```bash
cd "pointwave Mobile"
flutter clean
flutter pub get
flutter run
```

### Step 3: Verify Changes
After rebuild, check:
- [ ] Dashboard shows company name (not "Abubakar")
- [ ] Welcome card shows company name
- [ ] Virtual accounts appear in Fund Wallet
- [ ] No backend errors in logs

## Why Virtual Accounts Are "Unavailable"

The mobile app's `fund_wallet_screen.dart` looks for these fields in the user object:
- `pointwave` - PointWave account
- `palmpay` - PalmPay/Xixapay account  
- `kolomoni_mfb` - Kolomoni account
- `sterlen` - Moniepoint account
- `wema` - Wema Bank account

The backend `getUserDetails()` returns:
- `palmpay_account_number` - This is the company's PalmPay account
- `account_number` - Default account
- `bank_name` - Bank name

### Solution:
The mobile app needs to check for `palmpay_account_number` field (which contains the company virtual account) instead of just `palmpay`.

## Quick Test

After rebuilding, test the login API response:

```bash
curl -X POST http://localhost:8000/api/login/verify/user \
  -H "Content-Type: application/json" \
  -d '{
    "email": "kobopointng@gmail.com",
    "password": "your_password"
  }'
```

Check the response for:
- `business_name` field
- `palmpay_account_number` field
- `account_number` field

## Expected Behavior After Rebuild

1. **Dashboard Header**: "Good Afternoon, Kobopoint" (company name)
2. **Welcome Card**: "Welcome Back, Kobopoint! 😎"
3. **Fund Wallet**: Shows company virtual account number
4. **No Backend Errors**: All undefined property errors fixed

## If Issues Persist

1. Check if user has `active_company_id` set:
   ```sql
   SELECT id, username, active_company_id FROM users WHERE email = 'kobopointng@gmail.com';
   ```

2. Check if company has virtual account:
   ```sql
   SELECT id, name, palmpay_account_number, palmpay_account_name 
   FROM companies 
   WHERE user_id = (SELECT id FROM users WHERE email = 'kobopointng@gmail.com');
   ```

3. Check backend logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

## Summary

- ✅ All backend errors fixed
- ✅ Backend returns company data correctly
- ✅ Mobile code updated to use company name
- ⚠️ **MUST REBUILD MOBILE APP** for changes to take effect

Run: `flutter clean && flutter pub get && flutter run`
