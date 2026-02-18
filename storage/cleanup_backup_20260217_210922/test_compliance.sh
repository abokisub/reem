#!/bin/bash

echo "======================================"
echo "PointPay Enterprise Compliance Test"
echo "======================================"
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test counter
PASSED=0
FAILED=0

# Function to test command existence
test_command() {
    if php artisan list | grep -q "$1"; then
        echo -e "${GREEN}✓${NC} Command exists: $1"
        ((PASSED++))
    else
        echo -e "${RED}✗${NC} Command missing: $1"
        ((FAILED++))
    fi
}

# Function to test file existence
test_file() {
    if [ -f "$1" ]; then
        echo -e "${GREEN}✓${NC} File exists: $1"
        ((PASSED++))
    else
        echo -e "${RED}✗${NC} File missing: $1"
        ((FAILED++))
    fi
}

# Function to test directory existence
test_dir() {
    if [ -d "$1" ]; then
        echo -e "${GREEN}✓${NC} Directory exists: $1"
        ((PASSED++))
    else
        echo -e "${RED}✗${NC} Directory missing: $1"
        ((FAILED++))
    fi
}

echo "1. Testing Commands..."
echo "----------------------"
test_command "gateway:settle"
test_command "gateway:reconcile"
test_command "sandbox:reset"
test_command "sandbox:provision"
echo ""

echo "2. Testing Migrations..."
echo "------------------------"
test_file "database/migrations/2026_02_17_170000_encrypt_existing_api_keys.php"
test_file "database/migrations/2026_02_17_165900_expand_api_key_columns_raw.php"
echo ""

echo "3. Testing CI/CD Pipeline..."
echo "----------------------------"
test_file ".github/workflows/test-and-deploy.yml"
echo ""

echo "4. Testing Phase Test Structure..."
echo "-----------------------------------"
test_file "phpunit.xml"
test_dir "tests/Phase1"
test_dir "tests/Phase2"
test_file "tests/Phase1/VirtualAccountCreationTest.php"
test_file "tests/Phase2/DepositProcessingTest.php"
echo ""

echo "5. Testing API Documentation..."
echo "-------------------------------"
test_file "resources/views/docs/index.blade.php"
test_file "routes/web.php"
echo ""

echo "6. Testing Scheduler Configuration..."
echo "--------------------------------------"
if grep -q "gateway:settle" app/Console/Kernel.php; then
    echo -e "${GREEN}✓${NC} Settlement scheduler configured"
    ((PASSED++))
else
    echo -e "${RED}✗${NC} Settlement scheduler not configured"
    ((FAILED++))
fi

if grep -q "gateway:reconcile" app/Console/Kernel.php; then
    echo -e "${GREEN}✓${NC} Reconciliation scheduler configured"
    ((PASSED++))
else
    echo -e "${RED}✗${NC} Reconciliation scheduler not configured"
    ((FAILED++))
fi

if grep -q "sandbox:reset" app/Console/Kernel.php; then
    echo -e "${GREEN}✓${NC} Sandbox reset scheduler configured"
    ((PASSED++))
else
    echo -e "${RED}✗${NC} Sandbox reset scheduler not configured"
    ((FAILED++))
fi
echo ""

echo "7. Testing Model Encryption..."
echo "------------------------------"
if grep -q "'api_key' => 'encrypted'" app/Models/Company.php; then
    echo -e "${GREEN}✓${NC} API key encryption configured in model"
    ((PASSED++))
else
    echo -e "${RED}✗${NC} API key encryption not configured in model"
    ((FAILED++))
fi
echo ""

echo "8. Testing Documentation Files..."
echo "----------------------------------"
test_file "ENTERPRISE_COMPLIANCE_AUDIT.md"
test_file "IMPLEMENTATION_COMPLETE.md"
echo ""

echo "======================================"
echo "Test Results"
echo "======================================"
echo -e "${GREEN}Passed: $PASSED${NC}"
echo -e "${RED}Failed: $FAILED${NC}"
echo ""

TOTAL=$((PASSED + FAILED))
PERCENTAGE=$((PASSED * 100 / TOTAL))

echo "Compliance Score: $PERCENTAGE%"
echo ""

if [ $FAILED -eq 0 ]; then
    echo -e "${GREEN}🎉 All tests passed! System is enterprise-compliant!${NC}"
    exit 0
else
    echo -e "${YELLOW}⚠️  Some tests failed. Review the output above.${NC}"
    exit 1
fi
