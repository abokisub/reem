#!/bin/bash

echo "╔════════════════════════════════════════════════════════════╗"
echo "║         FIX VA DEPOSIT FEE CONFIGURATION                   ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

echo "📋 What this fix does:"
echo "  1. Adds virtual_funding_* columns to settings table"
echo "  2. Updates AdminController to sync transfer_charge_* → virtual_funding_*"
echo "  3. FeeService will now read from virtual_funding_* for VA deposits"
echo ""

read -p "Push to GitHub? (y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]
then
    echo "📤 Pushing to GitHub..."
    git add database/migrations/2026_02_20_180000_add_virtual_funding_columns_to_settings.php
    git add app/Http/Controllers/API/AdminController.php
    git add app/Services/FeeService.php
    git add check_live_fee_columns.php
    git add FIX_VA_DEPOSIT_FEE_COMPLETE.sh
    git add VA_DEPOSIT_FEE_MISMATCH_ANALYSIS.md
    
    git commit -m "Fix: Add virtual_funding columns for VA deposit fees

- Added migration to create virtual_funding_type/value/cap columns
- AdminController now syncs transfer_charge_* to virtual_funding_*
- FeeService reads from virtual_funding_* for va_deposit fees
- Fixes issue where VA deposits always charged 0.5% fallback
- Admin panel 'Funding with Bank Transfer' now controls VA deposit fees"
    
    git push origin main
    
    echo "✅ Pushed to GitHub!"
    echo ""
fi

echo "📥 Deploy to server:"
echo "  ssh aboksdfs@server350.web-hosting.com"
echo "  cd /home/aboksdfs/app.pointwave.ng"
echo "  git pull origin main"
echo "  php artisan migrate"
echo "  php artisan cache:clear"
echo "  php artisan config:clear"
echo ""

echo "🧪 Test with new deposit:"
echo "  The fee should now match what you set in admin panel"
echo "  at /secure/discount/banks under 'Funding with Bank Transfer'"
echo ""

echo "✅ Fix complete!"
