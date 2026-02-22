# Company Activation & Document Preview Fixes

## Issues Fixed

### 1. Master Wallet Not Created on Company Activation
**Problem**: When admin activates a company, no PalmPay master wallet is generated.

**Root Cause**: The wallet creation was happening AFTER the status update, and the company object wasn't being refreshed properly.

**Solution**: 
1. **Fixed activation flow**: Moved master wallet creation BEFORE status update
2. **Added detailed logging**: Now logs KYC availability (BVN, NIN, RC number)
3. **Auto-creation on login**: When existing active companies login, system auto-creates missing master wallets
4. **Manual script**: Created `create_missing_master_wallets.php` to fix existing companies

**KYC Requirements** (at least ONE required):
- `director_bvn` (preferred)
- `director_nin` (alternative)
- `business_registration_number` (RC number - fallback)

### 2. Document Preview Shows Corrupt/Broken Images
**Problem**: When viewing company documents, images show as corrupt or broken.

**Root Cause**: Documents are stored in `storage/app/kyc_documents/` (private storage) but there was no route to serve them.

**Solution**: Added new route and controller method:
- Route: `GET /api/admin/documents/{documentId}/view`
- Controller: `DocumentController::view()`
- Returns the actual file with proper MIME type for inline viewing

**Usage**:
```javascript
// In frontend, use this URL to preview documents:
const documentUrl = `/api/admin/documents/${documentId}/view`;

// Example:
<img src={`/api/admin/documents/${doc.id}/view`} alt="Document" />
```

## Files Modified

1. **app/Http/Controllers/API/Admin/DocumentController.php**
   - Added `view()` method to serve documents

2. **routes/api.php**
   - Added route: `GET /api/admin/documents/{documentId}/view`

3. **app/Http/Controllers/Admin/CompanyKycController.php**
   - Fixed master wallet creation order (before status update)
   - Added detailed logging for debugging

4. **app/Http/Controllers/API/AuthController.php**
   - Added auto-creation of master wallets on login for existing companies

5. **create_missing_master_wallets.php** (NEW)
   - Script to create master wallets for existing active companies

## Testing

### Test Master Wallet Creation:
```bash
# 1. Check company has KYC data
SELECT id, name, director_bvn, director_nin, business_registration_number, 
       palmpay_account_number, is_active
FROM companies WHERE id = 2;

# 2. Activate company via admin panel
# 3. Check logs for master wallet creation
tail -f storage/logs/laravel.log | grep "master wallet"

# 4. Verify master wallet was created
SELECT id, name, palmpay_account_number, palmpay_account_name 
FROM companies WHERE id = 2;
```

### Create Master Wallets for Existing Companies:
```bash
# Run the script to create missing master wallets
php create_missing_master_wallets.php
```

### Test Document Preview:
```bash
# 1. Get a document ID
SELECT id, company_id, document_type, file_path, status 
FROM company_documents LIMIT 1;

# 2. Test the view endpoint
curl -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  https://app.pointwave.ng/api/admin/documents/1/view

# Should return the actual image/PDF file
```

## Deployment

```bash
cd /home/aboksdfs/app.pointwave.ng
git pull origin main
php artisan config:clear
php artisan cache:clear
php artisan route:clear
curl -s https://app.pointwave.ng/clear-opcache.php

# Create missing master wallets for existing companies
php create_missing_master_wallets.php
```

## Notes

- Master wallet creation now happens BEFORE status update to ensure proper flow
- Auto-creation on login ensures existing companies get master wallets
- Detailed logging helps debug any KYC issues
- Documents are served with proper MIME types for inline viewing
- The route requires admin authentication (`auth.token` + `admin` middleware)
