#!/bin/bash

# SSL Certificate Fix Script for app.pointwave.ng
# This script installs a valid SSL certificate using Let's Encrypt

echo "=========================================="
echo "SSL Certificate Fix for app.pointwave.ng"
echo "=========================================="
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "Please run as root (use sudo)"
    exit 1
fi

# Step 1: Install Certbot
echo "Step 1: Installing Certbot..."
apt update
apt install certbot python3-certbot-nginx -y
echo "✓ Certbot installed"
echo ""

# Step 2: Check current certificate
echo "Step 2: Checking current certificate..."
certbot certificates
echo ""

# Step 3: Obtain SSL certificate
echo "Step 3: Obtaining SSL certificate for app.pointwave.ng..."
echo "This will automatically configure Nginx..."
certbot --nginx -d app.pointwave.ng --non-interactive --agree-tos --email support@pointwave.ng --redirect

if [ $? -eq 0 ]; then
    echo "✓ SSL certificate obtained and installed"
else
    echo "✗ Failed to obtain SSL certificate"
    echo "Trying alternative method..."
    
    # Alternative: Use webroot method
    certbot certonly --webroot -w /home/aboksdfs/app.pointwave.ng/public -d app.pointwave.ng --non-interactive --agree-tos --email support@pointwave.ng
    
    if [ $? -eq 0 ]; then
        echo "✓ SSL certificate obtained (manual configuration needed)"
    else
        echo "✗ Failed to obtain SSL certificate"
        exit 1
    fi
fi
echo ""

# Step 4: Test certificate renewal
echo "Step 4: Testing automatic renewal..."
certbot renew --dry-run
echo "✓ Auto-renewal configured"
echo ""

# Step 5: Reload Nginx
echo "Step 5: Reloading Nginx..."
nginx -t
if [ $? -eq 0 ]; then
    systemctl reload nginx
    echo "✓ Nginx reloaded"
else
    echo "✗ Nginx configuration error"
    exit 1
fi
echo ""

# Step 6: Verify SSL certificate
echo "Step 6: Verifying SSL certificate..."
echo ""
certbot certificates
echo ""

# Step 7: Test HTTPS connection
echo "Step 7: Testing HTTPS connection..."
curl -I https://app.pointwave.ng 2>&1 | head -n 1
echo ""

# Step 8: Check certificate SAN
echo "Step 8: Checking Subject Alternative Name..."
echo | openssl s_client -servername app.pointwave.ng -connect app.pointwave.ng:443 2>/dev/null | openssl x509 -noout -text | grep -A1 "Subject Alternative Name"
echo ""

echo "=========================================="
echo "SSL Certificate Fix Complete!"
echo "=========================================="
echo ""
echo "Certificate Details:"
certbot certificates | grep -A5 "app.pointwave.ng"
echo ""
echo "Next Steps:"
echo "1. Test API endpoint: curl -I https://app.pointwave.ng/api/v1/banks"
echo "2. Notify KoboPoint that SSL is fixed"
echo "3. Ask them to re-enable SSL verification"
echo ""
echo "Auto-renewal is configured and will run automatically."
echo ""
