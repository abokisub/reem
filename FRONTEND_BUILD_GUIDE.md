# Frontend Build Guide - Transaction Normalization

## Overview

The backend has been updated to support transaction normalization. The existing RA Transactions frontend component should work without changes due to backward compatibility, but you may want to enhance it to use the new fields.

---

## Current Status

### Backend Changes (✅ Complete)
- RA Dashboard endpoint refactored (AllRATransactions method)
- Admin Dashboard endpoint created (AdminTransactionController)
- Backward compatibility maintained with legacy field mappings
- New fields available: transaction_ref, session_id, transaction_type, settlement_status, net_amount

### Frontend Status
- Existing RATransactions.js component should work without changes
- Can be enhanced to display new fields (optional)

---

## Option 1: Deploy Without Frontend Changes (Recommended for Quick Deploy)

The backend maintains backward compatibility, so you can deploy immediately:

```bash
# On server
cd app.pointwave.ng
bash COMPLETE_DEPLOYMENT_SCRIPT.sh
```

The existing frontend will continue to work with:
- Legacy field names (transid, date, details, charges, oldbal, newbal)
- All existing functionality preserved

---

## Option 2: Enhance Frontend to Use New Fields (Optional)

If you want to display the new normalized fields, update the frontend:

### New Fields Available from Backend

```javascript
// New fields in API response
{
  transaction_ref: "TXN123ABC456",     // Normalized reference
  session_id: "sess_uuid-here",        // Session tracking
  transaction_type: "va_deposit",      // Enum type
  settlement_status: "settled",        // Settlement state
  net_amount: 990.00,                  // Amount after fees
  
  // Legacy fields (still available)
  transid: "old-reference",
  date: "2024-02-21",
  details: "description",
  charges: 10.00,
  oldbal: 1000.00,
  newbal: 990.00
}
```

### Enhancement Steps

1. **Add new columns to RATransactions table** (optional)
2. **Add session_id filter** (optional)
3. **Display transaction_type labels** (optional)
4. **Show settlement_status** (optional)

---

## Frontend Build Process

### Step 1: Navigate to Frontend Directory

```bash
cd frontend
```

### Step 2: Install Dependencies (if needed)

```bash
npm install
```

### Step 3: Build for Production

```bash
npm run build
```

This creates optimized production files in `frontend/build/`

### Step 4: Deploy Build to Public Directory

```bash
# Copy build files to Laravel public directory
cp -r build/* ../public/

# Or use rsync for better control
rsync -av --delete build/ ../public/
```

### Step 5: Clear Browser Cache

After deployment, users may need to clear browser cache or do a hard refresh (Ctrl+F5)

---

## Testing Checklist

### Backend API Testing

```bash
# Test RA Dashboard endpoint
curl -X GET "https://app.pointwave.ng/api/transactions/ra-transactions" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"

# Expected: Only 4 customer-facing transaction types
# Should NOT see: fee_charge, kyc_charge, manual_adjustment
```

### Frontend Testing

1. Login to RA Dashboard
2. Navigate to Transactions page
3. Verify transactions display correctly
4. Test filters (status, search)
5. Test pagination
6. Verify no "N/A" values appear
7. Check that only customer-facing transactions show

---

## Rollback Procedure

If issues occur after deployment:

### Backend Rollback

```bash
# Revert to previous commit
git log --oneline | head -5
git revert <commit-hash>
git push origin main

# On server
git pull origin main
php artisan cache:clear
sudo systemctl restart php-fpm
```

### Frontend Rollback

```bash
# Restore previous build
cp -r ../public_backup/* ../public/
```

---

## Performance Optimization

### Backend
- ✅ Eager loading implemented (prevents N+1 queries)
- ✅ Indexed columns used (session_id, transaction_ref, created_at)
- ✅ Pagination enabled (50 per page)

### Frontend
- Consider implementing virtual scrolling for large lists
- Add debouncing to search inputs
- Cache API responses where appropriate

---

## Monitoring

### After Deployment, Monitor:

1. **API Response Times**
   - Target: p95 < 200ms
   - Check Laravel logs for slow queries

2. **Error Rates**
   - Watch for 500 errors in logs
   - Monitor failed API calls in browser console

3. **User Reports**
   - Check for missing data
   - Verify no N/A values displayed
   - Confirm filters work correctly

---

## Quick Deploy Commands

### Full Deployment (Backend + Frontend)

```bash
# On server
cd app.pointwave.ng

# Deploy backend
bash COMPLETE_DEPLOYMENT_SCRIPT.sh

# Build and deploy frontend
cd frontend
npm install
npm run build
cp -r build/* ../public/

# Verify
curl -I https://app.pointwave.ng
```

### Backend Only

```bash
cd app.pointwave.ng
bash COMPLETE_DEPLOYMENT_SCRIPT.sh
```

### Frontend Only

```bash
cd app.pointwave.ng/frontend
npm run build
cp -r build/* ../public/
```

---

## Troubleshooting

### Issue: Frontend shows old data

**Solution:**
```bash
# Clear browser cache
# Hard refresh: Ctrl+F5 (Windows) or Cmd+Shift+R (Mac)

# Or add cache busting to build
npm run build -- --no-cache
```

### Issue: API returns 404

**Solution:**
```bash
php artisan route:clear
php artisan cache:clear
php artisan config:cache
```

### Issue: Transactions not filtering correctly

**Solution:**
Check that Phase 1 migration has been run:
```bash
php artisan migrate:status | grep phase1
```

---

## Next Steps After Deployment

1. ✅ Backend deployed and tested
2. ✅ Frontend built and deployed
3. ⏳ Run Phase 2 migration (backfill historical data)
4. ⏳ Run Phase 3 migration (enforce constraints)
5. ⏳ Monitor for 24 hours
6. ⏳ Implement settlement integrity checker (Priority 5)

---

**Status:** Ready for deployment
**Backward Compatibility:** ✅ Maintained
**Breaking Changes:** ❌ None
**Recommended:** Deploy backend first, test, then optionally enhance frontend

