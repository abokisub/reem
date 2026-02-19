#!/bin/bash

echo "=========================================="
echo "Set Settlement Delay to 1 Minute"
echo "=========================================="
echo ""
echo "This will update the settlement delay to 1 minute (0.0167 hours)"
echo ""
read -p "Continue? (y/n): " confirm

if [ "$confirm" != "y" ]; then
    echo "Cancelled."
    exit 0
fi

echo ""
echo "🔧 Updating settlement delay to 1 minute..."

php artisan tinker --execute="
DB::table('settings')->update([
    'settlement_delay_hours' => 0.0167,
    'settlement_skip_weekends' => false,
    'settlement_skip_holidays' => false,
    'updated_at' => now()
]);

\$settings = DB::table('settings')->first();
echo '\n✅ Settlement delay updated!\n';
echo 'Current Settings:\n';
echo '  - Delay: ' . \$settings->settlement_delay_hours . ' hours (1 minute)\n';
echo '  - Skip Weekends: ' . (\$settings->settlement_skip_weekends ? 'Yes' : 'No') . '\n';
echo '  - Skip Holidays: ' . (\$settings->settlement_skip_holidays ? 'Yes' : 'No') . '\n';
echo '  - Settlement Time: ' . \$settings->settlement_time . '\n';
echo '  - Auto Settlement: ' . (\$settings->auto_settlement_enabled ? 'Enabled' : 'Disabled') . '\n';
"

echo ""
echo "=========================================="
echo "✅ Settlement delay set to 1 minute!"
echo "=========================================="
echo ""
echo "📝 What this means:"
echo "   - Transactions will settle 1 minute after they're received"
echo "   - Weekends and holidays are NOT skipped (immediate settlement)"
echo "   - The exact time is preserved (no 2am adjustment)"
echo ""
echo "🧪 Test it:"
echo "   1. Send ₦250 to PalmPay account 6644694207"
echo "   2. Wait 1 minute"
echo "   3. Run: php artisan settlements:process"
echo "   4. Check company wallet balance"
echo ""
