#!/bin/bash

echo "=========================================="
echo "Building Frontend - Receipt Download Fix"
echo "=========================================="
echo ""

# Navigate to frontend directory
cd frontend

echo "Step 1: Installing dependencies..."
npm install --legacy-peer-deps

echo ""
echo "Step 2: Building production bundle..."
npm run build

echo ""
echo "=========================================="
echo "Build Complete!"
echo "=========================================="
echo ""
echo "Next Steps:"
echo "1. Upload the 'frontend/build' folder to your cPanel server"
echo "2. Replace the existing 'build' folder at: /home/your_username/public_html/build"
echo "3. Clear browser cache (Ctrl+Shift+R)"
echo "4. Test the download button on any transaction receipt"
echo ""
echo "The download button will now:"
echo "  - Show a download icon (not share icon)"
echo "  - Open browser print dialog"
echo "  - Allow saving as PDF with clean formatting"
echo "  - Hide all UI elements except the receipt"
echo ""
