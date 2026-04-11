// Check your current fee settings

echo "\n=== CURRENT FEE CONFIGURATION ===\n\n";

// Check service_charges table
$charges = DB::table('service_charges')
    ->where('service_category', 'payment')
    ->where('is_active', true)
    ->get();

if ($charges->count() > 0) {
    echo "Service Charges:\n";
    foreach ($charges as $charge) {
        echo sprintf(
            "- %s: %s %s%s\n",
            $charge->display_name,
            $charge->charge_type,
            $charge->charge_value,
            $charge->charge_cap ? " (capped at ₦" . $charge->charge_cap . ")" : ""
        );
    }
} else {
    echo "No service charges configured!\n";
}

echo "\n";

// Check company fee settings
$companyFees = DB::table('company_fee_settings')->get();
if ($companyFees->count() > 0) {
    echo "Company Fee Settings:\n";
    foreach ($companyFees as $fee) {
        $company = DB::table('companies')->where('id', $fee->company_id)->first();
        echo sprintf(
            "Company: %s | Model: %s | Flat: ₦%s | Percent: %s%% | Cap: ₦%s\n",
            $company->name ?? 'Unknown',
            $fee->fee_model,
            $fee->flat_fee,
            $fee->percentage_fee,
            $fee->cap_amount ?? 'None'
        );
    }
} else {
    echo "No company-specific fees configured.\n";
}

echo "\n=== PROBLEM ANALYSIS ===\n";
echo "Your costs per transaction:\n";
echo "- PalmPay: 0.5% (capped at ₦500)\n";
echo "- Transfer: ₦25 flat\n";
echo "\nFor a ₦10,000 transaction:\n";
echo "- PalmPay fee: ₦50 (0.5%)\n";
echo "- Transfer fee: ₦25\n";
echo "- Total cost: ₦75\n";
echo "- You need to charge AT LEAST ₦75 to break even\n";
echo "- Recommended: ₦100 (1%) for 33% profit margin\n";
