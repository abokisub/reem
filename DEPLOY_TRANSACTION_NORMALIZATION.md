# Transaction Normalization - Complete Deployment Guide

## Date: February 21, 2026

This guide walks you through deploying the transaction normalization backend changes to your production server and building the frontend.

---

## 📋 WHAT WAS IMPLEMENTED

### Backend Changes
✅ TransactionReconciliationService - Automatic status reconciliation
✅ ProcessTransactionReconciliation command - Runs every 5 minutes
✅ TransactionValidator - Validates all new transactions
✅ Transaction model updates - Auto-generates required fields
✅ TransactionStatusLog model - Audit trail for status changes
✅ Phase 1 migration - Adds nullable columns (session_id, transaction_ref, etc.)
✅ Audit log table migration - Tracks all status changes

### What This Fixes
- ✅ transactions.status is now the ONLY canonical status source
- ✅ Automatic reconciliation of stale transactions every 5 minutes
- ✅ Complete audit trail for all status changes
- ✅ Settlement status derived from transaction status (not independent)
- ✅ No more N/A values in new transactions

---

## 🚀 STEP 1: DEPLOY BACKEND TO SERVER

### 1.1 Connect to Server
```bash
ssh aboksdfs@server350
cd app.pointwave.ng
```

### 1.2 Pull Latest Changes from GitHub
```bash
git pull origin main
```

**Expected Output:**
```
remote: Enumerating objects: ...
Updating 01af2cc..2e72763
Fast-forward
 app/Console/Commands/ProcessTransactionReconciliation.php | 89 ++++
 app/Models/Transaction.php                                | 130 ++++-
 app/Models/TransactionStatusLog.php                       | 57 ++
 app/Services/TransactionReconciliationService.php         | 420 +++++++++++++
 app/Validators/TransactionValidator.php                   | 187 ++++++
 app/Validators/ValidationResult.php                       | 73 +++
 database/migrations/2026_02_21_000001_...                 | 153 +++++
 database/migrations/2026_02_21_000004_...                 | 64 ++
 specs/transaction-normalization/...                       | (multiple files)
 TRANSACTION_NORMALIZATION_BACKEND_COMPLETE.md             | 427 +++++++++++++
 16 files changed, 5635 insertions(+)
```

### 1.3 Run Phase 1 Migration (Adds Nullable Columns)
```bash
php artisan migrate --path=database/migrations/2026_02_21_000001_phase1_add_transaction_normalization_columns.php
```

**Expected Output:**
```
Migrating: 2026_02_21_000001_phase1_add_transaction_normalization_columns
Migrated:  2026_02_21_000001_phase1_add_transaction_normalization_columns (XXX.XXms)
```

### 1.4 Run Audit Log Table Migration
```bash
php artisan migrate --path=database/migrations/2026_02_21_000004_create_transaction_status_logs_table.php
```

**Expected Output:**
```
Migrating: 2026_02_21_000004_create_transaction_status_logs_table
Migrated:  2026_02_21_000004_create_transaction_status_logs_table (XXX.XXms)
```

### 1.5 Verify Migrations
```bash
php artisan migrate:status | grep "2026_02_21"
```

**Expected Output:**
```
| Yes  | 2026_02_21_000001_phase1_add_transaction_normalization_columns | XX |
| No   | 2026_02_21_000002_phase2_backfill_transaction_data             |    |
| No   | 2026_02_21_000003_phase3_enforce_transaction_constraints       |    |
| Yes  | 2026_02_21_000004_create_transaction_status_logs_table         | XX |
```

### 1.6 Clear Laravel Caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

### 1.7 Verify Scheduled Command is Registered
```bash
php artisan schedule:list | grep reconcile
```

**Expected Output:**
```
*/5 * * * * php artisan transactions:reconcile
```

### 1.8 Test Reconciliation Command Manually
```bash
php artisan transactions:reconcile
```

**Expected Output:**
```
Starting scheduled transaction reconciliation...
Found 0 stale transactions for reconciliation
Scheduled reconciliation completed
Total checked: 0
Reconciled: 0
Timeouts: 0
Failed: 0
```

