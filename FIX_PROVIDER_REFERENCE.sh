#!/bin/bash

echo "=========================================="
echo "FIXING PROVIDER_REFERENCE COLUMN ISSUE"
echo "=========================================="
echo ""

# Run the migration
echo "Running migration to add provider_reference column..."
php artisan migrate --force

if [ $? -eq 0 ]; then
    echo "✓ Migration completed successfully"
else
    echo "✗ Migration failed"
    exit 1
fi

echo ""
echo "Clearing cache..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear

echo ""
echo "=========================================="
echo "FIX DEPLOYED SUCCESSFULLY!"
echo "=========================================="
echo ""
echo "The provider_reference column has been added to transactions table."
echo "Transfers should now work without errors."
