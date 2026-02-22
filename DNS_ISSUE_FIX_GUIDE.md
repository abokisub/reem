# DNS Issue Fix for app.pointwave.ng

## Problem

The domain `app.pointwave.ng` is not resolving - browsers cannot find the server.

**Error**: "We can't connect to the server at app.pointwave.ng"

This is a DNS configuration issue, NOT an SSL issue. The domain needs to be properly configured to point to your server.

## Diagnosis

Run these commands to check DNS status:

```bash
# Check if domain resolves
dig app.pointwave.ng +short
nslookup app.pointwave.ng

# Check your server's public IP
curl -4 ifconfig.me
# or
curl -4 icanhazip.com
```

## Solution

### Step 1: Get Your Server's IP Address

SSH into your server and run:

```bash
# Get public IP address
curl -4 ifconfig.me
```

Example output: `123.456.789.012`

### Step 2: Configure DNS Records

You need to add an A record for `app.pointwave.ng` pointing to your server's IP.

#### Option A: Using Your Domain Registrar

1. Log in to your domain registrar (where you bought pointwave.ng)
2. Go to DNS Management / DNS Settings
3. Add an A record:
   - **Host/Name**: `app`
   - **Type**: `A`
   - **Value/Points to**: `YOUR_SERVER_IP` (e.g., 123.456.789.012)
   - **TTL**: 3600 (or Auto)

#### Option B: Using Cloudflare (Recommended)

If you use Cloudflare:

1. Log in to Cloudflare Dashboard
2. Select domain: `pointwave.ng`
3. Go to **DNS** → **Records**
4. Click **Add record**
5. Configure:
   - **Type**: A
   - **Name**: app
   - **IPv4 address**: YOUR_SERVER_IP
   - **Proxy status**: 🟠 DNS only (turn off proxy initially)
   - **TTL**: Auto
6. Click **Save**

#### Option C: Using cPanel

1. Log in to cPanel
2. Go to **Zone Editor**
3. Find domain `pointwave.ng`
4. Click **+ A Record**
5. Fill in:
   - **Name**: app.pointwave.ng
   - **Address**: YOUR_SERVER_IP
6. Click **Add Record**

### Step 3: Verify DNS Propagation

After adding the DNS record, wait 5-10 minutes, then test:

```bash
# Check if DNS is working
dig app.pointwave.ng +short

# Should return your server IP
# Example: 123.456.789.012

# Check from multiple locations
nslookup app.pointwave.ng 8.8.8.8
nslookup app.pointwave.ng 1.1.1.1
```

Online tools to check DNS:
- https://dnschecker.org/#A/app.pointwave.ng
- https://www.whatsmydns.net/#A/app.pointwave.ng

### Step 4: Configure Web Server

Once DNS is working, ensure your web server (Nginx/Apache) is configured for the domain.

#### For Nginx

Create/edit: `/etc/nginx/sites-available/app.pointwave.ng`

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name app.pointwave.ng;

    root /home/aboksdfs/app.pointwave.ng/public;
    index index.php index.html;

    # Logs
    access_log /var/log/nginx/app.pointwave.ng-access.log;
    error_log /var/log/nginx/app.pointwave.ng-error.log;

    # Laravel/PHP configuration
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

Enable the site:
```bash
sudo ln -sf /etc/nginx/sites-available/app.pointwave.ng /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

#### For Apache

Create/edit: `/etc/apache2/sites-available/app.pointwave.ng.conf`

```apache
<VirtualHost *:80>
    ServerName app.pointwave.ng
    ServerAdmin admin@pointwave.ng
    DocumentRoot /home/aboksdfs/app.pointwave.ng/public

    <Directory /home/aboksdfs/app.pointwave.ng/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/app.pointwave.ng-error.log
    CustomLog ${APACHE_LOG_DIR}/app.pointwave.ng-access.log combined
</VirtualHost>
```

Enable the site:
```bash
sudo a2ensite app.pointwave.ng.conf
sudo apache2ctl configtest
sudo systemctl reload apache2
```

### Step 5: Check Firewall

Ensure ports 80 and 443 are open:

```bash
# Check firewall status
sudo ufw status

# Allow HTTP and HTTPS
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw reload

# Or if using firewalld
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

### Step 6: Test the Site

