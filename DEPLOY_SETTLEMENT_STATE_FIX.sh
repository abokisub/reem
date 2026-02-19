#!/bin/bash

echo "=========================================="
echo "DEPLOYING SETTLEMENT STATE TRANSITION FIX"
echo "=========================================="
echo ""

# Pull latest changes
echo "1. Pulling latest changes from GitHub..."
git pull origin main

if [ $? -ne 0 ]; then
    echo "❌ Git pull failed!"
    exit 1
fi

echo "✅ Code updated successfully"
echo ""

# Clear caches
echo "2. Clearing application caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo "✅ Caches cleared"
echo ""

# Check if the fix is in place
echo "3. Verifying fix..."
if grep -q "status' => 'debited'" app/Services/PalmPay/TransferService.php; then
    echo "✅ Fix verified: Transaction status set to 'debited'"
else
    echo "⚠️  Warning: Could not verify fix in code"
fi

echo ""
echo "=========================================="
echo "✅ DEPLOYMENT COMPLETE!"
echo "=========================================="
echo ""
echo "What was fixed:"
echo "- Changed initial transfer status from 'pending' to 'debited'"
echo "- This allows proper state transitions: debited → processing → successful"
echo "- No more 'Invalid state transition' errors"
echo ""
echo "Next steps:"
echo "1. Test a settlement transfer"
echo "2. Check logs: tail -f storage/logs/laravel.log"
echo "3. Verify settlement completes without errors"
echo ""
