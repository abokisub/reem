# React Cache Busting Solution - Fix "Old Page After Refresh"

## Problem
After deploying a new React build, users see the old version until they clear their browser cache (Ctrl+Shift+Delete).

## Root Cause
Browsers cache JavaScript and CSS files aggressively. Without proper cache-busting, they serve old files even after deployment.

---

## ✅ Complete Solution (3 Steps)

### Step 1: Update `.htaccess` File

Add these headers to your `.htaccess` file in the `public` folder (or wherever your React build is deployed):

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On

    # --- CACHE BUSTING FOR REACT ---
    # Force browsers to check for new versions
    <FilesMatch "\.(html|htm)$">
        FileETag None
        <IfModule mod_headers.c>
            Header unset ETag
            Header set Cache-Control "max-age=0, no-cache, no-store, must-revalidate"
            Header set Pragma "no-cache"
            Header set Expires "Wed, 11 Jan 1984 05:00:00 GMT"
        </IfModule>
    </FilesMatch>

    # Cache JS/CSS files but with versioning (React handles this)
    <FilesMatch "\.(js|css)$">
        <IfModule mod_headers.c>
            Header set Cache-Control "public, max-age=31536000, immutable"
        </IfModule>
    </FilesMatch>

    # Cache images for 1 year
    <FilesMatch "\.(jpg|jpeg|png|gif|ico|svg|webp)$">
        <IfModule mod_headers.c>
            Header set Cache-Control "public, max-age=31536000"
        </IfModule>
    </FilesMatch>

    # React Router - Send all requests to index.html
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.html [L]
</IfModule>
```

### Step 2: Enable Build Hashing (Already Done by Create React App)

Create React App automatically adds hashes to filenames:
- `main.abc123.js` (changes with each build)
- `main.def456.css` (changes with each build)

This is already working if you're using `react-scripts build`.

### Step 3: Add Service Worker Unregister (Important!)

If you have an old service worker, it might cache everything. Add this to your `public/index.html`:

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Your App</title>
</head>
<body>
    <div id="root"></div>
    
    <!-- Unregister old service workers -->
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistrations().then(function(registrations) {
                for(let registration of registrations) {
                    registration.unregister();
                }
            });
        }
    </script>
</body>
</html>
```

---

## 🚀 Deployment Process

### Every Time You Deploy:

1. **Build React App**
   ```bash
   cd frontend
   npm run build
   ```

2. **Clear Server Cache** (Important!)
   ```bash
   # On your server, run these commands:
   
   # Clear OPcache (if using PHP)
   curl http://yoursite.com/clear-opcache.php
   
   # Or restart PHP-FPM
   sudo systemctl restart php-fpm
   
   # Clear browser cache headers
   sudo systemctl restart apache2
   # OR
   sudo systemctl restart nginx
   ```

3. **Upload Build Files**
   ```bash
   # Upload the entire build folder
   # Make sure to overwrite ALL files
   rsync -avz --delete build/ user@server:/path/to/public/
   ```

4. **Verify Deployment**
   - Open browser in Incognito mode
   - Visit your site
   - Check if new version is showing
   - Check browser console for any errors

---

## 🔧 Troubleshooting

### Issue 1: Still Seeing Old Version

**Solution**: Check if files are actually updated on server
```bash
# SSH into server
cd /path/to/public/static/js

# Check file dates
ls -lah

# Files should have today's date
# If not, upload didn't work properly
```

### Issue 2: Blank Page After Refresh

**Problem**: React Router not configured properly

**Solution**: Make sure `.htaccess` has this:
```apache
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.html [L]
```

### Issue 3: Users Still See Old Version

**Problem**: Their browser cached the old `index.html`

**Solution**: 
1. Tell users to hard refresh: `Ctrl + Shift + R` (Windows) or `Cmd + Shift + R` (Mac)
2. Or clear browser cache: `Ctrl + Shift + Delete`
3. Or use Incognito mode to test

### Issue 4: Service Worker Caching Everything

**Problem**: Old service worker is caching files

**Solution**: Add the unregister script from Step 3 above

---

## 📋 Quick Checklist

Before deploying:
- [ ] Run `npm run build`
- [ ] Check `.htaccess` has cache-busting headers
- [ ] Upload ALL files (don't skip any)
- [ ] Clear server cache (OPcache, Apache/Nginx)
- [ ] Test in Incognito mode
- [ ] Check browser console for errors

After deploying:
- [ ] Hard refresh browser (Ctrl+Shift+R)
- [ ] Check version number (if you have one)
- [ ] Test all pages work
- [ ] Check API calls work

---

## 🎯 Best Practices

### 1. Add Version Number to Your App

In `src/App.js` or footer:
```javascript
const APP_VERSION = "2.1.0"; // Update this with each deployment

function Footer() {
  return (
    <div>
      Version {APP_VERSION}
    </div>
  );
}
```

### 2. Use Build Script with Timestamp

Create `build-with-timestamp.sh`:
```bash
#!/bin/bash
echo "Building React app..."
npm run build

echo "Build completed at: $(date)" > build/BUILD_INFO.txt
echo "Version: $(git rev-parse --short HEAD)" >> build/BUILD_INFO.txt
```

### 3. Automated Deployment Script

Create `deploy.sh`:
```bash
#!/bin/bash

# Build
echo "Building..."
cd frontend
npm run build

# Upload
echo "Uploading..."
rsync -avz --delete build/ user@server:/path/to/public/

# Clear cache
echo "Clearing cache..."
ssh user@server "curl http://yoursite.com/clear-opcache.php"
ssh user@server "sudo systemctl restart php-fpm"

echo "Deployment complete!"
```

---

## 🔍 How to Verify It's Working

### Test 1: Check File Hashes
After building, check `build/static/js/`:
- Files should have hashes: `main.abc123.js`
- Hash should change with each build

### Test 2: Check Headers
```bash
curl -I https://yoursite.com/index.html

# Should see:
# Cache-Control: max-age=0, no-cache, no-store, must-revalidate
```

### Test 3: Check Network Tab
1. Open browser DevTools (F12)
2. Go to Network tab
3. Refresh page
4. Check if JS files have hashes in names
5. Check if `index.html` is not cached (Status: 200, not 304)

---

## 📞 For Your Friend

Tell them to:

1. **Add the `.htaccess` code above** to their public folder
2. **Always clear server cache** after deployment
3. **Use Incognito mode** to test new deployments
4. **Tell users to hard refresh** (Ctrl+Shift+R) after updates

The key is:
- ✅ HTML files: Never cache
- ✅ JS/CSS files: Cache forever (but with hashes that change)
- ✅ Clear server cache after each deployment

---

## 🎓 Why This Works

1. **HTML never cached**: Browser always fetches fresh `index.html`
2. **JS/CSS with hashes**: Each build creates new filenames
3. **Old files ignored**: Browser sees new hash, downloads new file
4. **No manual cache clearing**: Users get updates automatically

---

**Last Updated**: 2026-02-22
**Status**: ✅ Production-Ready Solution
