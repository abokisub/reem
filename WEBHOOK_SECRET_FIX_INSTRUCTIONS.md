# Webhook Secret Fix Instructions

## Problem
Webhook secrets are returning serialized PHP format (`s:70:"whsec_..."`) instead of clean values like `whsec_abc123...`

This happens because the values in the database were encrypted with a different APP_KEY or stored incorrectly.

## Solution Steps

### Step 1: Pull Latest Code
```bash
cd /home/aboksdfs/app.pointwave.ng
git pull origin main
```

### Step 2: Run Diagnostic (Optional but Recommended)
This will show you exactly what's wrong:
```bash
php diagnose_webhook_secrets.php
```

Expected output will show:
- Raw encrypted values from database
- Whether they're in serialized format
- Decryption test results

### Step 3: Run the Fix Script
This will re-encrypt all webhook secrets with the current APP_KEY:
```bash
php fix_webhook_secrets_encryption.php
```

The script will:
- Find all companies with webhook secrets
- Try to decrypt existing secrets
- If decryption fails, generate new secrets
- Re-encrypt everything with current APP_KEY
- Update the database

### Step 4: Clear Cache
```bash
php artisan config:clear
php artisan cache:clear
```

### Step 5: Test the API
```bash
php test_credentials_api.php
```

Expected output should show CLEAN webhook secrets:
```
✓ webhook_secret: whsec_aad2319b0eb134b2f598ad63d4c2a7a2a4b054f42781b6599b92a7cc1a97fc68
✓ test_webhook_secret: whsec_test_6104274c5192e923ddf4cb697e32810aa30d194fd3a4386448a0a35f3e6cd2f9
```

NOT serialized format like:
```
✗ webhook_secret: s:70:"whsec_..."
```

### Step 6: Test the Actual API Endpoint
Test via browser or Postman:
```
GET https://app.pointwave.ng/api/company/credentials
Authorization: Bearer {company_token}
```

The response should show clean webhook secrets.

### Step 7: Verify on Frontend
Log in to the Developer API page and check if webhook secrets are now visible.

## Quick One-Liner (All Steps)
```bash
cd /home/aboksdfs/app.pointwave.ng && \
git pull origin main && \
php fix_webhook_secrets_encryption.php && \
php artisan config:clear && \
php artisan cache:clear && \
php test_credentials_api.php
```

## If Fix Script Not Found
If you get "Could not open input file: fix_webhook_secrets_encryption.php", run:
```bash
git status
ls -la fix_webhook_secrets_encryption.php
```

If the file doesn't exist, it means git pull didn't work properly. Try:
```bash
git fetch origin
git reset --hard origin/main
```

## Important Notes

1. **New Secrets**: If the fix script generates new secrets (because old ones couldn't be decrypted), you'll need to update Kobopoint's webhook configuration with the new secrets.

2. **No Downtime**: This fix doesn't affect transaction processing. Transactions will continue to work normally.

3. **Webhook Delivery**: After fixing, webhooks will be delivered with proper signatures that companies can verify.

4. **Security**: The new encryption uses your current APP_KEY from .env file. Make sure this key is secure and backed up.

## Troubleshooting

### Issue: "The payload is invalid" error
This means the encrypted value can't be decrypted with current APP_KEY. The fix script will generate a new secret.

### Issue: Still showing serialized format after fix
1. Make sure you cleared cache: `php artisan config:clear && php artisan cache:clear`
2. Check if the fix script actually ran successfully
3. Run diagnostic again: `php diagnose_webhook_secrets.php`

### Issue: Frontend still not showing secrets
1. Check browser console for JavaScript errors
2. Verify API endpoint returns correct data: `curl -H "Authorization: Bearer {token}" https://app.pointwave.ng/api/company/credentials`
3. Check if frontend is caching the old response

## What This Fix Does

The fix script:
1. Reads all companies from database
2. For each company:
   - Tries to decrypt webhook_secret
   - If successful: Re-encrypts with current APP_KEY
   - If fails: Generates new secret and encrypts it
   - Same for test_webhook_secret
3. Updates database with properly encrypted values

After this fix:
- `decrypt()` function will return clean strings
- API will return clean webhook secrets
- Companies can use these secrets to verify webhook signatures
- No more serialized format issues
