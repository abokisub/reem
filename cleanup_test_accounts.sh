#!/bin/bash

# Script to cleanup test virtual accounts for KoboPoint
# Run this on your server

cd /home/aboksdfs/app.pointwave.ng

echo "=========================================="
echo "KoboPoint Virtual Accounts Cleanup"
echo "=========================================="
echo ""

# Step 1: List all accounts
echo "Step 1: Listing all virtual accounts..."
echo ""

php artisan tinker --execute="
\$company = \App\Models\Company::where('business_id', '3450968aa027e86e3ff5b0169dc17edd7694a846')->first();
if (!\$company) {
    echo 'Company not found\n';
    exit;
}

\$accounts = \App\Models\VirtualAccount::where('company_id', \$company->id)->get();
echo 'Total Accounts: ' . \$accounts->count() . '\n\n';

foreach (\$accounts as \$index => \$account) {
    echo '--- Account #' . (\$index + 1) . ' (ID: ' . \$account->id . ') ---\n';
    echo 'Account Number: ' . \$account->account_number . '\n';
    echo 'Account Name: ' . \$account->account_name . '\n';
    echo 'Status: ' . \$account->status . '\n';
    if (\$account->customer) {
        echo 'Customer: ' . \$account->customer->first_name . ' ' . \$account->customer->last_name . '\n';
        echo 'Email: ' . \$account->customer->email . '\n';
        echo 'Phone: ' . \$account->customer->phone . '\n';
    }
    echo 'Created: ' . \$account->created_at . '\n';
    
    // Check if it looks like a test account
    \$isTest = false;
    if (\$account->customer) {
        \$name = strtolower(\$account->customer->first_name . ' ' . \$account->customer->last_name);
        \$email = strtolower(\$account->customer->email);
        if (
            strpos(\$name, 'test') !== false ||
            strpos(\$email, 'test') !== false ||
            strpos(\$name, 'demo') !== false ||
            strpos(\$email, 'demo') !== false ||
            strpos(\$name, 'sample') !== false
        ) {
            \$isTest = true;
            echo '⚠️  LOOKS LIKE TEST ACCOUNT\n';
        }
    }
    echo '\n';
}
"

echo ""
echo "=========================================="
echo "Step 2: Delete Test Accounts"
echo "=========================================="
echo ""
echo "Review the list above and identify test account IDs."
echo ""
echo "To delete a test account, run:"
echo "  php artisan tinker"
echo ""
echo "Then in tinker, run:"
echo "  \$account = \App\Models\VirtualAccount::find(ACCOUNT_ID);"
echo "  \$account->delete();"
echo ""
echo "Or to delete by customer name pattern:"
echo "  \$company = \App\Models\Company::where('business_id', '3450968aa027e86e3ff5b0169dc17edd7694a846')->first();"
echo "  \$testAccounts = \App\Models\VirtualAccount::where('company_id', \$company->id)"
echo "      ->whereHas('customer', function(\$q) {"
echo "          \$q->where('first_name', 'LIKE', '%test%')"
echo "            ->orWhere('last_name', 'LIKE', '%test%')"
echo "            ->orWhere('email', 'LIKE', '%test%');"
echo "      })->get();"
echo "  foreach (\$testAccounts as \$account) {"
echo "      echo 'Deleting: ' . \$account->account_name . '\n';"
echo "      \$account->delete();"
echo "  }"
echo ""
echo "=========================================="
echo "IMPORTANT: Backup database before deleting!"
echo "=========================================="
