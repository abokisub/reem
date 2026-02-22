#!/bin/bash

echo "========================================="
echo "Deploy Webhook Secrets System"
echo "========================================="
echo ""

# Pull latest code
echo "📥 Pulling latest code from GitHub..."
git pull origin main

# Generate secrets for existing companies (optional - auto-generates on login)
echo ""
echo "🔑 Generating webhook secrets for existing companies..."
php generate_webhook_secrets.php

# Clear caches
echo ""
echo "🗑️  Clearing caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Clear OPcache
echo "🗑️  Clearing OPcache..."
curl -s "https://app.pointwave.ng/clear-opcache.php?secret=$(grep OPCACHE_SECRET .env | cut -d '=' -f2)"

echo ""
echo "========================================="
echo "✅ Deployment Complete!"
echo "========================================="
echo ""
echo "How it works:"
echo ""
echo "1. NEW COMPANIES:"
echo "   - Webhook secrets auto-generate on registration"
echo "   - Visible immediately in Developer API page"
echo ""
echo "2. EXISTING COMPANIES:"
echo "   - Secrets generated when they visit Developer API page"
echo "   - Or run: php generate_webhook_secrets.php"
echo ""
echo "3. COMPANIES CAN SEE SECRETS:"
echo "   - Login → Developer API → Webhook Configuration"
echo "   - Two fields: Webhook Secret (Live) and (Test)"
echo "   - Copy button for easy copying"
echo ""
echo "Test it:"
echo "1. Login as KoboPoint"
echo "2. Go to Developer API page"
echo "3. Scroll to Webhook Configuration"
echo "4. You should see webhook secrets with copy buttons"
echo ""
