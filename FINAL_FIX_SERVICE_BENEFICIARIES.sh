#!/bin/bash

echo "=========================================="
echo "FINAL FIX FOR SERVICE_BENEFICIARIES TABLE"
echo "=========================================="

echo ""
echo "Step 1: Checking if table exists..."
TABLE_EXISTS=$(php artisan tinker --execute="echo Schema::hasTable('service_beneficiaries') ? 'yes' : 'no';")
echo "Table exists: $TABLE_EXISTS"

if [[ "$TABLE_EXISTS" == *"yes"* ]]; then
    echo ""
    echo "Step 2: Dropping existing table..."
    php artisan tinker --execute="Schema::dropIfExists('service_beneficiaries'); echo 'Table dropped';"
else
    echo ""
    echo "Step 2: Table doesn't exist, skipping drop..."
fi

echo ""
echo "Step 3: Removing migration record from database..."
php artisan tinker --execute="DB::table('migrations')->where('migration', '2026_02_18_220000_create_service_beneficiaries_table')->delete(); echo 'Migration record removed';"

echo ""
echo "Step 4: Running fresh migration..."
php artisan migrate --path=database/migrations/2026_02_18_220000_create_service_beneficiaries_table.php

echo ""
echo "Step 5: Verifying table structure..."
php artisan tinker --execute="
\$columns = DB::select('DESCRIBE service_beneficiaries');
foreach (\$columns as \$col) {
    echo \$col->Field . ' - ' . \$col->Type . ' - Key: ' . \$col->Key . PHP_EOL;
}
"

echo ""
echo "Step 6: Checking indexes..."
php artisan tinker --execute="
\$indexes = DB::select('SHOW INDEX FROM service_beneficiaries');
echo 'Indexes on service_beneficiaries:' . PHP_EOL;
foreach (\$indexes as \$idx) {
    echo '  ' . \$idx->Key_name . ' on ' . \$idx->Column_name . PHP_EOL;
}
"

echo ""
echo "=========================================="
echo "TABLE FIX COMPLETE!"
echo "=========================================="
echo ""
echo "IMPORTANT: PalmPay IP Whitelist Issue Detected"
echo "Your production server IP needs to be whitelisted in PalmPay dashboard"
echo "Error: 'request ip not in ip white list'"
echo ""
echo "Action Required:"
echo "1. Get your server IP: curl ifconfig.me"
echo "2. Add it to PalmPay merchant dashboard IP whitelist"
echo "=========================================="
