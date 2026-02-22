#!/bin/bash

# Quick script to list KoboPoint virtual accounts
# Run this on your server

cd /home/aboksdfs/app.pointwave.ng

echo "=========================================="
echo "KoboPoint Virtual Accounts"
echo "=========================================="
echo ""

php artisan tinker --execute="
\$company = \App\Models\Company::where('business_id', '3450968aa027e86e3ff5b0169dc17edd7694a846')->first();
if (!\$company) {
    echo 'Company not found\n';
    exit;
}
echo 'Company: ' . \$company->name . ' (ID: ' . \$company->id . ')\n';
echo 'Email: ' . \$company->email . '\n';
echo 'Status: ' . \$company->status . '\n\n';

\$accounts = \App\Models\VirtualAccount::where('company_id', \$company->id)->get();
echo 'Total Virtual Accounts: ' . \$accounts->count() . '\n\n';

if (\$accounts->count() === 0) {
    echo 'No virtual accounts found.\n';
} else {
    foreach (\$accounts as \$index => \$account) {
        echo '--- Account #' . (\$index + 1) . ' ---\n';
        echo 'Account Number: ' . \$account->account_number . '\n';
        echo 'Account Name: ' . \$account->account_name . '\n';
        echo 'Bank: ' . \$account->bank_name . '\n';
        echo 'Status: ' . \$account->status . '\n';
        echo 'KYC Level: ' . (\$account->kyc_level ?? 'N/A') . '\n';
        if (\$account->customer) {
            echo 'Customer: ' . \$account->customer->first_name . ' ' . \$account->customer->last_name . '\n';
            echo 'Customer Email: ' . \$account->customer->email . '\n';
            echo 'Customer Phone: ' . \$account->customer->phone . '\n';
        }
        echo 'Created: ' . \$account->created_at . '\n\n';
    }
}
"

echo "=========================================="
echo "Done"
echo "=========================================="
