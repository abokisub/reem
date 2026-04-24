#!/bin/bash

# Comprehensive Virtual Account Safety Check
# Checks all companies for missing VAs and issues

echo "Starting comprehensive safety check..."
echo ""

php CHECK_ALL_COMPANIES_VAS.php

echo ""
echo "Check complete! Review the output above for any issues."
echo ""
echo "If issues found, you can fix them with:"
echo "  php artisan kyc:regenerate-missing-accounts --company-id=<ID> --assign-fresh-kyc"
