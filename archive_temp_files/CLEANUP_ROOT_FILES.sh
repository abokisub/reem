#!/bin/bash

# Cleanup Script - Move temporary files to archive
# This keeps your Laravel root clean while preserving files for reference

echo "Creating archive directory..."
mkdir -p archive_temp_files

echo "Moving test scripts..."
mv -v blacklist_kobopoint_old_kyc.php archive_temp_files/ 2>/dev/null
mv -v calculate_profit_loss.php archive_temp_files/ 2>/dev/null
mv -v check_current_fees.php archive_temp_files/ 2>/dev/null
mv -v check_kobopoint_kyc.php archive_temp_files/ 2>/dev/null
mv -v check_missing_kobopoint_accounts.php archive_temp_files/ 2>/dev/null
mv -v check_missing_va.php archive_temp_files/ 2>/dev/null
mv -v cleanup_orphaned_company_users.php archive_temp_files/ 2>/dev/null
mv -v detailed_profit_analysis.php archive_temp_files/ 2>/dev/null
mv -v fix_kobopoint_now.php archive_temp_files/ 2>/dev/null
mv -v reprocess_failed_webhooks.php archive_temp_files/ 2>/dev/null
mv -v setup_kyc_pool.php archive_temp_files/ 2>/dev/null
mv -v test_api_endpoint.php archive_temp_files/ 2>/dev/null
mv -v test_card_checkout.php archive_temp_files/ 2>/dev/null
mv -v test_dashboard_api.php archive_temp_files/ 2>/dev/null
mv -v test_dynamic_account.php archive_temp_files/ 2>/dev/null
mv -v test_settlement.php archive_temp_files/ 2>/dev/null
mv -v tinker_profit_check.php archive_temp_files/ 2>/dev/null
mv -v total_profit_analysis.php archive_temp_files/ 2>/dev/null

echo "Moving emergency fix scripts..."
mv -v FINAL_FIX_2_USERS.php archive_temp_files/ 2>/dev/null
mv -v FIX_OYITIPAY_FINAL.php archive_temp_files/ 2>/dev/null
mv -v FIX_OYITIPAY_MANUAL.php archive_temp_files/ 2>/dev/null
mv -v FIX_REMAINING_2_USERS.php archive_temp_files/ 2>/dev/null
mv -v emergency_fix_kobopoint.sh archive_temp_files/ 2>/dev/null
mv -v EMERGENCY_KYC_FIX.sh archive_temp_files/ 2>/dev/null
mv -v FIX_MISSING_VIRTUAL_ACCOUNTS.sh archive_temp_files/ 2>/dev/null
mv -v PUSH_VIRTUAL_ACCOUNT_FIX.sh archive_temp_files/ 2>/dev/null

echo "Moving shell scripts..."
mv -v check_profit.sh archive_temp_files/ 2>/dev/null
mv -v BACKEND_PUSH_COMMANDS.sh archive_temp_files/ 2>/dev/null
mv -v DEPLOY_TO_SERVER.sh archive_temp_files/ 2>/dev/null

echo "Moving documentation files..."
mv -v API_ENDPOINTS_STATUS.md archive_temp_files/ 2>/dev/null
mv -v BACKEND_API_IMPLEMENTATION_COMPLETE.md archive_temp_files/ 2>/dev/null
mv -v BACKEND_SAFETY_VERIFICATION_REPORT.md archive_temp_files/ 2>/dev/null
mv -v COMPANY_VA_STATUS.md archive_temp_files/ 2>/dev/null
mv -v IMPLEMENTATION_COMPLETE.md archive_temp_files/ 2>/dev/null
mv -v MOBILE_APP_FINAL_STATUS.md archive_temp_files/ 2>/dev/null
mv -v MOBILE_APP_INTEGRATION_PLAN.md archive_temp_files/ 2>/dev/null
mv -v MOBILE_FINAL_REPORT.md archive_temp_files/ 2>/dev/null
mv -v MOBILE_FIX_INSTRUCTIONS.md archive_temp_files/ 2>/dev/null
mv -v MOBILE_IMPLEMENTATION_STATUS.md archive_temp_files/ 2>/dev/null
mv -v NOMBA_API_REFERENCE.md archive_temp_files/ 2>/dev/null
mv -v NOMBA_MASTER_REFERENCE.md archive_temp_files/ 2>/dev/null
mv -v PROJECT_SUMMARY.md archive_temp_files/ 2>/dev/null
mv -v QUICK_FIX_REFERENCE.md archive_temp_files/ 2>/dev/null
mv -v SETTLEMENT_CRON_REMINDER.md archive_temp_files/ 2>/dev/null
mv -v SETTLEMENT_SYSTEM_FIX.md archive_temp_files/ 2>/dev/null

echo ""
echo "✅ Cleanup complete!"
echo ""
echo "Files moved to: archive_temp_files/"
echo ""
echo "KEPT (useful for operations):"
echo "  - CHECK_ALL_COMPANIES_VAS.php"
echo "  - CHECK_ALL_COMPANIES.sh"
echo "  - GENERATE_VA_SAFETY_REPORT.php"
echo "  - REBUILD_AND_DEPLOY.sh"
echo "  - server.php (Laravel default)"
echo ""
echo "To restore files: mv archive_temp_files/* ."
echo "To delete archive: rm -rf archive_temp_files/"
