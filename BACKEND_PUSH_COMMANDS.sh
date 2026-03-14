#!/bin/bash

# Backend-Only Push to GitHub
# This script pushes only backend changes, excluding frontend

echo "🚀 Preparing Backend-Only Push to GitHub..."

# Add only backend files
git add app/
git add database/
git add routes/
git add config/
git add bootstrap/
git add storage/
git add public/.htaccess
git add composer.json
git add composer.lock
git add artisan
git add .env.example

# Add documentation files
git add *.md

# Add any PHP scripts
git add *.php

# Commit the changes
git commit -m "Backend Updates: Airtime System Fix, Settlement System, Virtual Account Charges, UI Improvements

- Fixed airtime purchase system with proper error handling
- Added missing exam_id and airtime tables
- Fixed admin login issue (admin -> ADMIN)
- Configured virtual account charges to 0.8%
- Improved settlement system documentation
- Hidden UI sections as requested (Calculator, Pricing, Transaction History, Bill Payments)
- Enhanced error handling with ApiResponseTrait
- Updated network data and airtime discount configurations
- Fixed database enum values and column references"

# Push to GitHub
git push origin main

echo "✅ Backend changes pushed to GitHub successfully!"