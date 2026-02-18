#!/bin/bash

echo "=========================================="
echo "VERIFYING PRODUCTION FIXES"
echo "=========================================="
echo ""

echo "1. Checking if migration file exists..."
if [ -f "database/migrations/2026_02_18_210000_add_phone_account_to_message_table.php" ]; then
    echo "✓ Migration file exists"
else
    echo "✗ Migration file NOT found"
fi
echo ""

echo "2. Checking TransactionController fix..."
if grep -q "time()" app/Http/Controllers/API/TransactionController.php; then
    echo "✓ Refund reference fix applied"
else
    echo "✗ Refund reference fix NOT applied"
fi
echo ""

echo "3. Checking check_transaction_customer.php fix..."
if grep -q "isset(\$va->customer_id)" check_transaction_customer.php; then
    echo "✓ Customer ID check fix applied"
else
    echo "✗ Customer ID check fix NOT applied"
fi
echo ""

echo "4. Checking .gitignore..."
if grep -q "/LandingPage/" .gitignore; then
    echo "✓ LandingPage excluded in .gitignore"
else
    echo "✗ LandingPage NOT excluded in .gitignore"
fi
echo ""

echo "5. Checking git status for frontend files..."
frontend_files=$(git status --short | grep -E "(frontend|LandingPage|Kobopoint)" | wc -l)
if [ "$frontend_files" -eq 0 ]; then
    echo "✓ No frontend files staged for commit"
else
    echo "⚠ Warning: $frontend_files frontend files found in git status"
fi
echo ""

echo "=========================================="
echo "FILES TO BE COMMITTED:"
echo "=========================================="
git status --short
echo ""

echo "=========================================="
echo "VERIFICATION COMPLETE!"
echo "=========================================="
