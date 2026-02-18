#!/bin/bash

echo "=========================================="
echo "DEPLOYING BACKEND FIXES"
echo "=========================================="
echo ""

# Run the migration to add phone_account column
echo "1. Running migration to add phone_account column..."
php artisan migrate --force
echo "✓ Migration completed"
echo ""

# Clear all caches
echo "2. Clearing application caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
echo "✓ Caches cleared"
echo ""

# Optimize application
echo "3. Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo "✓ Application optimized"
echo ""

echo "=========================================="
echo "FIXES APPLIED:"
echo "=========================================="
echo "✓ Fixed undefined property customer_id error"
echo "✓ Fixed duplicate refund reference issue"
echo "✓ Added phone_account column to message table"
echo "✓ Updated .gitignore to exclude frontend/LandingPage"
echo ""
echo "=========================================="
echo "DEPLOYMENT COMPLETE!"
echo "=========================================="
