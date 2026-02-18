#!/bin/bash

echo "=========================================="
echo "DEPLOYING TRANSFER & WITHDRAWAL FIXES"
echo "=========================================="
echo ""

echo "1. Running migration for service_beneficiaries table..."
php artisan migrate --force --path=database/migrations/2026_02_18_220000_create_service_beneficiaries_table.php
echo "✓ Migration completed"
echo ""

echo "2. Clearing application caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
echo "✓ Caches cleared"
echo ""

echo "3. Optimizing application..."
php artisan config:cache
php artisan route:cache
echo "✓ Application optimized"
echo ""

echo "=========================================="
echo "FIXES APPLIED:"
echo "=========================================="
echo "✓ Integrated PalmPay transfer service"
echo "✓ Created service_beneficiaries table"
echo "✓ Fixed transfer warnings"
echo "✓ Fixed database errors"
echo ""
echo "=========================================="
echo "DEPLOYMENT COMPLETE!"
echo "=========================================="
echo ""
echo "Note: Frontend changes need manual upload:"
echo "  - frontend/src/components/TransferConfirmDialog.js (new)"
echo "  - frontend/src/pages/dashboard/TransferFunds.js (modified)"
echo ""
echo "After uploading, rebuild frontend:"
echo "  cd frontend && npm run build"
echo ""
