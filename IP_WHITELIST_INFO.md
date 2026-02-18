# 🌐 IP Whitelist Information for PalmPay

## Your Server IP Addresses

Run these commands to get your IP addresses:

### 1. Public IP Address (External)
```bash
curl -s ifconfig.me
# OR
curl -s https://api.ipify.org
# OR
curl -s https://checkip.amazonaws.com
```

### 2. Local IP Address (Internal)
```bash
hostname -I | awk '{print $1}'
# OR
ip addr show | grep "inet " | grep -v 127.0.0.1
```

### 3. All Network Interfaces
```bash
ip addr show
```

---

## What to Whitelist on PalmPay Dashboard

### For Production Server:
1. **Public IP Address** - This is what PalmPay sees
2. **Server Domain IP** - If using domain name

### For Development/Testing:
1. **Your Office/Home IP** - For testing from local machine
2. **VPN IP** - If using VPN

---

## How to Find Your IPs

### Method 1: From Terminal
```bash
# Get public IP
curl ifconfig.me

# Get local IP
hostname -I
```

### Method 2: From PHP
```php
// Create a test file: public/ip-check.php
<?php
echo "Server IP: " . $_SERVER['SERVER_ADDR'] . "\n";
echo "Remote IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
echo "Public IP: " . file_get_contents('https://api.ipify.org') . "\n";
```

Then visit: `http://your-domain.com/ip-check.php`

### Method 3: From Laravel
```bash
php artisan tinker --execute="echo 'Server IP: ' . gethostbyname(gethostname()) . PHP_EOL;"
```

---

## PalmPay Dashboard - Where to Add IP

1. **Login to PalmPay Merchant Dashboard**
   - URL: https://merchant.palmpay-inc.com (or your merchant portal)

2. **Navigate to Settings**
   - Look for "API Settings" or "Security Settings"
   - Find "IP Whitelist" or "Allowed IPs"

3. **Add Your Server IP**
   - Format: `xxx.xxx.xxx.xxx`
   - You can add multiple IPs (one per line)
   - Save changes

4. **Test Connection**
   ```bash
   php artisan banks:sync
   ```

---

## Common IP Formats

### Single IP
```
192.168.1.100
```

### IP Range (CIDR)
```
192.168.1.0/24
```

### Multiple IPs
```
192.168.1.100
203.0.113.45
198.51.100.78
```

---

## Troubleshooting

### If PalmPay Still Blocks You:

1. **Check if IP changed**
   ```bash
   curl ifconfig.me
   ```

2. **Verify DNS resolution**
   ```bash
   nslookup open-gw-prod.palmpay-inc.com
   ```

3. **Test connectivity**
   ```bash
   curl -v https://open-gw-prod.palmpay-inc.com
   ```

4. **Check firewall**
   ```bash
   sudo ufw status
   ```

---

## After Whitelisting

Once you've added your IP to PalmPay dashboard:

1. **Wait 5-10 minutes** for changes to propagate

2. **Test bank sync**
   ```bash
   php artisan banks:sync
   ```

3. **Check logs**
   ```bash
   tail -f storage/logs/laravel.log
   ```

4. **Verify banks loaded**
   ```bash
   php artisan tinker --execute="echo App\Models\Bank::count() . ' banks loaded';"
   ```

---

## Quick IP Check Script

Save this as `check-ip.sh`:

```bash
#!/bin/bash

echo "=== IP Address Information ==="
echo ""
echo "Public IP (Method 1):"
curl -s ifconfig.me
echo ""
echo ""
echo "Public IP (Method 2):"
curl -s https://api.ipify.org
echo ""
echo ""
echo "Local IP:"
hostname -I | awk '{print $1}'
echo ""
echo ""
echo "All Network Interfaces:"
ip addr show | grep "inet " | grep -v 127.0.0.1
echo ""
echo "=== Add these IPs to PalmPay Dashboard ==="
```

Run it:
```bash
chmod +x check-ip.sh
./check-ip.sh
```

---

## Important Notes

1. **Dynamic IP Warning**: If your server has a dynamic IP (changes periodically), you'll need to:
   - Use a static IP
   - OR update PalmPay whitelist when IP changes
   - OR use a VPN with static IP

2. **Load Balancer**: If behind a load balancer, whitelist the load balancer's IP

3. **CDN/Proxy**: If using Cloudflare or similar, whitelist the origin server IP

4. **Multiple Servers**: If you have staging + production, whitelist both IPs

---

## Contact PalmPay Support

If you need help with IP whitelisting:

- **Email**: merchant-support@palmpay-inc.com
- **Phone**: Check your merchant agreement
- **Dashboard**: Use support chat in merchant portal

Provide them with:
- Your Merchant ID: `126020209274801`
- Your App ID: `L260202154361881198161`
- Your Server IP: (from commands above)

---

**Next Steps:**
1. Run the IP check commands above
2. Copy your public IP address
3. Login to PalmPay merchant dashboard
4. Add IP to whitelist
5. Wait 5-10 minutes
6. Run `php artisan banks:sync`
7. Verify banks loaded successfully
