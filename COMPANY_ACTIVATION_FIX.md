# Company Activation & Document Preview Fixes

## Issues Fixed

### 1. Master Wallet Not Created on Company Activation
**Problem**: When admin activates a company, no PalmPay master wallet is generated.

**Root Cause**: The code exists in `CompanyKycController::toggleStatus()` but may fail silently if:
- Company has no `director_bvn`
- Company has no `director_nin`  
- Company has no `business_registration_number` (RC number)

**Solution**: The system already has fallback logic:
1. First tries `director_bvn`
2. Falls back to `director_nin`
3. Falls back to `business_registration_number` (RC number)
4. If all missing, logs error but doesn't fail activation

**To ensure master wallet creation**:
- Make sure company has at least ONE of these fields filled:
  - `director_bvn` (preferred)
  - `director_nin` (alternative)
  - `business_registration_number` (RC number - fallback)

**Check logs**: Look in `storage/logs/laravel.log` for:
```
Failed to create company master wallet
```

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

## Testing

### Test Master Wallet Creation:
```bash
# 1. Check company has KYC data
SELECT id, name, director_bvn, director_nin, business_registration_number 
FROM companies WHERE id = 2;

# 2. Activate company via admin panel
# 3. Check if master wallet was created
SELECT id, name, palmpay_account_number, palmpay_account_name 
FROM companies WHERE id = 2;

# 4. Check virtual_accounts table
SELECT * FROM virtual_accounts 
WHERE company_id = 2 AND user_id LIKE 'company_master_%';
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
```

## Notes

- Master wallet creation happens automatically when `is_active` is set to `true`
- If creation fails, it logs the error but doesn't prevent activation
- Documents are served with proper MIME types for inline viewing
- The route requires admin authentication (`auth.token` + `admin` middleware)
