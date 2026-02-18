# Domain Configuration Fix - app.pointwave.ng

## Current Issue
Your domain `app.pointwave.ng` is showing a directory listing instead of your Laravel application.

**Problem:** Web server document root is pointing to `/home/habukhan/Documents/pointpay` instead of `/home/habukhan/Documents/pointpay/public`

## Solution

You need to update your web server configuration to point to the `public` directory.

### For cPanel/Hosting Control Panel

1. **Login to your hosting control panel**

2. **Find "Document Root" or "Web Root" settings**
   - Usually under "Domains" or "Addon Domains"

3. **Update the document root:**
   ```
   Current: /home/habukhan/Documents/pointpay
   Change to: /home/habukhan/Documents/pointpay/public
   ```

4. **Save changes**

### For Apache (If you have server access)

1. **Edit your Apache virtual host configuration:**
   ```bash
   sudo nano /etc/apache2/sites-available/app.pointwave.ng.conf
   ```

2. **Update DocumentRoot:**
   ```apache
   <VirtualHost *:80>
       ServerName app.pointwave.ng
       ServerAlias www.app.pointwave.ng
       
       # CHANGE THIS LINE
       DocumentRoot /home/habukhan/Documents/pointpay/public
       
       <Directory /home/habukhan/Documents/pointpay/public>
           Options -Indexes +FollowSymLinks
           AllowOverride All
           Require all granted
       </Directory>
       
       ErrorLog ${APACHE_LOG_DIR}/pointwave-error.log
       CustomLog ${APACHE_LOG_DIR}/pointwave-access.log combined
   </VirtualHost>
   ```

3. **Enable the site and restart Apache:**
   ```bash
   sudo a2ensite app.pointwave.ng.conf
   sudo systemctl restart apache2
   ```

### For Nginx (If using Nginx)

1. **Edit your Nginx configuration:**
   ```bash
   sudo nano /etc/nginx/sites-available/app.pointwave.ng
   ```

2. **Update root directive:**
   ```nginx
   server {
       listen 80;
       server_name app.pointwave.ng www.app.pointwave.ng;
       
       # CHANGE THIS LINE
       root /home/habukhan/Documents/pointpay/public;
       
       index index.php index.html;
       
       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }
       
       location ~ \.php$ {
           fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
           fastcgi_index index.php;
           fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
           include fastcgi_params;
       }
       
       location ~ /\.(?!well-known).* {
           deny all;
       }
   }
   ```

3. **Test and restart Nginx:**
   ```bash
   sudo nginx -t
   sudo systemctl restart nginx
   ```

## Alternative: Create Symlink (Quick Fix)

If you can't change the document root, create a symlink:

```bash
# Go to your project root
cd /home/habukhan/Documents/pointpay

# Create index.php that redirects to public
cat > index.php << 'EOF'
<?php
// Redirect to public directory
header('Location: /public/');
exit;
EOF
```

**Note:** This is NOT recommended for production. Always use proper document root configuration.

## Verify Configuration

After making changes, test:

1. **Visit:** https://app.pointwave.ng
2. **Expected:** Should show your Laravel application (login page)
3. **Not Expected:** Directory listing

## Security Note

**IMPORTANT:** Never expose your Laravel root directory to the web. Always point to the `public` folder to protect:
- `.env` file (contains secrets)
- `config/` directory
- `database/` directory
- `storage/` directory
- All other sensitive files

## Contact Your Hosting Provider

If you're using shared hosting and can't find these settings:

1. **Contact your hosting support**
2. **Tell them:** "Please change my document root for app.pointwave.ng to point to the 'public' subdirectory"
3. **Provide path:** `/home/habukhan/Documents/pointpay/public`

## After Fixing

Once the document root is corrected:

1. ✅ Visit https://app.pointwave.ng
2. ✅ You should see the login page
3. ✅ API endpoints will work: https://app.pointwave.ng/api/v1/...
4. ✅ Documentation will work: https://app.pointwave.ng/docs

## Common Hosting Providers

### Namecheap cPanel
1. Login to cPanel
2. Go to "Domains" → "Addon Domains"
3. Click "Manage" next to app.pointwave.ng
4. Change "Document Root" to: `public_html/pointpay/public`

### HostGator
1. Login to cPanel
2. Go to "Domains"
3. Click domain name
4. Update "Document Root" field

### Bluehost
1. Login to cPanel
2. Go to "Domains" → "Addon Domains"
3. Edit domain
4. Change root directory to include `/public`

## Current File Structure

Your project structure:
```
/home/habukhan/Documents/pointpay/
├── app/
├── config/
├── database/
├── public/          ← Web server should point HERE
│   ├── index.php    ← Laravel entry point
│   ├── .htaccess    ← Already configured correctly
│   └── ...
├── routes/
├── storage/
└── vendor/
```

## Status

- ✅ Laravel files are in place
- ✅ `.htaccess` is configured correctly
- ✅ `public/index.php` exists
- ❌ Web server document root needs to be updated

## Next Steps

1. Update document root to point to `public` folder
2. Restart web server
3. Test https://app.pointwave.ng
4. Verify login page loads
5. Test API endpoints

Once this is fixed, your application will work perfectly!
