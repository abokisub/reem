#!/bin/bash

echo "=========================================="
echo "DEPLOYING DEBUG SCRIPT AND TESTING RECEIPT"
echo "=========================================="
echo ""

# Step 1: Push to GitHub
echo "Step 1: Pushing debug script to GitHub..."
git add debug_receipt_generation.php
git commit -m "Add receipt generation debug script"
git push origin main

echo ""
echo "Step 2: On your server, run these commands:"
echo ""
echo "cd /home/aboksdfs/app.pointwave.ng"
echo "git pull origin main"
echo "php debug_receipt_generation.php"
echo ""
echo "This will show us exactly what's happening when the receipt is generated."
echo ""
