#!/bin/bash

echo "========================================="
echo "Deploy Master Wallet Auto-Creation Fix"
echo "========================================="

# Pull latest code
echo "📥 Pulling latest code from GitHub..."
git pull origin main

# Clear caches
echo "🗑️  Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Clear OPcache
echo "🗑️  Clearing OPcache..."
curl -s https://app.pointwave.ng/clear-opcache.php > /dev/null 2>&1 || echo "OPcache clear skipped"

echo ""
echo "========================================="
echo "✅ Deployment Complete!"
echo "========================================="
echo ""
echo "What was fixed:"
echo "- Admin KYC approval now auto-creates company wallet"
echo "- Admin KYC approval now auto-creates master virtual account"
echo "- Master account uses company director's BVN (aggregator model)"
echo ""
echo "For existing companies missing master wallet:"
echo "1. Run diagnostic: php check_amtpay_company.php"
echo "2. Run fix: php fix_amtpay_master_wallet.php"
echo ""
echo "The fix ensures:"
echo "✅ Company wallet created on KYC approval"
echo "✅ Master virtual account created on KYC approval"
echo "✅ Customers can create accounts without KYC"
echo "✅ All customer accounts use company director's BVN"
