# Fix Documentation Pages - Deployment Guide

## Problem
- `/docs` routes work (old Laravel blade templates)
- `/documentation` routes show 500 error
- Need to deploy the new React-based documentation pages

## Solution: Rebuild and Deploy Frontend

### Step 1: SSH into Your Server
```bash
ssh aboksdfs@server350
cd app.pointwave.ng
```

### Step 2: Pull Latest Code (Already Done)
```bash
git pull origin main
```

### Step 3: Rebuild the React Frontend
```bash
cd frontend
npm install
npm run build
```

This will:
- Install any new dependencies
- Build the React app with all new documentation pages
- Generate updated `index.html` and static assets

### Step 4: Clear Laravel Cache
```bash
cd ..
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### Step 5: Set Proper Permissions
```bash
chmod -R 755 frontend/
chown -R aboksdfs:aboksdfs frontend/
```

### Step 6: Test the Documentation
Visit these URLs to verify:
- https://app.pointwave.ng/documentation/home ✅
- https://app.pointwave.ng/documentation/quick-start ✅
- https://app.pointwave.ng/documentation/authentication ✅
- https://app.pointwave.ng/documentation/webhooks ✅
- https://app.pointwave.ng/documentation/pagination ✅

## What Changed

The new documentation system uses:
- React components (not Laravel blade templates)
- Modern UI with code examples in PHP, Node.js, and Python
- Better navigation and search
- Responsive design
- Can be accessed by both logged-in users and public visitors

## Old vs New Routes

### Old (Still Works)
- `/docs` - Old Laravel blade documentation
- `/docs/authentication`
- `/docs/customers`
- etc.

### New (After Build)
- `/documentation/home` - New React documentation
- `/documentation/quick-start`
- `/documentation/authentication`
- `/documentation/webhooks`
- `/documentation/pagination`
- etc.

## If Build Fails

If `npm run build` fails, check:

1. **Node.js Version**
```bash
node --version
# Should be v14 or higher
```

2. **Disk Space**
```bash
df -h
# Make sure you have at least 1GB free
```

3. **Memory Issues**
If build fails with "JavaScript heap out of memory":
```bash
export NODE_OPTIONS="--max-old-space-size=4096"
npm run build
```

4. **Check Build Errors**
```bash
npm run build 2>&1 | tee build.log
cat build.log
```

## Quick Command Summary

Run these commands in order:
```bash
cd /home/aboksdfs/app.pointwave.ng/frontend
npm install
npm run build
cd ..
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

Then test: https://app.pointwave.ng/documentation/home

## Note About /docs vs /documentation

- `/docs` - Old system (Laravel blade templates) - Still accessible
- `/documentation` - New system (React components) - Modern, better UX

You can keep both or redirect `/docs` to `/documentation` later if you want.
