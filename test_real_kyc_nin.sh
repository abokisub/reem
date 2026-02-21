#!/bin/bash

# ============================================================================
# Real KYC NIN Verification Test Script
# ============================================================================
# This script tests NIN verification with real EaseID API
# NIN to test: 35257106066
# ============================================================================

echo "============================================"
echo "  REAL KYC NIN VERIFICATION TEST"
echo "============================================"
echo ""

# Your API credentials
API_KEY="7db8dbb3991382487a1fc388a05d96a7139d92ba"
SECRET_KEY="d8a3151a8993c157c1a4ee5ecda8983107004b1fbdec3f55011f1b7ee4e63f517b7403e021e96995e4c5c90aab834ea70845672dbe6ccf7910dda45c"
BUSINESS_ID="3450968aa027e86e3ff5b0169dc17edd7694a846"
NIN="35257106066"

echo "📋 Test Configuration:"
echo "   NIN: $NIN"
echo "   API Endpoint: https://app.pointwave.ng/api/v1/kyc/verify-nin"
echo ""

# Step 1: Check wallet balance BEFORE
echo "Step 1: Checking wallet balance BEFORE verification..."
echo "----------------------------------------"

BALANCE_BEFORE=$(curl -s -X GET "https://app.pointwave.ng/api/v1/balance" \
  -H "Authorization: Bearer $SECRET_KEY" \
  -H "x-api-key: $API_KEY" \
  -H "x-business-id: $BUSINESS_ID" \
  -H "Content-Type: application/json" | grep -o '"balance":[0-9.]*' | cut -d':' -f2)

echo "   Wallet Balance: ₦$BALANCE_BEFORE"
echo ""

# Step 2: Verify NIN
echo "Step 2: Verifying NIN with EaseID API..."
echo "----------------------------------------"

RESPONSE=$(curl -s -X POST "https://app.pointwave.ng/api/v1/kyc/verify-nin" \
  -H "Authorization: Bearer $SECRET_KEY" \
  -H "x-api-key: $API_KEY" \
  -H "x-business-id: $BUSINESS_ID" \
  -H "Idempotency-Key: test_nin_$(date +%s)_$$" \
  -H "Content-Type: application/json" \
  -d "{\"nin\":\"$NIN\"}")

echo "   API Response:"
echo "$RESPONSE" | python3 -m json.tool 2>/dev/null || echo "$RESPONSE"
echo ""

# Extract status and charge info
STATUS=$(echo "$RESPONSE" | grep -o '"status":[^,]*' | cut -d':' -f2 | tr -d ' ')
CHARGED=$(echo "$RESPONSE" | grep -o '"charged":[^,]*' | cut -d':' -f2 | tr -d ' ')
CHARGE_AMOUNT=$(echo "$RESPONSE" | grep -o '"charge_amount":[0-9.]*' | cut -d':' -f2)
TX_REF=$(echo "$RESPONSE" | grep -o '"transaction_reference":"[^"]*"' | cut -d'"' -f4)

echo "   Status: $STATUS"
echo "   Charged: $CHARGED"
echo "   Charge Amount: ₦$CHARGE_AMOUNT"
echo "   Transaction Reference: $TX_REF"
echo ""

# Step 3: Check wallet balance AFTER
echo "Step 3: Checking wallet balance AFTER verification..."
echo "----------------------------------------"

sleep 2  # Wait for transaction to process

BALANCE_AFTER=$(curl -s -X GET "https://app.pointwave.ng/api/v1/balance" \
  -H "Authorization: Bearer $SECRET_KEY" \
  -H "x-api-key: $API_KEY" \
  -H "x-business-id: $BUSINESS_ID" \
  -H "Content-Type: application/json" | grep -o '"balance":[0-9.]*' | cut -d':' -f2)

echo "   Wallet Balance: ₦$BALANCE_AFTER"
echo ""

# Calculate difference
if [ ! -z "$BALANCE_BEFORE" ] && [ ! -z "$BALANCE_AFTER" ]; then
    DIFFERENCE=$(echo "$BALANCE_BEFORE - $BALANCE_AFTER" | bc)
    echo "   Balance Difference: ₦$DIFFERENCE"
    echo ""
fi

# Step 4: Verify transaction was created
echo "Step 4: Checking transaction record..."
echo "----------------------------------------"
echo ""
echo "Run this command to check the transaction:"
echo ""
echo "php artisan tinker"
echo ">>> DB::table('transactions')->where('category', 'kyc_charge')->latest()->first();"
echo ">>> exit"
echo ""

# Summary
echo "============================================"
echo "  TEST SUMMARY"
echo "============================================"
echo ""

if [ "$STATUS" = "true" ]; then
    echo "✅ NIN Verification: SUCCESS"
else
    echo "❌ NIN Verification: FAILED"
fi

if [ "$CHARGED" = "true" ]; then
    echo "✅ Charge Deducted: YES (₦$CHARGE_AMOUNT)"
else
    echo "ℹ️  Charge Deducted: NO (Free or Cached)"
fi

if [ ! -z "$BALANCE_BEFORE" ] && [ ! -z "$BALANCE_AFTER" ]; then
    echo "✅ Wallet Balance Updated: ₦$BALANCE_BEFORE → ₦$BALANCE_AFTER"
fi

if [ ! -z "$TX_REF" ]; then
    echo "✅ Transaction Reference: $TX_REF"
fi

echo ""
echo "============================================"
echo ""
echo "Next Steps:"
echo "1. Check transaction in database (see command above)"
echo "2. Check transaction in admin dashboard"
echo "3. Check transaction in company dashboard"
echo "4. Verify NIN data was returned correctly"
echo ""
echo "============================================"
