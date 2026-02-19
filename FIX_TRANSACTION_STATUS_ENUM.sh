#!/bin/bash

echo "=========================================="
echo "FIXING TRANSACTION STATUS ENUM ISSUE"
echo "=========================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Issue:${NC} Transaction status 'debited' is not in the database ENUM"
echo -e "${YELLOW}Solution:${NC} Run the pending migration to expand the status ENUM"
echo ""

# Step 1: Check current migration status
echo "Step 1: Checking migration status..."
php artisan migrate:status | grep "expand_transaction_status"
echo ""

# Step 2: Run the migration
echo "Step 2: Running the migration..."
php artisan migrate --path=database/migrations/2026_02_19_120000_expand_transaction_status_enum.php --force

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Migration executed successfully${NC}"
else
    echo -e "${RED}✗ Migration failed${NC}"
    exit 1
fi
echo ""

# Step 3: Verify the migration
echo "Step 3: Verifying migration status..."
php artisan migrate:status | grep "expand_transaction_status"
echo ""

# Step 4: Check for any failed transactions that need refunding
echo "Step 4: Checking for transactions stuck in error state..."
php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Find transactions that failed due to ENUM error
\$failedTransactions = DB::table('transactions')
    ->where('created_at', '>=', now()->subHours(24))
    ->where('status', 'pending')
    ->whereNotNull('error_message')
    ->where('error_message', 'like', '%Data truncated for column%')
    ->get();

if (\$failedTransactions->count() > 0) {
    echo \"Found \" . \$failedTransactions->count() . \" transactions with ENUM errors:\n\";
    foreach (\$failedTransactions as \$txn) {
        echo \"  - Transaction ID: {\$txn->transaction_id}, Reference: {\$txn->reference}, Amount: {\$txn->total_amount}\n\";
    }
    echo \"\nThese transactions need manual review and potential refund.\n\";
} else {
    echo \"No transactions found with ENUM errors.\n\";
}
"
echo ""

echo -e "${GREEN}=========================================="
echo "FIX COMPLETED"
echo "==========================================${NC}"
echo ""
echo "Next Steps:"
echo "1. Test a new transfer to verify the fix"
echo "2. Review any failed transactions from the last 24 hours"
echo "3. Process refunds for affected transactions if needed"
echo ""
