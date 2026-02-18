#!/bin/bash

echo "=========================================="
echo "Deploy & Test Refund/Notification Features"
echo "=========================================="
echo ""
echo "This will:"
echo "1. Run migration to add refund columns"
echo "2. Test the refund/notification logic"
echo "3. Show you how to test the endpoints"
echo ""

# Run migration
echo "📦 Running migration..."
php artisan migrate --force

if [ $? -ne 0 ]; then
    echo "❌ Migration failed!"
    exit 1
fi

echo "✅ Migration complete"
echo ""

# Run test script
echo "🧪 Running test script..."
php test_refund_notification.php

echo ""
echo "=========================================="
echo "✅ Deployment Complete"
echo "=========================================="
echo ""
echo "📝 Next Steps:"
echo "1. Build frontend: cd frontend && npm run build"
echo "2. Upload build folder to server public/"
echo "3. Test in browser:"
echo "   - Login as company user"
echo "   - Go to RA Transactions page"
echo "   - Click 'Initiate Refund' on a successful transaction"
echo "   - Click 'Resend Notification' to test webhook"
echo "   - Click 'Export' to download CSV"
echo ""