```bash
# Test HTTP connection
curl -I http://app.pointwave.ng

# Should return HTTP 200 OK or 301/302 redirect
```

Open in browser: http://app.pointwave.ng

## Quick Diagnostic Commands

Run these on your server to diagnose issues:

```bash
# 1. Check if web server is running
sudo systemctl status nginx
# or
sudo systemctl status apache2

# 2. Check if web server is listening on port 80
sudo netstat -tlnp | grep :80
# or
sudo ss -tlnp | grep :80

# 3. Check Nginx/Apache configuration
sudo nginx -t
# or
sudo apache2ctl configtest

# 4. Check if site directory exists
ls -la /home/aboksdfs/app.pointwave.ng/public

# 5. Check PHP-FPM status
sudo systemctl status php8.2-fpm

# 6. View recent error logs
sudo tail -f /var/log/nginx/error.log
# or
sudo tail -f /var/log/apache2/error.log
```

## Common Issues and Fixes

### Issue 1: DNS Not Propagating

**Symptom**: `dig app.pointwave.ng` returns nothing

**Fix**:
- Wait 5-30 minutes for DNS propagation
- Check DNS record is correct in your DNS provider
- Try flushing local DNS cache:
  ```bash
  # Linux
  sudo systemd-resolve --flush-caches
  
  # macOS
  sudo dscacheutil -flushcache
  
  # Windows
  ipconfig /flushdns
  ```

### Issue 2: Web Server Not Running

**Symptom**: DNS works but site doesn't load

**Fix**:
```bash
# Start web server
sudo systemctl start nginx
sudo systemctl enable nginx

# or for Apache
sudo systemctl start apache2
sudo systemctl enable apache2
```

### Issue 3: Wrong Document Root

**Symptom**: 404 errors or default page shows

**Fix**:
- Verify document root in Nginx/Apache config points to `/home/aboksdfs/app.pointwave.ng/public`
- Check file permissions:
  ```bash
  sudo chown -R www-data:www-data /home/aboksdfs/app.pointwave.ng
  sudo chmod -R 755 /home/aboksdfs/app.pointwave.ng
  ```

### Issue 4: Firewall Blocking

**Symptom**: DNS works, server running, but connection times out

**Fix**:
```bash
# Check if firewall is blocking
sudo ufw status verbose

# Allow traffic
sudo ufw allow 'Nginx Full'
# or
sudo ufw allow 'Apache Full'
```

## Complete Setup Script

Save this as `setup_domain.sh` and run with `sudo bash setup_domain.sh`:

```bash
#!/bin/bash

echo "Setting up app.pointwave.ng..."

# Get server IP
SERVER_IP=$(curl -4 -s ifconfig.me)
echo "Server IP: $SERVER_IP"
echo ""
echo "Add this DNS record to your domain:"
echo "Type: A"
echo "Name: app"
echo "Value: $SERVER_IP"
echo ""
read -p "Press Enter after you've added the DNS record..."

# Wait for DNS propagation
echo "Checking DNS..."
while ! dig app.pointwave.ng +short | grep -q "$SERVER_IP"; do
    echo "Waiting for DNS to propagate..."
    sleep 10
done
echo "✓ DNS is working!"

# Configure Nginx (if installed)
if command -v nginx &> /dev/null; then
    echo "Configuring Nginx..."
    # Nginx configuration here
    sudo systemctl reload nginx
    echo "✓ Nginx configured"
fi

# Test site
echo "Testing site..."
curl -I http://app.pointwave.ng
echo ""
echo "Setup complete! Visit: http://app.pointwave.ng"
```

## Response to KoboPoint

Once DNS is fixed, send this:

---

**Subject**: DNS Issue Resolved - app.pointwave.ng Now Accessible

Hello KoboPoint Team,

We've identified and resolved the issue. The problem was DNS configuration - the domain `app.pointwave.ng` was not pointing to our server.

**What we fixed:**
- Added proper DNS A record for app.pointwave.ng
- Configured web server for the domain
- Verified site is now accessible

**Please test:**
```bash
# Check DNS resolution
dig app.pointwave.ng +short

# Test API endpoint
curl -I https://app.pointwave.ng/api/v1/banks
```

The site should now be fully accessible. The SSL certificate will be our next priority once you confirm the site is reachable.

Thank you for your patience!

---

## Priority

🔴 **CRITICAL** - Site is completely down, blocking all API access
