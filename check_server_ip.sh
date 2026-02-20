#!/bin/bash

echo "╔════════════════════════════════════════════════════════════╗"
echo "║    CHECK SERVER IP ADDRESS                                  ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

echo "🌐 CHECKING SERVER IP ADDRESSES"
echo "------------------------------------------------------------"
echo ""

# Method 1: Using curl to external service
echo "Method 1: External IP (what PalmPay sees)"
echo "------------------------------------------------------------"
IP1=$(curl -s https://api.ipify.org)
if [ -n "$IP1" ]; then
    echo "✅ IP Address: $IP1"
else
    echo "❌ Failed to get IP from ipify.org"
fi
echo ""

# Method 2: Using another service as backup
echo "Method 2: Backup check (ifconfig.me)"
echo "------------------------------------------------------------"
IP2=$(curl -s https://ifconfig.me)
if [ -n "$IP2" ]; then
    echo "✅ IP Address: $IP2"
else
    echo "❌ Failed to get IP from ifconfig.me"
fi
echo ""

# Method 3: Using dig
echo "Method 3: DNS lookup of your domain"
echo "------------------------------------------------------------"
IP3=$(dig +short app.pointwave.ng | tail -n1)
if [ -n "$IP3" ]; then
    echo "✅ IP Address: $IP3"
else
    echo "❌ Failed to get IP from DNS"
fi
echo ""

# Method 4: Check local network interfaces
echo "Method 4: Server network interfaces"
echo "------------------------------------------------------------"
if command -v ip &> /dev/null; then
    echo "Public IPs on server:"
    ip addr show | grep "inet " | grep -v "127.0.0.1" | awk '{print "  " $2}'
elif command -v ifconfig &> /dev/null; then
    echo "Public IPs on server:"
    ifconfig | grep "inet " | grep -v "127.0.0.1" | awk '{print "  " $2}'
else
    echo "⚠️  Cannot check local interfaces"
fi
echo ""

echo "╔════════════════════════════════════════════════════════════╗"
echo "║    SUMMARY                                                  ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

# Determine the most reliable IP
if [ -n "$IP1" ]; then
    MAIN_IP="$IP1"
elif [ -n "$IP2" ]; then
    MAIN_IP="$IP2"
elif [ -n "$IP3" ]; then
    MAIN_IP="$IP3"
else
    MAIN_IP="UNKNOWN"
fi

echo "🎯 YOUR SERVER'S PUBLIC IP ADDRESS:"
echo "------------------------------------------------------------"
echo ""
echo "    $MAIN_IP"
echo ""
echo "------------------------------------------------------------"
echo ""

if [ "$MAIN_IP" != "UNKNOWN" ]; then
    echo "✅ This is the IP address you need to whitelist in PalmPay"
    echo ""
    echo "📋 NEXT STEPS:"
    echo "------------------------------------------------------------"
    echo "1. Copy this IP address: $MAIN_IP"
    echo "2. Contact PalmPay support or login to PalmPay merchant portal"
    echo "3. Add this IP to your whitelist"
    echo "4. Wait 5-10 minutes for changes to propagate"
    echo "5. Test again with: php diagnose_kobopoint_issue.php"
    echo ""
    echo "📧 PALMPAY SUPPORT:"
    echo "------------------------------------------------------------"
    echo "Email: business@palmpay.com"
    echo "Technical: tech-support@palmpay.com"
    echo ""
    echo "Include in your email:"
    echo "  - Merchant ID: 126020209274801"
    echo "  - IP to whitelist: $MAIN_IP"
    echo "  - Reason: Production server IP for API access"
else
    echo "❌ Could not determine server IP"
    echo ""
    echo "Try these alternatives:"
    echo "1. Check cPanel: Home > Server Information > Shared IP Address"
    echo "2. Contact your hosting provider"
    echo "3. Visit: https://whatismyipaddress.com from your server"
fi

echo ""
