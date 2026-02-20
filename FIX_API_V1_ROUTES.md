# Fix API V1 Routes

## Issues Found:

1. **PUT routes not working** - Route cache issue
2. **KYC endpoints returning 401** - Using wrong middleware (`auth.token` instead of `MerchantAuth`)
3. **GET customer returns null** - Response format issue

## Solutions:

### 1. Clear Route Cache on Server

```bash
cd /home/aboksdfs/app.pointwave.ng
php artisan route:clear
php artisan config:clear
php artisan cache:clear
composer dump-autoload
```

### 2. Fix KYC Routes Middleware

The KYC routes currently use `auth.token` (session-based) but should use `MerchantAuth` (API key-based) for V1 API.

**File:** `routes/api.php` (lines 820-830)

**Change FROM:**
```php
Route::middleware(['auth.token'])->prefix('v1/kyc')->group(function () {
```

**Change TO:**
```php
Route::middleware([\App\Http\Middleware\V1\MerchantAuth::class])->prefix('v1/kyc')->group(function () {
```

### 3. Test Results Summary

| Endpoint | Status | Issue |
|----------|--------|-------|
| POST /customers | ✅ PASS | Working |
| GET /customers/{id} | ⚠️ PARTIAL | Returns null (format issue) |
| PUT /customers/{id} | ❌ FAIL | Route cache |
| POST /virtual-accounts | ✅ PASS | Working |
| PUT /virtual-accounts/{id} | ❌ FAIL | Route cache |
| GET /transactions | ✅ PASS | Working |
| POST /kyc/verify-bvn | ❌ FAIL | Wrong middleware |
| POST /kyc/verify-nin | ❌ FAIL | Wrong middleware |
| POST /kyc/verify-bank-account | ❌ FAIL | Wrong middleware |
| GET /kyc/status | ❌ FAIL | Wrong middleware |

## Quick Fix Steps:

1. **On Server:**
   ```bash
   ssh to server
   cd /home/aboksdfs/app.pointwave.ng
   php artisan route:clear
   php artisan config:clear
   php artisan cache:clear
   ```

2. **Update routes/api.php:**
   - Change KYC middleware from `auth.token` to `MerchantAuth`

3. **Push to GitHub and deploy:**
   ```bash
   git add routes/api.php
   git commit -m "Fix: Use MerchantAuth middleware for V1 KYC endpoints"
   git push origin main
   ```

4. **On server:**
   ```bash
   git pull origin main
   composer dump-autoload
   php artisan route:clear
   php artisan config:clear
   php artisan cache:clear
   ```

5. **Re-run tests:**
   ```bash
   php test_all_api_v1_endpoints.php
   ```

## Expected After Fix:

All 11 tests should PASS:
- ✅ Create Customer
- ✅ Get Customer
- ✅ Update Customer
- ✅ Create Virtual Account
- ✅ Update Virtual Account
- ✅ Get Transactions
- ✅ Verify BVN
- ✅ Verify NIN
- ✅ Verify Bank Account
- ✅ Get KYC Status
- ✅ Cleanup
