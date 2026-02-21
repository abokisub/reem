#!/bin/bash

echo "🚀 Deploying All API V1 Bug Fixes..."
echo ""

# Push to GitHub
echo "📤 Pushing to GitHub..."
git add .
git commit -m "Fix: API V1 - LIST VAs, DELETE VA, add Banks & Balance endpoints"
git push origin main

echo ""
echo "✅ Pushed to GitHub"
echo ""
echo "📋 Next Steps (Run on Server):"
echo ""
echo "ssh into server and run:"
echo ""
echo "cd /home/aboksdfs/app.pointwave.ng"
echo "git pull origin main"
echo "php artisan route:clear"
echo "php artisan config:clear"
echo "php artisan cache:clear"
echo "php artisan optimize"
echo ""
echo "✅ Done! Test the endpoints."
