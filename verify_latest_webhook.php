<?php

// Latest webhook from Amtpay's logs
$payload = '{"event":"payment.success","event_id":"47b8d726-07a1-4d19-8dff-95c1625bdc56","timestamp":"2026-02-25T15:14:13+01:00","data":{"transaction_id":"txn_699f03b5935ba90377","amount":"100.00","fee":"0.60","net_amount":"99.40","reference":"REF699F03B5935CA8626","status":"success","customer":{"account_number":"6604877046","sender_name":"ABOKI TELECOMMUNICATION SERVICES","sender_account":"7040540018","sender_bank":"OPAY"},"narration":"Transfer from ABOKI TELECOMMUNICATION SERVICES","created_at":"2026-02-25T15:14:13+01:00"}}';

$secret = 'whsec_383b656ab3e08d06598c6cec6f429d07a43047030265e45ff70b89d04f0f6e24';

echo "=== Verifying Latest Webhook ===\n\n";
echo "PointWave sent: b2a7a55e9e2a5a5ab469add2a10618c579413a12f94fa37fddd0dc657f893e86\n";
echo "Amtpay calculated: 848e96f6082baea4c412fd07a805cfab1ac3bb4c8876829b75311cb8393fb5b4\n\n";

// Calculate with raw payload (what PointWave sends)
$correctSig = hash_hmac('sha256', $payload, $secret);
echo "Correct signature (with raw payload): {$correctSig}\n\n";

if ($correctSig === 'b2a7a55e9e2a5a5ab469add2a10618c579413a12f94fa37fddd0dc657f893e86') {
    echo "✅ PointWave is sending CORRECT signature!\n";
} else {
    echo "❌ PointWave signature doesn't match\n";
}

if ($correctSig === '848e96f6082baea4c412fd07a805cfab1ac3bb4c8876829b75311cb8393fb5b4') {
    echo "✅ Amtpay is calculating correctly!\n";
} else {
    echo "❌ Amtpay is STILL using wrong method (json_encode)\n";
    echo "\nAMTPAY NEEDS TO FIX THEIR CODE:\n";
    echo "They must change: \$payload = json_encode(\$request->all());\n";
    echo "To: \$payload = \$request->getContent();\n";
}
