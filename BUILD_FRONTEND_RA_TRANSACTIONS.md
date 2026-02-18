# Build and Deploy RA Transactions Frontend

## Quick Deploy Instructions

### On Your Local Machine or Server

```bash
# Navigate to frontend directory
cd frontend

# Install dependencies (if needed)
npm install

# Build the frontend
npm run build

# The build folder will be created at: frontend/build/
```

### Upload to Production Server

Upload the entire `build` folder contents to:
```
/home/aboksdfs/app.pointwave.ng/public/
```

You can use:
- FTP/SFTP client (FileZilla, WinSCP, etc.)
- SCP command:
  ```bash
  scp -r frontend/build/* user@server:/home/aboksdfs/app.pointwave.ng/public/
  ```
- cPanel File Manager
- rsync:
  ```bash
  rsync -avz frontend/build/ user@server:/home/aboksdfs/app.pointwave.ng/public/
  ```

## What Changed

### New Files
- `frontend/src/pages/dashboard/RATransactionDetails.js` - Full page transaction details

### Updated Files
- `frontend/src/pages/dashboard/RATransactions.js` - View icon now navigates to details page
- `frontend/src/routes/index.js` - Added route for transaction details page

## Features Added

1. **Full Page Details** - Click view icon opens full page instead of modal
2. **Green Status** - All successful transactions show green badges
3. **Complete Sender Info** - Shows sender name, account number, and bank
4. **Professional Actions** - Refund and notification buttons with proper error handling
5. **Status Messages** - Helpful messages about transaction eligibility

## Testing After Deploy

1. Clear browser cache (Ctrl+Shift+Delete)
2. Login to company dashboard
3. Go to RA Transactions page
4. Click eye icon on any transaction
5. Verify:
   - Opens full page (not modal)
   - Shows sender details (not "N/A")
   - Status is green for successful
   - Buttons work without errors

## Troubleshooting

### Build Errors
```bash
# Clear node modules and reinstall
rm -rf node_modules package-lock.json
npm install
npm run build
```

### Page Not Loading
- Clear browser cache
- Check if build folder uploaded correctly
- Check browser console for errors
- Verify route is registered in routes/index.js

### Details Not Showing
- Check if transaction has metadata
- Check backend API response
- Check browser console for errors
- Verify transaction ID is correct

### Buttons Not Working
- Check if migration ran on server
- Check Laravel logs for errors
- Verify API endpoints are accessible
- Check authentication token is valid

## Backend Requirements

Make sure these are done on production server:

```bash
# Pull latest code
git pull origin main

# Run migration
php artisan migrate

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

## Files to Check

If something doesn't work, check these files:

### Frontend
- `frontend/src/pages/dashboard/RATransactions.js`
- `frontend/src/pages/dashboard/RATransactionDetails.js`
- `frontend/src/routes/index.js`

### Backend
- `app/Http/Controllers/API/TransactionController.php`
- `app/Http/Controllers/API/Trans.php`
- `routes/api.php`
- `database/migrations/2026_02_18_180000_add_refund_columns_to_transactions.php`

## Support

If you encounter issues:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Check browser console for JavaScript errors
3. Verify API endpoints return correct data
4. Check database for transaction metadata
5. Verify webhook URL is configured (for notifications)
