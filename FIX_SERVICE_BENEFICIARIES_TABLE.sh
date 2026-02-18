#!/bin/bash

echo "=========================================="
echo "FIXING SERVICE_BENEFICIARIES TABLE"
echo "=========================================="

echo ""
echo "1. Dropping partially created table..."
php artisan tinker --execute="DB::statement('DROP TABLE IF EXISTS service_beneficiaries');"
echo "✓ Table dropped"

echo ""
echo "2. Running fixed migration..."
php artisan migrate --force --path=database/migrations/2026_02_18_220000_create_service_beneficiaries_table.php
echo "✓ Migration completed"

echo ""
echo "3. Verifying table structure..."
php artisan tinker --execute="DB::select('DESCRIBE service_beneficiaries');"

echo ""
echo "=========================================="
echo "TABLE FIX COMPLETE!"
echo "=========================================="
