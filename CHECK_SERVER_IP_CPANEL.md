# How to Check Your Server IP in cPanel

## Method 1: cPanel Dashboard (Easiest)

1. **Login to cPanel**:
   - Go to: `https://app.pointwave.ng:2083`
   - Or: `https://your-hosting-domain.com:2083`
   - Enter your cPanel username and password

2. **Check Server Information**:
   - On the right sidebar, look for "General Information" or "Statistics"
   - Find: "Shared IP Address" or "Server IP Address"
   - **This is your server's IP** - write it down!

3. **Alternative Location**:
   - Go to: "Server Information" (in the General Information section)
   - Look for: "Shared IP Address"

---

## Method 2: Terminal/SSH in cPanel

1. **Open Terminal in cPanel**:
   - Login to cPanel
   - Search for "Terminal" in the search box
   - Click "Terminal" to open

2. **Run this command**:
   ```bash
   curl https://api.ipify.org
   ```
   
3. **The output is your server's public IP**

---

## Method 3: Create a PHP File

1. **In cPanel File Manager**:
   - Go to: File Manager
   - Navigate to: `public_html` or your app directory
   - Create new file: `check_ip.php`

2. **Add this code**:
   ```php
   <?php
   echo "Server IP: " . $_SERVER['SERVER_ADDR'] . "<br>";
   echo "Public IP: " . file_get_contents('https://api.ipify.org');
   ?>
   ```

3. **Visit in browser**:
   - Go to: `https://app.pointwave.ng/check_ip.php`
   - The "Public IP" is what you need

4. **Delete the file after** (security)

---

## Method 4: SSH Command (If you have SSH access)

```bash
# Login via SSH
ssh your-username@app.pointwave.ng

# Check public IP
curl https://api.ipify.org

# Or
curl https://ifconfig.me

# Or check server IP
hostname -I
```

---

## What IP Do You Need?

You need the **PUBLIC IP** (outgoing IP) - this is what PalmPay sees when your server makes API calls.

**NOT** the local IP (192.168.x.x or 127.0.0.1)

---

## Quick Test - Run on Your Server

**Option A: Via cPanel Terminal**

1. Login to cPanel
2. Open Terminal
3. Navigate to your app:
   ```bash
   cd /home/aboksdfs/app.pointwave.ng
   ```
4. Run:
   ```bash
   curl https://api.ipify.org
   ```
5. **This is your server IP!**

**Option B: Via SSH**

```bash
ssh aboksdfs@app.pointwave.ng
cd /home/aboksdfs/app.pointwave.ng
curl https://api.ipify.org
```

---

## After You Get the IP

1. **Write it down** (e.g., `66.29.153.81`)

2. **Whitelist in PalmPay**:
   - Login to PalmPay merchant portal
   - Go to: API Settings → IP Whitelist
   - Add your server IP
   - Save

3. **Test**:
   ```bash
   cd /home/aboksdfs/app.pointwave.ng
   php diagnose_kobopoint_issue.php
   ```

---

## Common Server IPs for Shared Hosting

Your server IP is likely one of these formats:
- `66.29.153.81` (DNS IP we found earlier)
- `162.241.x.x` (common for cPanel hosting)
- `192.185.x.x` (common for shared hosting)
- `198.54.x.x` (common for cloud hosting)

**NOT** `105.115.5.6` (that's your local machine)

---

## If You Can't Access cPanel

Contact your hosting provider (e.g., Namecheap, Hostinger, etc.) and ask:

"What is the outgoing IP address for my server at app.pointwave.ng?"

They will tell you immediately.

---

## Summary

**Easiest Method**: 
1. Login to cPanel
2. Look at right sidebar → "Shared IP Address"
3. That's your server IP!

**Alternative**:
1. cPanel → Terminal
2. Run: `curl https://api.ipify.org`
3. That's your server IP!

**Then**: Whitelist that IP in PalmPay
