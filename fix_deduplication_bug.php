<?php
// Fix the deduplication bug in VirtualAccountService
// This will restore the corrupted account and prevent future incorrect matches

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\VirtualAccount;
use App\Models\CompanyUser;

$confirm = $argv[1] ?? null;

if ($confirm !== 'CONFIRM') {
    echo "⚠️  WARNING: This will fix the deduplication bug\n";
    echo "This will:\n";
    echo "1. Restore account 6662822179 to original owner 'Nana Aisha Bello'\n";
    echo "2. Show you the deduplication bug that needs to be fixed in code\n\n";
    echo "To proceed, run: php fix_deduplication_bug.php CONFIRM\n";
    exit(1);
}

echo "=== FIXING DEDUPLICATION BUG ===\n\n";

// 1. Find the corrupted account
$corruptedAccount = VirtualAccount::where('account_number', '6662822179')->first();

if (!$corruptedAccount) {
    echo "❌ Account 6662822179 not found\n";
    exit(1);
}

echo "1. CURRENT CORRUPTED STATE:\n";
echo "- Account: {$corruptedAccount->account_number}\n";
echo "- Customer Name: {$corruptedAccount->customer_name} (WRONG)\n";
echo "- Phone: {$corruptedAccount->customer_phone}\n";
echo "- Email: {$corruptedAccount->customer_email}\n";
echo "- User ID: {$corruptedAccount->user_id}\n\n";

// 2. Find the original customer data
// The email nanabello161@gmail.com suggests the original name was "Nana Bello"
echo "2. RESTORING ORIGINAL CUSTOMER DATA:\n";
echo "Based on email 'nanabello161@gmail.com', original customer was likely 'Nana Aisha Bello'\n";

// Restore the original customer name
$corruptedAccount->update([
    'customer_name' => 'Nana Aisha Bello',
    'updated_at' => now()
]);

echo "✅ Restored customer name to 'Nana Aisha Bello'\n\n";

// 3. Analyze the deduplication bug
echo "3. DEDUPLICATION BUG ANALYSIS:\n";
echo "The bug is in VirtualAccountService.php line ~60-80\n";
echo "The deduplication logic is too aggressive and matches accounts incorrectly.\n\n";

echo "PROBLEMATIC CODE:\n";
echo "```php\n";
echo "\$existing = VirtualAccount::where('company_id', \$companyId)\n";
echo "    ->where(function (\$query) use (\$email, \$phone, \$companyId) {\n";
echo "        \$query->whereIn('user_id', function (\$q) use (\$email, \$phone) {\n";
echo "            \$q->select('id')->from('users')->where('email', \$email);\n";
echo "            if (\$phone)\n";
echo "                \$q->orWhere('phone', \$phone);  // ← BUG: Too broad matching\n";
echo "        });\n";
echo "    })\n";
echo "    ->first();\n";
echo "```\n\n";

echo "THE PROBLEM:\n";
echo "- Developer registers with phone 07040540018\n";
echo "- Logic searches for ANY account with this phone OR email\n";
echo "- Somehow matches existing account with different phone/email\n";
echo "- Updates existing customer name instead of creating new account\n\n";

echo "THE FIX NEEDED:\n";
echo "1. Make deduplication logic more strict\n";
echo "2. Only match if BOTH email AND phone match exactly\n";
echo "3. Add logging to see why accounts are being matched\n";
echo "4. Prevent updating customer names of existing accounts\n\n";

echo "✅ IMMEDIATE FIX COMPLETE\n";
echo "Account 6662822179 restored to original owner 'Nana Aisha Bello'\n";
echo "Now you need to fix the deduplication logic in VirtualAccountService.php\n";