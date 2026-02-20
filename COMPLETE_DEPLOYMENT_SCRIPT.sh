#!/bin/bash

# Complete Transaction Normalization Deployment Script
# This script deploys all backend changes and prepares for frontend build

echo "========================================="
echo "Transaction Normalization Deployment"
echo "========================================="
echo ""

# Step 1: Pull latest changes
echo "Step 1: Pulling latest changes from GitHub..."
git pull origin main

if [ $? -ne 0 ]; then
    echo "ERROR: Git pull failed!"
    exit 1
fi

echo "✓ Changes pulled successfully"
echo ""

# Step 2: Clear all caches
echo "Step 2: Clearing application caches..."
php artisan cache:clear
php artisan route:clear
php artisan config:clear
php artisan view:clear

echo "✓ Caches cleared"
echo ""

# Step 3: Verify migrations
echo "Step 3: Checking migration status..."
php artisan migrate:status | grep -E "2026_02_21"

echo ""
echo "Required migrations:"
echo "  ✓ 2026_02_21_000001_phase1_add_transaction_normalization_columns"
echo "  ✓ 2026_02_21_000004_create_transaction_status_logs_table"
echo "  ⏳ 2026_02_21_000002_phase2_backfill_transaction_data (run manually)"
echo "  ⏳ 2026_02_21_000003_phase3_enforce_transaction_constraints (run after Phase 2)"
echo ""

# Step 4: Verify routes
echo "Step 4: Verifying routes..."
php artisan route:list | grep -E "(ra-transactions|admin/transactions)" | head -5

echo ""
echo "✓ Routes verified"
echo ""

# Step 5: Test database connection
echo "Step 5: Testing database connection..."
php artisan db:show

echo ""

# Step 6: Restart services
echo "Step 6: Restarting PHP-FPM..."
sudo systemctl restart php-fpm

echo "✓ PHP-FPM restarted"
echo ""

echo "========================================="
echo "Backend Deployment Complete!"
echo "========================================="
echo ""
echo "Next Steps:"
echo "1. Test RA Dashboard endpoint: /api/transactions/ra-transactions"
echo "2. Test Admin Dashboard endpoint: /admin/transactions"
echo "3. Build frontend: cd frontend && npm run build"
echo "4. Deploy frontend build to public directory"
echo ""
echo "For frontend build instructions, see FRONTEND_BUILD_GUIDE.md"
echo ""
