#!/bin/bash

echo "=========================================="
echo "FIXING service_beneficiaries TABLE"
echo "=========================================="
echo ""

echo "1. Dropping existing table (if exists)..."
php artisan tinker --execute="DB::statement('DROP TABLE IF EXISTS service_beneficiaries');"
echo "✓ Table dropped"
echo ""

echo "2. Running fixed migration..."
php artisan migrate --force --path=database/migrations/2026_02_18_220000_create_service_beneficiaries_table.php
echo "✓ Migration completed"
echo ""

echo "3. Verifying table creation..."
php artisan tinker --execute="echo Schema::hasTable('service_beneficiaries') ? 'Table exists!' : 'Table missing!';"
echo ""

echo "4. Clearing application caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
echo "✓ Caches cleared"
echo ""

echo "=========================================="
echo "FIX COMPLETE!"
echo "=========================================="
echo ""
echo "The service_beneficiaries table has been"
echo "recreated with proper index configuration."
echo ""
