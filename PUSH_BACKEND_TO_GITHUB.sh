#!/bin/bash

echo "=========================================="
echo "PUSHING BACKEND TO GITHUB"
echo "=========================================="
echo ""

# Check git status
echo "1. Checking git status..."
git status
echo ""

# Verify .gitignore excludes frontend and landing page
echo "2. Verifying .gitignore excludes frontend/LandingPage..."
if grep -q "/frontend/" .gitignore && grep -q "/LandingPage/" .gitignore; then
    echo "✓ .gitignore properly configured"
else
    echo "⚠ Warning: .gitignore may not exclude frontend/LandingPage"
fi
echo ""

# Add backend files only
echo "3. Adding backend files to git..."
git add app/
git add database/migrations/
git add routes/
git add config/
git add .gitignore
git add check_transaction_customer.php
echo "✓ Backend files staged"
echo ""

# Show what will be committed
echo "4. Files to be committed:"
git status --short
echo ""

# Commit changes
echo "5. Committing changes..."
read -p "Enter commit message (or press Enter for default): " commit_msg
if [ -z "$commit_msg" ]; then
    commit_msg="Fix production errors: undefined property, duplicate refund, missing column"
fi
git commit -m "$commit_msg"
echo "✓ Changes committed"
echo ""

# Push to GitHub
echo "6. Pushing to GitHub..."
read -p "Push to which branch? (default: main): " branch
if [ -z "$branch" ]; then
    branch="main"
fi

git push origin "$branch"
echo "✓ Pushed to GitHub"
echo ""

echo "=========================================="
echo "GITHUB PUSH COMPLETE!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. SSH to your server"
echo "2. Pull the latest changes: git pull origin $branch"
echo "3. Run: bash DEPLOY_BACKEND_FIXES.sh"
echo ""