---

## ✅ STEP 2: VERIFY BACKEND DEPLOYMENT

### 2.1 Check Database Schema
```bash
php artisan tinker
```

Then run:
```php
DB::select("SHOW COLUMNS FROM transactions WHERE Field IN ('session_id', 'transaction_ref', 'transaction_type', 'settlement_status', 'net_amount')");
exit
```

**Expected:** You should see all 5 new columns listed.

### 2.2 Check Audit Log Table
```bash
php artisan tinker
```

Then run:
```php
DB::select("SHOW TABLES LIKE 'transaction_status_logs'");
exit
```

**Expected:** Table should exist.

### 2.3 Test Transaction Model
```bash
php artisan tinker
```

Then run:
```php
$transaction = new App\Models\Transaction();
$transaction->company_id = 1;
$transaction->amount = 1000;
$transaction->fee = 50;
$transaction->status = 'pending';
$transaction->transaction_type = 'va_deposit';
// Check auto-generation
echo "Session ID: " . ($transaction->session_id ?? 'NOT SET') . "\n";
echo "Transaction Ref: " . ($transaction->transaction_ref ?? 'NOT SET') . "\n";
echo "Net Amount: " . ($transaction->net_amount ?? 'NOT SET') . "\n";
echo "Settlement Status: " . ($transaction->settlement_status ?? 'NOT SET') . "\n";
exit
```

**Expected:** All fields should show "NOT SET" because boot() only runs on save, but the model should load without errors.

---

## 🎨 STEP 3: BUILD FRONTEND

### 3.1 Navigate to Frontend Directory
```bash
cd frontend
```

### 3.2 Install Dependencies (if needed)
```bash
npm install
```

### 3.3 Build Frontend for Production
```bash
npm run build
```

**Expected Output:**
```
> frontend@0.1.0 build
> react-scripts build

Creating an optimized production build...
Compiled successfully.

File sizes after gzip:

  XXX kB  build/static/js/main.XXXXXXXX.js
  XXX kB  build/static/css/main.XXXXXXXX.css

The build folder is ready to be deployed.
```

### 3.4 Verify Build Output
```bash
ls -la build/
```

**Expected:** You should see:
- `index.html`
- `static/` directory with js, css, media folders
- `asset-manifest.json`
- `manifest.json`

### 3.5 Copy Build to Public Directory (if needed)
```bash
# If your Laravel serves from public/build
cp -r build/* ../public/build/

# OR if you have a separate frontend deployment
# Follow your existing frontend deployment process
```

---

## 🔍 STEP 4: VERIFY EVERYTHING WORKS

### 4.1 Check Laravel Logs
```bash
cd ~/app.pointwave.ng
tail -f storage/logs/laravel.log
```

**Look for:**
- No errors related to Transaction model
- No errors related to TransactionReconciliationService
- Scheduled reconciliation logs (after 5 minutes)

### 4.2 Test API Endpoints
```bash
# Test RA Transactions endpoint
curl -X POST https://app.pointwave.ng/api/ra-transactions \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"id": "YOUR_USER_ID", "limit": 10, "status": "ALL", "search": ""}'
```

**Expected:** JSON response with transactions (no errors)

### 4.3 Check Cron is Running
```bash
crontab -l | grep schedule:run
```

**Expected:**
```
* * * * * cd /home/aboksdfs/app.pointwave.ng && php artisan schedule:run >> /dev/null 2>&1
```

If not present, add it:
```bash
crontab -e
```

Add this line:
```
* * * * * cd /home/aboksdfs/app.pointwave.ng && php artisan schedule:run >> /dev/null 2>&1
```

---

## 📊 STEP 5: MONITOR RECONCILIATION

### 5.1 Watch Reconciliation Logs
```bash
tail -f storage/logs/laravel.log | grep reconcil
```

