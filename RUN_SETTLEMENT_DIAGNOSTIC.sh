#!/bin/bash

echo "=========================================="
echo "Settlement Receipt Diagnostic"
echo "=========================================="
echo ""

echo "1. Pulling latest code from GitHub..."
git pull origin main

echo ""
echo "2. Running diagnostic script..."
php debug_settlement_receipt_final.php

echo ""
echo "✅ Done! Review the output above to see what's happening."
