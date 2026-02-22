# Frontend Build & Deployment Instructions

## Issue Fixed
The React frontend was NOT reading `webhook_secret` and `test_webhook_secret` from the API response.

## File Changed
`frontend/src/pages/dashboard/DeveloperAPI.js`

## What Was Changed

In the `fetchCredentials` function, added two missing fields:

```javascript
// BEFORE (missing webhook secrets):
setCredentials({
    ...credentials,
    business_id: data.business_id,
    api_key: data.api_key,
    secret_key: data.secret_key,
    public_key: data.public_key,
    webhook_url: data.webhook_url,
    test_api_key: data.test_api_key,
    test_secret_key: data.test_secret_key,
    test_webhook_url: data.test_webhook_url
});

// AFTER (with webhook secrets):
setCredentials({
    ...credentials,
    business_id: data.business_id,
    api_key: data.api_key,
    secret_key: data.secret_key,
    public_key: data.public_key,
    webhook_url: data.webhook_url,
    webhook_secret: data.webhook_secret,           // ← ADDED
    test_api_key: data.test_api_key,
    test_secret_key: data.test_secret_key,
    test_webhook_url: data.test_webhook_url,
    test_webhook_secret: data.test_webhook_secret  // ← ADDED
});
```

## Build & Deploy Steps

### Step 1: Navigate to Frontend Directory
```bash
cd /path/to/your/project/frontend
```

### Step 2: Install Dependencies (if needed)
```bash
npm install
# or
yarn install
```

### Step 3: Build for Production
```bash
npm run build
# or
yarn build
```

This will create a `build` folder with optimized production files.

### Step 4: Upload to Server

#### Option A: Using SCP (from your local machine)
```bash
# Backup existing build first
ssh aboksdfs@server350 "cd /home/aboksdfs/app.pointwave.ng && mv public/dashboard public/dashboard.backup.$(date +%Y%m%d_%H%M%S)"

# Upload new build
scp -r build/* aboksdfs@server350:/home/aboksdfs/app.pointwave.ng/public/dashboard/
```

#### Option B: Using SFTP
1. Connect to server via SFTP
2. Navigate to `/home/aboksdfs/app.pointwave.ng/public/dashboard/`
3. Backup existing files (rename folder to `dashboard.backup`)
4. Upload contents of `build` folder to `dashboard` folder

#### Option C: Using rsync (recommended)
```bash
# Sync build folder to server
rsync -avz --delete build/ aboksdfs@server350:/home/aboksdfs/app.pointwave.ng/public/dashboard/
```

### Step 5: Verify Deployment

1. Clear browser cache (Ctrl+Shift+Delete)
2. Log in to https://app.pointwave.ng
3. Go to Developer API page
4. Check if webhook secrets are now visible

## Quick Reference

### Build Output Location
- Local: `frontend/build/`
- Server: `/home/aboksdfs/app.pointwave.ng/public/dashboard/`

### Files to Upload
Upload ALL files from `frontend/build/` to server's `public/dashboard/` directory.

### Important Notes

1. **Backup First**: Always backup existing build before uploading new one
2. **Clear Cache**: Users need to clear browser cache to see changes
3. **Check Permissions**: Ensure uploaded files have correct permissions (644 for files, 755 for directories)

### Set Correct Permissions (on server)
```bash
cd /home/aboksdfs/app.pointwave.ng/public/dashboard
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
```

## Verification

After deployment, the Developer API page should show:
- ✅ Webhook Secret (Live): `whsec_aad2319b0eb134b2f598ad63d4c2a7a2a4b054f42781b6599b92a7cc1a97fc68`
- ✅ Webhook Secret (Test): `whsec_test_6104274c5192e923ddf4cb697e32810aa30d194fd3a4386448a0a35f3e6cd2f9`

## Troubleshooting

### Issue: Still showing empty after deployment
**Solution**: Clear browser cache completely (Ctrl+Shift+Delete, select "All time")

### Issue: Build fails
**Solution**: 
```bash
rm -rf node_modules package-lock.json
npm install
npm run build
```

### Issue: Permission denied when uploading
**Solution**: Check SSH key or password, ensure you have write permissions

### Issue: Old version still showing
**Solution**: 
1. Check if files were actually uploaded
2. Clear browser cache
3. Check server file timestamps: `ls -la /home/aboksdfs/app.pointwave.ng/public/dashboard/`

## Alternative: Build on Server

If you prefer to build directly on the server:

```bash
# SSH to server
ssh aboksdfs@server350

# Navigate to project
cd /home/aboksdfs/app.pointwave.ng/frontend

# Pull latest changes (if frontend is in git)
git pull origin main

# Install dependencies
npm install

# Build
npm run build

# Copy to public directory
rm -rf ../public/dashboard/*
cp -r build/* ../public/dashboard/
```

## Summary

The fix is simple: the frontend was not reading webhook secrets from the API response. After building and deploying the updated frontend, webhook secrets will display correctly on the Developer API page.
