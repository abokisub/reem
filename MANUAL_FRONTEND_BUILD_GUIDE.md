# 📦 MANUAL FRONTEND BUILD & UPLOAD GUIDE

## 🎯 PROBLEM
Browser is showing old cached React dashboard instead of the new payment gateway dashboard.

## ✅ SOLUTION
Build the frontend locally and upload to production server manually.

---

## 📋 STEP-BY-STEP INSTRUCTIONS

### STEP 1: Build Frontend Locally (On Your Computer)

Open terminal/command prompt in your project folder and run:

```bash
cd frontend
npm run build
```

**Wait for build to complete.** You should see:
```
Creating an optimized production build...
Compiled successfully!

File sizes after gzip:
  ...
The build folder is ready to be deployed.
```

This creates a `frontend/build` folder with all the compiled files.

---

### STEP 2: Prepare Files for Upload

After build completes, you'll have these files in `frontend/build/`:
- `index.html`
- `asset-manifest.json`
- `manifest.json`
- `favicon.ico`
- `static/` folder (contains CSS, JS files)

---

### STEP 3: Upload to Production Server

#### Option A: Using cPanel File Manager

1. **Login to cPanel** at your hosting provider
2. **Open File Manager**
3. **Navigate to:** `/home/aboksdfs/public_html/public/`
4. **Delete old files:**
   - Delete `index.html` (if exists in public folder)
   - Delete `static` folder (if exists in public folder)
   - Delete `asset-manifest.json` (if exists)
   - Delete `manifest.json` (if exists)
   
5. **Upload new files:**
   - Upload `frontend/build/index.html` to `public/`
   - Upload `frontend/build/asset-manifest.json` to `public/`
   - Upload `frontend/build/manifest.json` to `public/`
   - Upload entire `frontend/build/static/` folder to `public/static/`

#### Option B: Using FTP/SFTP (FileZilla, WinSCP, etc.)

1. **Connect to your server:**
   - Host: `66.29.153.81` or `app.pointwave.ng`
   - Username: `aboksdfs`
   - Password: Your cPanel password
   - Port: 21 (FTP) or 22 (SFTP)

2. **Navigate to:** `/home/aboksdfs/public_html/public/`

3. **Delete old files:**
   - Delete `index.html` (if exists in public folder)
   - Delete `static` folder (if exists in public folder)
   - Delete `asset-manifest.json` (if exists)
   - Delete `manifest.json` (if exists)

4. **Upload new files:**
   - Upload `frontend/build/index.html` to `public/`
   - Upload `frontend/build/asset-manifest.json` to `public/`
   - Upload `frontend/build/manifest.json` to `public/`
   - Upload entire `frontend/build/static/` folder to `public/static/`

#### Option C: Using SSH/Terminal (Recommended if you have SSH access)

```bash
# Connect to server
ssh aboksdfs@66.29.153.81

# Navigate to project
cd app.pointwave.ng

# Remove old build files from public folder
rm -f public/index.html
rm -f public/asset-manifest.json
rm -f public/manifest.json
rm -rf public/static

# Copy new build files
cp frontend/build/index.html public/
cp frontend/build/asset-manifest.json public/
cp frontend/build/manifest.json public/
cp -r frontend/build/static public/

# Set correct permissions
chmod 644 public/index.html
chmod 644 public/asset-manifest.json
chmod 644 public/manifest.json
chmod -R 755 public/static
```

---

### STEP 4: Clear Server Caches

After uploading, run these commands on the server (via SSH or cPanel Terminal):

```bash
cd /home/aboksdfs/public_html

php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

---

### STEP 5: Clear Browser Cache

Now clear your browser cache:

#### Chrome/Edge:
1. Press `Ctrl + Shift + Delete` (Windows) or `Cmd + Shift + Delete` (Mac)
2. Select "Cached images and files"
3. Click "Clear data"

#### Or do a Hard Refresh:
- Windows/Linux: `Ctrl + Shift + R`
- Mac: `Cmd + Shift + R`

#### Or use Incognito/Private Window:
- Open new incognito/private window
- Visit: https://app.pointwave.ng

---

## 🔍 VERIFY IT WORKS

1. Visit: https://app.pointwave.ng
2. Login with your credentials
3. You should see the **payment gateway dashboard** (not the old PointPay dashboard)
4. Try refreshing the page - it should stay on the payment gateway dashboard

---

## ❌ TROUBLESHOOTING

### Issue: Still seeing old dashboard after upload

**Solution 1: Check file locations**
Make sure files are in the correct location:
- `public/index.html` (NOT `frontend/build/index.html`)
- `public/static/` folder (NOT `frontend/build/static/`)

**Solution 2: Clear browser cache again**
- Try incognito/private window
- Try different browser
- Clear cache and hard refresh

**Solution 3: Check .htaccess**
Make sure `public/.htaccess` exists and has the correct content (it should already be there from git pull)

**Solution 4: Verify build completed**
Check that `frontend/build/` folder has:
- `index.html` file
- `static/js/` folder with .js files
- `static/css/` folder with .css files

---

## 📁 FILE STRUCTURE AFTER UPLOAD

Your production server should look like this:

```
/home/aboksdfs/public_html/
├── app/
├── bootstrap/
├── config/
├── database/
├── frontend/              (source code - not served to users)
│   ├── src/
│   ├── public/
│   └── build/            (built files - copy these to public/)
├── public/               (THIS IS WHAT USERS SEE)
│   ├── index.php         (Laravel entry point)
│   ├── .htaccess         (routing rules)
│   ├── index.html        (React app - COPY FROM frontend/build/)
│   ├── asset-manifest.json
│   ├── manifest.json
│   └── static/           (React assets - COPY FROM frontend/build/static/)
│       ├── css/
│       ├── js/
│       └── media/
├── routes/
├── storage/
└── vendor/
```

---

## 🎯 QUICK CHECKLIST

- [ ] Built frontend: `cd frontend && npm run build`
- [ ] Deleted old files from `public/` folder
- [ ] Uploaded `index.html` to `public/`
- [ ] Uploaded `asset-manifest.json` to `public/`
- [ ] Uploaded `manifest.json` to `public/`
- [ ] Uploaded `static/` folder to `public/static/`
- [ ] Ran `php artisan cache:clear` on server
- [ ] Ran `php artisan config:clear` on server
- [ ] Ran `php artisan route:clear` on server
- [ ] Cleared browser cache (Ctrl+Shift+Delete)
- [ ] Did hard refresh (Ctrl+Shift+R)
- [ ] Verified new dashboard loads
- [ ] Tested page refresh (should not show old dashboard)

---

## 💡 IMPORTANT NOTES

1. **Don't delete `index.php`** - This is Laravel's entry point, keep it!
2. **Don't delete `.htaccess`** - This handles routing, keep it!
3. **The `frontend/` folder** is source code - users never see this
4. **The `public/` folder** is what users see - this is where you upload build files
5. **Always build locally first** - Don't build on production server
6. **Clear caches after upload** - Both server and browser caches

---

## 🆘 NEED HELP?

If you still see the old dashboard after following all steps:

1. Take a screenshot of your `public/` folder structure
2. Run this command on server and send output:
   ```bash
   ls -la public/ | grep -E "index.html|static"
   ```
3. Check browser console for errors (F12 → Console tab)
4. Send me the errors

---

**Last Updated:** February 18, 2026  
**Version:** 2.0.0
