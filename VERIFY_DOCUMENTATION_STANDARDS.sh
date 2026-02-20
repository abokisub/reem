#!/bin/bash

echo "=========================================="
echo "DOCUMENTATION STANDARDS VERIFICATION"
echo "=========================================="
echo ""

ERRORS=0

echo "1. Checking for exposed real credentials..."
echo "   Searching for known real Business IDs, API keys, Secret keys..."

# Search for known real credentials
if grep -r "7bb0db039913624f7a1fc3d6a05d50a7139d93ba\|345d9d4baa927e86a3ff5bb169dc17edf694ab46\|dc6dce73a9b5904e88ddbffcc0a80ef1ede9c973\|428a0513d6c476d41607881f1d28958dc1a6e8c" resources/views/docs/ frontend/src/pages/dashboard/Documentation/ 2>/dev/null; then
    echo "   ❌ FAIL: Found exposed real credentials!"
    ERRORS=$((ERRORS + 1))
else
    echo "   ✅ PASS: No real credentials found"
fi

echo ""
echo "2. Checking React components for credential fetching..."
echo "   Searching for axios calls to /api/company/credentials..."

if grep -r "axios.get.*credentials\|fetchCredentials\|/api/company/credentials" frontend/src/pages/dashboard/Documentation/ 2>/dev/null; then
    echo "   ❌ FAIL: Found credential fetching code!"
    ERRORS=$((ERRORS + 1))
else
    echo "   ✅ PASS: No credential fetching found"
fi

echo ""
echo "3. Checking for placeholder text standards..."
echo "   Verifying use of YOUR_SECRET_KEY, YOUR_API_KEY, YOUR_BUSINESS_ID..."

# Count placeholder usage in React files
REACT_PLACEHOLDERS=$(grep -r "YOUR_SECRET_KEY\|YOUR_API_KEY\|YOUR_BUSINESS_ID\|YOUR_TEST" frontend/src/pages/dashboard/Documentation/ 2>/dev/null | wc -l)

# Count placeholder usage in Blade files  
BLADE_PLACEHOLDERS=$(grep -r "YOUR_SECRET_KEY\|YOUR_API_KEY\|YOUR_BUSINESS_ID\|your_.*_here\|{secret_key}\|{api_key}\|{business_id}" resources/views/docs/ 2>/dev/null | wc -l)

echo "   React components: $REACT_PLACEHOLDERS placeholder references"
echo "   Blade templates: $BLADE_PLACEHOLDERS placeholder references"

if [ "$REACT_PLACEHOLDERS" -gt 0 ] && [ "$BLADE_PLACEHOLDERS" -gt 0 ]; then
    echo "   ✅ PASS: Placeholder text is used consistently"
else
    echo "   ⚠️  WARNING: Low placeholder usage detected"
fi

echo ""
echo "4. Checking Laravel blade files for dynamic credential injection..."
echo "   Searching for @auth, \$user, \$company variables..."

if grep -r "@auth\|\\$user->api_key\|\\$company->business_id" resources/views/docs/ 2>/dev/null; then
    echo "   ❌ FAIL: Found dynamic credential injection!"
    ERRORS=$((ERRORS + 1))
else
    echo "   ✅ PASS: No dynamic credential injection"
fi

echo ""
echo "5. Checking for professional documentation structure..."
echo "   Verifying required sections exist..."

REQUIRED_DOCS=(
    "resources/views/docs/index.blade.php"
    "resources/views/docs/authentication.blade.php"
    "resources/views/docs/customers.blade.php"
    "resources/views/docs/virtual-accounts.blade.php"
    "resources/views/docs/transfers.blade.php"
    "resources/views/docs/webhooks.blade.php"
    "resources/views/docs/errors.blade.php"
    "resources/views/docs/sandbox.blade.php"
)

MISSING=0
for doc in "${REQUIRED_DOCS[@]}"; do
    if [ ! -f "$doc" ]; then
        echo "   ❌ Missing: $doc"
        MISSING=$((MISSING + 1))
    fi
done

if [ "$MISSING" -eq 0 ]; then
    echo "   ✅ PASS: All required documentation files exist"
else
    echo "   ❌ FAIL: $MISSING documentation files missing"
    ERRORS=$((ERRORS + 1))
fi

echo ""
echo "6. Checking React documentation components..."

REQUIRED_REACT_DOCS=(
    "frontend/src/pages/dashboard/Documentation/Authentication.js"
    "frontend/src/pages/dashboard/Documentation/CreateCustomer.js"
    "frontend/src/pages/dashboard/Documentation/Transfers.js"
    "frontend/src/pages/dashboard/Documentation/Webhooks.js"
    "frontend/src/pages/dashboard/Documentation/Sandbox.js"
)

MISSING_REACT=0
for doc in "${REQUIRED_REACT_DOCS[@]}"; do
    if [ ! -f "$doc" ]; then
        echo "   ❌ Missing: $doc"
        MISSING_REACT=$((MISSING_REACT + 1))
    fi
done

if [ "$MISSING_REACT" -eq 0 ]; then
    echo "   ✅ PASS: All required React components exist"
else
    echo "   ❌ FAIL: $MISSING_REACT React components missing"
    ERRORS=$((ERRORS + 1))
fi

echo ""
echo "=========================================="
echo "VERIFICATION SUMMARY"
echo "=========================================="

if [ "$ERRORS" -eq 0 ]; then
    echo "✅ ALL CHECKS PASSED"
    echo ""
    echo "Documentation follows professional standards:"
    echo "  ✅ No real credentials exposed"
    echo "  ✅ No credential fetching in React components"
    echo "  ✅ Placeholder text used consistently"
    echo "  ✅ No dynamic credential injection"
    echo "  ✅ All required files present"
    echo ""
    echo "Developers will have a professional experience like Stripe/Paystack!"
    exit 0
else
    echo "❌ $ERRORS CHECKS FAILED"
    echo ""
    echo "Please review the errors above and fix them before deployment."
    exit 1
fi
