#!/bin/bash

echo "=========================================="
echo "Fix Company Dashboard & RA Transactions"
echo "=========================================="
echo ""
echo "This will:"
echo "1. Pull latest backend code"
echo "2. Clear all caches"
echo "3. Test company dashboard data"
echo "4. Test RA transactions endpoint"
echo ""

# Pull latest code
echo "📥 Pulling latest code from GitHub..."
git pull origin main

# Clear all caches
echo ""
echo "🧹 Clearing all caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

echo ""
echo "✅ Caches cleared"
echo ""

# Test company dashboard
echo "🔍 Testing company dashboard data..."
echo ""

php artisan tinker --execute="
\$user = \App\Models\User::where('email', 'abokisub@gmail.com')->first();
echo \"Company: \" . \$user->active_company_id . \"\n\";
echo \"Username: \" . \$user->username . \"\n\n\";

// Test transactions query (what dashboard should show)
\$transactions = \DB::table('transactions')
    ->where('company_id', \$user->active_company_id)
    ->where('type', 'credit')
    ->where('channel', 'virtual_account')
    ->where('status', 'success')
    ->get();

echo \"Total Transactions: \" . \$transactions->count() . \"\n\";
echo \"Total Revenue: ₦\" . \$transactions->sum('amount') . \"\n\n\";

// Test pending settlement
\$pendingSettlement = \DB::table('settlement_queue')
    ->where('company_id', \$user->active_company_id)
    ->where('status', 'pending')
    ->sum('amount');

echo \"Pending Settlement: ₦\" . \$pendingSettlement . \"\n\";
echo \"Pending Count: \" . \DB::table('settlement_queue')
    ->where('company_id', \$user->active_company_id)
    ->where('status', 'pending')
    ->count() . \"\n\n\";

// Show latest 3 transactions
echo \"Latest 3 Transactions:\n\";
\$latest = \DB::table('transactions')
    ->where('company_id', \$user->active_company_id)
    ->where('type', 'credit')
    ->where('channel', 'virtual_account')
    ->orderBy('id', 'desc')
    ->limit(3)
    ->get();

foreach (\$latest as \$tx) {
    echo \"  - \" . \$tx->transaction_id . \": ₦\" . \$tx->amount . \" (\" . \$tx->status . \") - \" . \$tx->created_at . \"\n\";
}

echo \"\n\";
echo \"🔍 Testing RA Transactions API Response...\n\";
echo \"\n\";

// Test what the RA transactions endpoint will return
\$raTransactions = \DB::table('transactions')
    ->where('company_id', \$user->active_company_id)
    ->where('channel', 'virtual_account')
    ->select(
        '*',
        'reference as transid',
        'created_at as date',
        'description as details',
        'fee as charges',
        \DB::raw(\"CASE WHEN status = 'success' THEN 'successful' WHEN status = 'failed' THEN 'failed' ELSE 'processing' END as status\")
    )
    ->orderBy('id', 'desc')
    ->limit(3)
    ->get();

echo \"RA Transactions Count: \" . \$raTransactions->count() . \"\n\";
echo \"Sample RA Transaction Data:\n\";
foreach (\$raTransactions as \$tx) {
    echo \"  - Reference: \" . \$tx->transid . \"\n\";
    echo \"    Amount: ₦\" . \$tx->amount . \"\n\";
    echo \"    Status: \" . \$tx->status . \"\n\";
    echo \"    Fee: ₦\" . (\$tx->charges ?? 0) . \"\n\";
    echo \"    Date: \" . \$tx->date . \"\n\n\";
}
"

echo ""
echo "=========================================="
echo "✅ Fix Complete"
echo "=========================================="
echo ""
echo "📝 Next Steps:"
echo "1. Login to company dashboard: abokisub@gmail.com"
echo "2. Check if transactions are now visible on main dashboard"
echo "3. Check if RA Transactions page shows data"
echo "4. Check if pending settlement count shows correctly"
echo "5. Check if balance updates with new transactions"
echo ""
