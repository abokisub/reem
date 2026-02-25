# Immediate Fix for Amtpay API Access

## Current Status (from diagnostic)
- Company: Amtpay (ID: 10)
- status: **'pending'** ❌ (should be 'active')
- is_active: **true** ✓
- Result: API LOCKED (middleware requires BOTH to be correct)

## Quick Fix on Live Server

### Option 1: Use the unlock script (RECOMMENDED)
```bash
cd /home/aboksdfs/app.pointwave.ng
php unlock_api_access.php
# Enter: Amtpay
# Confirm: yes
```

### Option 2: Direct SQL
```bash
mysql -u your_user -p your_database
```

```sql
-- Fix Amtpay
UPDATE companies 
SET status = 'active', is_active = 1 
WHERE id = 10;

-- Verify
SELECT id, name, status, is_active FROM companies WHERE id = 10;
```

### Option 3: Artisan Tinker
```bash
php artisan tinker --execute="
\$c = \App\Models\Company::find(10);
\$c->status = 'active';
\$c->is_active = true;
\$c->save();
echo 'Fixed: ' . \$c->name . ' - API Access: ' . (\$c->isActive() ? 'Unlocked' : 'Still Locked');
"
```

## After Fix - Deploy Code Updates

The code fixes prevent this from happening again:

```bash
cd /home/aboksdfs/app.pointwave.ng
git pull origin main
```

Changes deployed:
1. **Backend**: `updateSettings` now updates BOTH `status` and `is_active`
2. **Backend**: `getCredentials` returns `api_access_enabled` (checks both fields)
3. **Frontend**: Toggle now uses `api_access_enabled` instead of just `is_active`

## Verification

After fixing, test the API:
```bash
# The company should now be able to create customers
curl -X POST https://app.pointwave.ng/api/v1/customers \
  -H "Authorization: Bearer [their_secret_key]" \
  -H "x-business-id: be69a2c84af2784c15463d5bbc51b31a33005b52" \
  -H "x-api-key: 146adefa7c5029564bef..." \
  -H "Content-Type: application/json" \
  -d '{
    "first_name": "Test",
    "last_name": "User",
    "email": "test@example.com",
    "phone_number": "08012345678"
  }'
```

Should return 201 Created instead of 403 Forbidden.

## Root Cause Summary

1. **Default State**: New companies created with `status='pending'` and `is_active=false`
2. **KYC Approval**: Admin approves KYC, sets `status='active'` but forgets `is_active`
3. **Dashboard Toggle**: Company tries to enable API, but toggle only updated `is_active`, not `status`
4. **Result**: One field correct, one field wrong = API still locked
5. **UI Bug**: Dashboard showed "Unlocked" because it only checked `is_active`

## Prevention

The code fix ensures:
- When company toggles API access, BOTH fields update together
- Dashboard shows correct state by checking BOTH fields
- No more mismatch between UI and actual API access