**After 5 minutes, you should see:**
```
[timestamp] local.INFO: Starting scheduled transaction reconciliation
[timestamp] local.INFO: Found X stale transactions for reconciliation
[timestamp] local.INFO: Scheduled reconciliation completed {"total_checked":X,"reconciled":Y,"timeouts":Z,"failed":0}
```

### 5.2 Check Transaction Status Logs Table
```bash
php artisan tinker
```

Then run:
```php
DB::table('transaction_status_logs')->count();
exit
```

**Expected:** Count increases as status changes are logged.

---

## ⚠️ TROUBLESHOOTING

### Issue: Migration Fails
**Error:** `SQLSTATE[42S21]: Column already exists`

**Solution:**
```bash
# Check which migrations have run
php artisan migrate:status

# If Phase 1 already ran, skip it
# If you need to rollback:
php artisan migrate:rollback --step=1
```

### Issue: Scheduled Command Not Running
**Error:** No reconciliation logs after 5 minutes

**Solution:**
```bash
# Check cron is set up
crontab -l

# Test schedule manually
php artisan schedule:run

# Check for errors
php artisan schedule:list
```

### Issue: Frontend Build Fails
**Error:** `npm ERR! code ELIFECYCLE`

**Solution:**
```bash
# Clear npm cache
npm cache clean --force

# Remove node_modules and reinstall
rm -rf node_modules package-lock.json
npm install

# Try build again
npm run build
```

### Issue: Permission Denied
**Error:** `Permission denied` when copying build files

**Solution:**
```bash
# Fix permissions
sudo chown -R aboksdfs:aboksdfs ~/app.pointwave.ng/public/build
chmod -R 755 ~/app.pointwave.ng/public/build
```

---

## 📝 WHAT'S NEXT (OPTIONAL - NOT REQUIRED NOW)

### Phase 2 Migration (Backfill Historical Data)
**DO NOT RUN YET** - This requires testing on staging first

```bash
# ONLY run after testing on staging with production data copy
php artisan migrate --path=database/migrations/2026_02_21_000002_phase2_backfill_transaction_data.php
```

### Phase 3 Migration (Enforce Constraints)
**DO NOT RUN YET** - This requires Phase 2 completion

```bash
# ONLY run after Phase 2 is complete and verified
php artisan migrate --path=database/migrations/2026_02_21_000003_phase3_enforce_transaction_constraints.php
```

---

## ✅ SUCCESS CRITERIA

After deployment, verify:

1. ✅ Phase 1 migration ran successfully
2. ✅ Audit log table created
3. ✅ No errors in Laravel logs
4. ✅ Scheduled command appears in `schedule:list`
5. ✅ Frontend builds without errors
6. ✅ API endpoints respond correctly
7. ✅ Reconciliation logs appear after 5 minutes

---

## 🎯 SUMMARY

**What You Deployed:**
- Backend status reconciliation service
- Automatic reconciliation every 5 minutes
- Transaction validation for new transactions
- Audit trail for all status changes
- Database schema updates (Phase 1)

**What's Working Now:**
- New transactions auto-generate session_id, transaction_ref, net_amount, settlement_status
- Stale transactions are automatically reconciled every 5 minutes
- All status changes are logged to audit trail
- transactions.status is the canonical status source

**What's Pending:**
- Phase 2 migration (backfill historical data) - requires staging test
- Phase 3 migration (enforce constraints) - requires Phase 2 completion
- RA Dashboard refactoring (use transaction_type filter)
- Admin Dashboard implementation

---

## 📞 SUPPORT

If you encounter issues:

1. Check Laravel logs: `tail -f storage/logs/laravel.log`
2. Check migration status: `php artisan migrate:status`
3. Test command manually: `php artisan transactions:reconcile`
4. Review `TRANSACTION_NORMALIZATION_BACKEND_COMPLETE.md` for details
5. Review `specs/transaction-normalization/ROLLBACK_GUIDE.md` for emergency rollback

---

**Deployment Status:** ✅ Ready to deploy
**Estimated Time:** 10-15 minutes
**Downtime Required:** None (Phase 1 is non-breaking)
