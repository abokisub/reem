#!/bin/bash

echo "=========================================="
echo "PointWave Live Dashboard Check"
echo "=========================================="
echo ""

# Pull latest code
echo "📥 Pulling latest code from GitHub..."
git pull origin main
echo ""

# Check live database for the transaction
echo "🔍 Checking live database for transaction txn_6995fdbf8c0ac44478..."
php artisan tinker --execute="
\$tx = \App\Models\Transaction::where('transaction_id', 'txn_6995fdbf8c0ac44478')->first();
if (\$tx) {
    echo '✅ Transaction Found in Database:\n';
    echo '  ID: ' . \$tx->transaction_id . '\n';
    echo '  Company ID: ' . \$tx->company_id . '\n';
    echo '  Amount: ₦' . \$tx->amount . '\n';
    echo '  Status: ' . \$tx->status . '\n';
    echo '  Created: ' . \$tx->created_at . '\n';
} else {
    echo '❌ Transaction NOT found in database!\n';
    echo '\n';
    echo '📊 Checking all transactions for company 2:\n';
    \$allTx = \App\Models\Transaction::where('company_id', 2)->orderBy('created_at', 'desc')->limit(5)->get();
    echo 'Total transactions: ' . \App\Models\Transaction::where('company_id', 2)->count() . '\n';
    foreach (\$allTx as \$t) {
        echo '  - ' . \$t->transaction_id . ': ₦' . \$t->amount . ' (' . \$t->status . ') - ' . \$t->created_at . '\n';
    }
}
"
echo ""

# Check webhook logs
echo "📋 Checking webhook logs..."
php artisan tinker --execute="
\$webhooks = \App\Models\PalmPayWebhook::orderBy('created_at', 'desc')->limit(3)->get();
echo 'Recent PalmPay Webhooks:\n';
foreach (\$webhooks as \$wh) {
    echo '  - ID ' . \$wh->id . ': ' . \$wh->event . ' (' . \$wh->status . ') - ' . \$wh->created_at . '\n';
    if (\$wh->transaction_id) {
        echo '    Transaction ID: ' . \$wh->transaction_id . '\n';
    }
}
"
echo ""

# Clear all caches
echo "🧹 Clearing all caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
echo "✅ Caches cleared"
echo ""

echo "=========================================="
echo "✅ Check Complete"
echo "=========================================="
echo ""
echo "📝 Next Steps:"
echo "1. If transaction exists but dashboard is empty, rebuild frontend"
echo "2. If transaction doesn't exist, check Laravel logs for errors"
echo "3. Test by sending another small amount (₦100)"
