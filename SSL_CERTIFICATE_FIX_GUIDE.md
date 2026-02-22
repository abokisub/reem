# SSL Certificate Fix for app.pointwave.ng

## Issue Report from KoboPoint

**Error**: `cURL error 60: SSL: no alternative certificate subject name matches target hostname 'app.pointwave.ng'`

**Impact**: KoboPoint cannot make secure API calls to our production endpoint.

## Problem

The SSL certificate installed on `app.pointwave.ng` doesn't include the domain in its Subject Alternative Name (SAN) field. This causes SSL verification to fail when clients try to connect.

## Solution

You need to obtain and install a proper SSL certificate that includes `app.pointwave.ng` in the SAN field.

### Option 1: Let's Encrypt (Free & Recommended)

Let's Encrypt provides free SSL certificates with automatic renewal.

#### Step 1: Install Certbot

```bash
# For Ubuntu/Debian
sudo apt update
sudo apt install certbot python3-certbot-nginx -y

# Or for Apache
sudo apt install certbot python3-certbot-apache -y
```

#### Step 2: Stop Nginx/Apache temporarily (if needed)

```bash
# For Nginx
sudo systemctl stop nginx

# For Apache
sudo systemctl stop apache2
```

#### Step 3: Obtain SSL Certificate

```bash
# Using standalone mode (if web server is stopped)
sudo certbot certonly --standalone -d app.pointwave.ng

# OR using webroot mode (if web server is running)
sudo certbot certonly --webroot -w /home/aboksdfs/app.pointwave.ng/public -d app.pointwave.ng

# OR using Nginx plugin (automatic configuration)
sudo certbot --nginx -d app.pointwave.ng

# OR using Apache plugin (automatic configuration)
sudo certbot --apache -d app.pointwave.ng
```

#### Step 4: Verify Certificate

```bash
# Check certificate details
sudo certbot certificates

# Test SSL configuration
curl -vI https://app.pointwave.ng 2>&1 | grep -E "subject:|issuer:|CN=|DNS:"
```

#### Step 5: Configure Auto-Renewal

```bash
# Test renewal
sudo certbot renew --dry-run

# Certbot automatically sets up a cron job for renewal
# Verify it's scheduled
sudo systemctl status certbot.timer
```

#### Step 6: Restart Web Server

```bash
# For Nginx
sudo systemctl start nginx
sudo systemctl reload nginx

# For Apache
sudo systemctl start apache2
sudo systemctl reload apache2
```

### Option 2: Manual SSL Configuration (If using Nginx)

If you already have an SSL certificate, ensure your Nginx configuration includes it properly:

```nginx
server {
    listen 443 ssl http2;
    server_name app.pointwave.ng;

    # SSL Certificate paths
    ssl_certificate /etc/letsencrypt/live/app.pointwave.ng/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/app.pointwave.ng/privkey.pem;

    # SSL Configuration
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;

    # Root directory
    root /home/aboksdfs/app.pointwave.ng/public;
    index index.php index.html;

    # PHP-FPM configuration
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Laravel routes
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
}

# Redirect HTTP to HTTPS
server {
    listen 80;
    server_name app.pointwave.ng;
    return 301 https://$server_name$request_uri;
}
```

Save to: `/etc/nginx/sites-available/app.pointwave.ng`

Then:
```bash
sudo ln -s /etc/nginx/sites-available/app.pointwave.ng /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Option 3: Using Cloudflare (Alternative)

If you use Cloudflare:

1. Go to Cloudflare Dashboard
2. Select your domain `pointwave.ng`
3. Go to SSL/TLS → Overview
4. Set SSL/TLS encryption mode to "Full (strict)"
5. Go to SSL/TLS → Edge Certificates
6. Enable "Always Use HTTPS"
7. Enable "Automatic HTTPS Rewrites"

## Verification Steps

After installing the certificate, verify it works:

### 1. Check Certificate Details

```bash
echo | openssl s_client -servername app.pointwave.ng -connect app.pointwave.ng:443 2>/dev/null | openssl x509 -noout -text | grep -A1 "Subject Alternative Name"
```

Expected output should include:
```
X509v3 Subject Alternative Name:
    DNS:app.pointwave.ng
```

### 2. Test with cURL

```bash
curl -I https://app.pointwave.ng
```

Should return `200 OK` without SSL errors.

### 3. Test from KoboPoint's Server

Ask KoboPoint to test:
```bash
curl -I https://app.pointwave.ng/api/v1/banks
```

Should work without SSL verification errors.

## Quick Fix Commands (All-in-One)

```bash
# Install Certbot
sudo apt update && sudo apt install certbot python3-certbot-nginx -y

# Obtain and install certificate (Nginx)
sudo certbot --nginx -d app.pointwave.ng

# Test renewal
sudo certbot renew --dry-run

# Verify certificate
curl -vI https://app.pointwave.ng 2>&1 | grep "subject:"
```

## Response to KoboPoint

Once fixed, send this message:

---

**Subject**: SSL Certificate Fixed for app.pointwave.ng

Hello KoboPoint Team,

We've updated the SSL certificate for app.pointwave.ng. The certificate now properly includes the domain in the Subject Alternative Name (SAN) field.

**What we did:**
- Installed a valid SSL certificate from Let's Encrypt
- Configured automatic renewal
- Verified SSL validation works correctly

**Please test:**
```bash
curl -I https://app.pointwave.ng/api/v1/banks
```

You should now be able to:
- Re-enable SSL verification in your production code
- Make secure API calls without certificate errors
- Remove the temporary SSL verification bypass

**Certificate Details:**
- Issuer: Let's Encrypt
- Valid for: app.pointwave.ng
- Expiry: [Check with: `sudo certbot certificates`]
- Auto-renewal: Enabled

Please test your integration and let us know if you encounter any issues.

Thank you for reporting this!

Best regards,
PointWave Team

---

## Troubleshooting

### Issue: Port 80/443 already in use

```bash
# Check what's using the ports
sudo lsof -i :80
sudo lsof -i :443

# Stop the service temporarily
sudo systemctl stop nginx
# or
sudo systemctl stop apache2
```

### Issue: DNS not pointing to server

```bash
# Check DNS resolution
dig app.pointwave.ng +short
nslookup app.pointwave.ng
```

Ensure the A record points to your server's IP address.

### Issue: Firewall blocking ports

```bash
# Allow HTTPS through firewall
sudo ufw allow 443/tcp
sudo ufw allow 80/tcp
sudo ufw reload
```

## Status

⚠️ **URGENT** - This needs to be fixed immediately as it's blocking KoboPoint's production integration.

## Priority

🔴 **HIGH PRIORITY** - Security and production integration issue
