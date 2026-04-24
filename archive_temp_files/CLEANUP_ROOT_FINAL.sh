#!/bin/bash

# Final cleanup script for Laravel root directory
# This moves all temporary files, test scripts, and documentation to archive

echo "=========================================="
echo "Laravel Root Directory Final Cleanup"
echo "=========================================="
echo ""

# Create archive directory if it doesn't exist
if [ ! -d "archive_temp_files" ]; then
    mkdir -p archive_temp_files
    echo "✓ Created archive_temp_files directory"
fi

# Files to archive
FILES_TO_ARCHIVE=(
    # SQL dumps
    "aboksdfs_pointwave.sql"
    
    # Documentation files
    "ADMIN_DASHBOARD_REDESIGN_COMPLETE.md"
    "ADMIN_DASHBOARD_REDESIGN_PLAN.md"
    "ADMIN_DASHBOARD_UPGRADE_SUMMARY.md"
    "PAYMENT_GATEWAY_UPGRADE_PLAN.md"
    "UPGRADE_QUICK_START.md"
    "VA_SAFETY_CHECK_GUIDE.md"
    "VIRTUAL_ACCOUNT_FIX_INSTRUCTIONS.md"
    "VIRTUAL_ACCOUNT_FIX_SUMMARY.md"
    
    # Shell scripts (except this one)
    "CHECK_ALL_COMPANIES.sh"
    "CLEANUP_ROOT_FILES.sh"
    
    # PHP test/check scripts
    "CHECK_ALL_COMPANIES_VAS.php"
    "GENERATE_VA_SAFETY_REPORT.php"
)

# Files to KEEP (operational)
KEEP_FILES=(
    "REBUILD_AND_DEPLOY.sh"
    "server.php"
)

echo "Files that will be KEPT (operational):"
for file in "${KEEP_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo "  ✓ $file"
    fi
done
echo ""

echo "Moving files to archive_temp_files/..."
echo ""

MOVED_COUNT=0
SKIPPED_COUNT=0

for file in "${FILES_TO_ARCHIVE[@]}"; do
    if [ -f "$file" ]; then
        mv "$file" archive_temp_files/
        echo "  ✓ Moved: $file"
        ((MOVED_COUNT++))
    else
        echo "  ⊘ Skipped (not found): $file"
        ((SKIPPED_COUNT++))
    fi
done

echo ""
echo "=========================================="
echo "Cleanup Summary"
echo "=========================================="
echo "Files moved to archive: $MOVED_COUNT"
echo "Files not found: $SKIPPED_COUNT"
echo ""
echo "✓ Root directory cleaned!"
echo ""
echo "Operational files kept in root:"
echo "  - REBUILD_AND_DEPLOY.sh (deployment script)"
echo "  - server.php (Laravel built-in server)"
echo "  - CLEANUP_ROOT_FINAL.sh (this script)"
echo ""
echo "All archived files are in: archive_temp_files/"
echo "=========================================="
