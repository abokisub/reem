#!/bin/bash

echo "=== Webhook Secret Fix Deployment ==="
echo ""

# Ensure we're in the right directory
cd /home/aboksdfs/app.pointwave.ng

# Pull latest changes
echo "1. Pulling latest code..."
git pull origin main

# Check if fix script exists
if [ ! -f "fix_webhook_secrets_encryption.php" ]; then
    echo "ERROR: fix_webhook_secrets_encryption.php not found!"
    echo "Checking git status..."
    git status
    exit 1
fi

echo "✓ Fix script found"
echo ""

# Run the fix script
echo "2. Running webhook secret fix..."
php fix_webhook_secrets_encryption.php

echo ""
echo "3. Clearing cache..."
php artisan config:clear
php artisan cache:clear

echo ""
echo "4. Testing credentials API..."
php test_credentials_api.php

echo ""
echo "=== Deployment Complete ==="
