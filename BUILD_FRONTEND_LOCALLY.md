# Build Frontend Locally and Deploy to Server

## Current Situation

✅ All backend migrations completed on server
✅ All frontend code pushed to GitHub
❌ Server doesn't have npm installed
❌ Frontend build failed on server

## Solution: Build Locally, Deploy to Server

Since the server doesn't have npm, we'll build the frontend on your local machine and copy the build files to the server.

---

## Step 1: Build Frontend Locally

On your local machine (where you have npm installed):

```bash
cd ~/Documents/pointpay/frontend

# Install dependencies (if needed)
npm install

# Build the frontend
npm run build
```

This will create a `frontend/build` directory with all the compiled files.

---

## Step 2: Copy Build to Server

### Option A: Using SCP (Recommended)

```bash
# From your local machine
cd ~/Documents/pointpay

# Copy the entire build directory to server
scp -r frontend/build/* aboksdfs@server350.web-hosting.com:~/app.pointwave.ng/public/
```

### Option B: Using rsync (Better for large files)

```bash
# From your local machine
cd ~/Documents/pointpay

# Sync build directory to server
rsync -avz --delete frontend/build/ aboksdfs@server350.web-hosting.com:~/app.pointwave.ng/public/
```

### Option C: Manual Upload via FTP/SFTP

1. Build frontend locally: `cd frontend && npm run build`
2. Use FileZilla or similar FTP client
3. Connect to server350.web-hosting.com
4. Navigate to `app.pointwave.ng/public/`
5. Upload all files from `frontend/build/` to `public/`

---

## Step 3: Clear Caches on Server

After copying the build files, SSH to the server and run:

```bash
cd app.pointwave.ng

# Clear all Laravel caches
php artisan cache:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear

# Restart PHP-FPM (if you have sudo access)
sudo systemctl restart php-fpm
```

If you don't have sudo access, the cache clear commands should be enough.

---

## Step 4: Test in Browser

1. Login to company dashboard at https://app.pointwave.ng
2. Go to RA Transactions page
3. Verify new columns display:
   - Transaction Ref
   - Session ID
   - Transaction Type (with labels)
   - Net Amount
   - Settlement Status (no N/A)
4. Go to Wallet Summary page
5. Verify same columns display
6. Login to admin dashboard
7. Go to Statement page
8. Verify all 7 transaction types display

---

## Alternative: Build on Server with Node Version Manager

If you want to install npm on the server (optional):

```bash
# On the server
cd ~

# Install nvm (Node Version Manager)
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash

# Reload shell
source ~/.bashrc

# Install Node.js
nvm install 18
nvm use 18

# Verify installation
node --version
npm --version

# Now you can build on the server
cd app.pointwave.ng/frontend
npm install
npm run build

# Copy build to public
cp -r build/* ../public/
```

---

## Quick Commands Summary

### On Local Machine:
```bash
cd ~/Documents/pointpay/frontend
npm run build
scp -r build/* aboksdfs@server350.web-hosting.com:~/app.pointwave.ng/public/
```

### On Server:
```bash
cd app.pointwave.ng
php artisan cache:clear
php artisan view:clear
php artisan config:clear
```

---

## What's Complete

✅ Backend migrations (all 4 phases)
✅ Phase 1: Columns added
✅ Phase 2: Data backfilled
✅ Phase 3: Marked as migrated (constraints skipped)
✅ Phase 4: Status logs table created
✅ Frontend code updated (all 3 components)
✅ All changes pushed to GitHub

⏳ Frontend build needs to be deployed
⏳ Browser testing needed

---

## Files Updated

### Frontend Components (3 files):
1. `frontend/src/pages/dashboard/RATransactions.js` - 11 columns
2. `frontend/src/pages/admin/AdminStatement.js` - 12 columns
3. `frontend/src/pages/dashboard/wallet-summary.js` - 10 columns

### New Columns Displayed:
- Transaction Ref (with copy button)
- Session ID (with copy button)
- Transaction Type (human-readable labels)
- Fee (charges)
- Net Amount (amount after fees)
- Settlement Status (no N/A values)

---

## Need Help?

If you encounter issues:
1. Check `storage/logs/laravel.log` on server
2. Check browser console (F12) for frontend errors
3. Verify build files exist in `public/` directory
4. Clear browser cache (Ctrl+Shift+R)

**The system is ready - just build locally and copy to server!**
