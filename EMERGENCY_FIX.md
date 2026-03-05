# EMERGENCY FIX - Site Down

## Problem
The frontend build is corrupted. The index.html file is only 828 bytes when it should be several KB.

## IMMEDIATE SOLUTION

### Option 1: Restore from Backup (FASTEST)
If you have a backup of the working site:
```bash
# On server
cd /home/aboksdfs/app.pointwave.ng
# Restore the frontend folder from your backup
```

### Option 2: Use Your Local Working Build (RECOMMENDED)
Since the site was working before on your local machine:

1. **On your LOCAL machine** (where it was working):
```bash
cd ~/Documents/pointwave/frontend
# Create a backup of the working build
tar -czf frontend-working.tar.gz index.html static/ asset-manifest.json manifest.json favicon/ icons/ fonts/ service-worker.js* robots.txt
```

2. **Upload to server** using cPanel File Manager or FTP:
   - Upload `frontend-working.tar.gz` to `/home/aboksdfs/app.pointwave.ng/`

3. **On server** via SSH or cPanel Terminal:
```bash
cd /home/aboksdfs/app.pointwave.ng
# Backup current broken build
mv frontend frontend-broken-backup

# Create new frontend directory
mkdir frontend

# Extract working build
tar -xzf frontend-working.tar.gz -C frontend/

# Set permissions
chmod -R 755 frontend/
chown -R aboksdfs:aboksdfs frontend/

# Clear Laravel cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### Option 3: Rebuild on Server with Increased Memory
```bash
cd /home/aboksdfs/app.pointwave.ng/frontend

# Increase Node.js memory limit
export NODE_OPTIONS="--max-old-space-size=4096"

# Clean everything
rm -rf node_modules build
npm cache clean --force

# Reinstall and build
npm install
npm run build

# If build succeeds, verify index.html size
ls -lh index.html
# Should be several KB, not 828 bytes
```

## Quick Check
After fixing, verify the site works:
```bash
# Check index.html size
ls -lh /home/aboksdfs/app.pointwave.ng/frontend/index.html

# Should show something like:
# -rw-r--r-- 1 aboksdfs aboksdfs 3.2K Feb 25 16:00 index.html
# NOT 828 bytes!
```

Then visit: https://app.pointwave.ng

## What Went Wrong
The npm build process was interrupted or failed, creating an incomplete index.html file. This causes the entire React app to fail to load.

## Prevention
Always verify build completed successfully:
```bash
npm run build
echo "Build exit code: $?"
ls -lh frontend/index.html
```

Exit code should be 0 and index.html should be several KB.
