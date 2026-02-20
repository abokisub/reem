# Transaction Normalization - Rollback Guide

## Overview

This guide provides step-by-step instructions for rolling back the transaction normalization migrations in case of issues during deployment. Each phase has its own rollback procedure that must be executed in reverse order.

## Rollback Order

**CRITICAL:** Rollbacks must be executed in reverse order:
1. Phase 3 rollback (if Phase 3 was deployed)
2. Phase 2 rollback (if Phase 2 was deployed)
3. Phase 1 rollback (if Phase 1 was deployed)
4. TransactionStatusLog table rollback (if created)

## Pre-Rollback Checklist

Before executing any rollback:

- [ ] Create a full database backup
- [ ] Verify backup integrity
- [ ] Document the reason for rollback
- [ ] Notify all stakeholders
- [ ] Put application in maintenance mode (if rolling back Phase 3)
- [ ] Stop all background jobs that process transactions

## Phase 3 Rollback: Remove Constraints

**When to use:** If Phase 3 migration causes issues or needs to be reverted

**Impact:** 
- Removes NOT NULL constraints
- Removes UNIQUE constraint on transaction_ref
- Removes CHECK constraint on amount
- Application can continue operating with nullable fields

**Command:**
```bash
php artisan migrate:rollback --step=1
```

**Verification:**
```sql
-- Verify constraints are removed
SHOW CREATE TABLE transactions;

-- Check that columns are nullable again
SELECT 
    COLUMN_NAME, 
    IS_NULLABLE, 
    COLUMN_TYPE 
FROM information_schema.COLUMNS 
WHERE TABLE_NAME = 'transactions' 
AND COLUMN_NAME IN ('session_id', 'transaction_ref', 'transaction_type', 'settlement_status', 'net_amount');
```

**Expected Result:**
- All normalized columns should be nullable
- No UNIQUE constraint on transaction_ref
- No CHECK constraint on amount

**Post-Rollback Actions:**
1. Update application code to handle nullable fields
2. Monitor for any validation errors
3. Investigate root cause of Phase 3 issues

## Phase 2 Rollback: Clear Backfilled Data

**When to use:** If backfilled data is incorrect or Phase 2 migration needs to be re-run

**Impact:**
- Clears all backfilled data (session_id, transaction_ref, transaction_type, settlement_status, net_amount)
- Reverts status normalization (successful → success)
- Columns remain in place but values are set to NULL

**Command:**
```bash
php artisan migrate:rollback --step=1
```

**Verification:**
```sql
-- Check that backfilled data is cleared
SELECT 
    COUNT(*) as total,
    COUNT(session_id) as with_session_id,
    COUNT(transaction_ref) as with_transaction_ref,
    COUNT(transaction_type) as with_transaction_type,
    COUNT(settlement_status) as with_settlement_status,
    COUNT(net_amount) as with_net_amount
FROM transactions;

-- Verify status values reverted
SELECT status, COUNT(*) as count 
FROM transactions 
GROUP BY status;
```

**Expected Result:**
- All normalized columns should have NULL values
- Status should show 'success' instead of 'successful'

**Post-Rollback Actions:**
1. Investigate data mapping issues
2. Fix backfill logic if needed
3. Re-run Phase 2 migration after fixes

## Phase 1 Rollback: Remove Columns and Indexes

**When to use:** If Phase 1 migration causes issues or complete rollback is needed

**Impact:**
- Removes all normalized columns (session_id, transaction_ref, transaction_type, settlement_status, net_amount)
- Removes all indexes created for normalized columns
- Returns schema to pre-migration state

**Command:**
```bash
php artisan migrate:rollback --step=1
```

**Verification:**
```sql
-- Verify columns are removed
SELECT COLUMN_NAME 
FROM information_schema.COLUMNS 
WHERE TABLE_NAME = 'transactions' 
AND COLUMN_NAME IN ('session_id', 'transaction_ref', 'transaction_type', 'settlement_status', 'net_amount');

-- Verify indexes are removed
SHOW INDEX FROM transactions 
WHERE Key_name IN (
    'transactions_session_id_index',
    'transactions_transaction_ref_index',
    'transactions_type_status_index'
);
```

**Expected Result:**
- No normalized columns should exist
- No normalized indexes should exist
- Original schema intact

**Post-Rollback Actions:**
1. Revert application code changes
2. Remove references to normalized fields
3. Investigate root cause of Phase 1 issues

## TransactionStatusLog Table Rollback

**When to use:** If the audit log table needs to be removed

**Impact:**
- Drops transaction_status_logs table
- All status change history is lost

**Command:**
```bash
php artisan migrate:rollback --step=1
```

**Verification:**
```sql
-- Verify table is dropped
SHOW TABLES LIKE 'transaction_status_logs';
```

**Expected Result:**
- Table should not exist

**Post-Rollback Actions:**
1. Remove references to TransactionStatusLog model
2. Update status reconciliation service

## Complete Rollback: All Phases

**When to use:** Complete failure requiring full rollback to original state

**Impact:**
- Removes all changes from transaction normalization feature
- Returns to original schema and data state

**Commands:**
```bash
# Rollback in reverse order
php artisan migrate:rollback --step=4

# Or rollback to specific batch
php artisan migrate:rollback --batch=X
```

**Verification:**
```bash
# Check migration status
php artisan migrate:status

# Verify schema
php artisan db:show transactions
```

**Post-Rollback Actions:**
1. Restore application code to pre-migration state
2. Remove all feature flags related to transaction normalization
3. Conduct post-mortem to identify issues
4. Plan fixes before attempting re-deployment

## Emergency Rollback Procedure

If migrations fail mid-execution or database is in inconsistent state:

### Step 1: Assess Damage
```sql
-- Check which columns exist
DESCRIBE transactions;

-- Check data integrity
SELECT 
    COUNT(*) as total,
    COUNT(session_id) as with_session_id,
    COUNT(transaction_ref) as with_transaction_ref,
    COUNT(transaction_type) as with_transaction_type
FROM transactions;
```

### Step 2: Manual Cleanup (if needed)

If automated rollback fails, manually execute cleanup:

```sql
-- Remove constraints (if Phase 3 partially applied)
ALTER TABLE transactions DROP CONSTRAINT IF EXISTS chk_amount_positive;
ALTER TABLE transactions DROP INDEX IF EXISTS transactions_transaction_ref_unique;

-- Make columns nullable (if Phase 3 partially applied)
ALTER TABLE transactions MODIFY COLUMN session_id VARCHAR(255) NULL;
ALTER TABLE transactions MODIFY COLUMN transaction_ref VARCHAR(255) NULL;
ALTER TABLE transactions MODIFY COLUMN transaction_type ENUM(...) NULL;
ALTER TABLE transactions MODIFY COLUMN settlement_status ENUM(...) NULL;
ALTER TABLE transactions MODIFY COLUMN net_amount DECIMAL(15, 2) NULL;

-- Clear backfilled data (if Phase 2 partially applied)
UPDATE transactions SET 
    session_id = NULL,
    transaction_ref = NULL,
    transaction_type = NULL,
    settlement_status = NULL,
    net_amount = NULL
WHERE session_id LIKE 'sess_%';

-- Remove indexes (if Phase 1 partially applied)
ALTER TABLE transactions DROP INDEX IF EXISTS transactions_session_id_index;
ALTER TABLE transactions DROP INDEX IF EXISTS transactions_transaction_ref_index;
ALTER TABLE transactions DROP INDEX IF EXISTS transactions_type_status_index;

-- Remove columns (if Phase 1 partially applied)
ALTER TABLE transactions DROP COLUMN IF EXISTS session_id;
ALTER TABLE transactions DROP COLUMN IF EXISTS transaction_ref;
ALTER TABLE transactions DROP COLUMN IF EXISTS transaction_type;
ALTER TABLE transactions DROP COLUMN IF EXISTS settlement_status;
ALTER TABLE transactions DROP COLUMN IF EXISTS net_amount;

-- Drop audit log table
DROP TABLE IF EXISTS transaction_status_logs;
```

### Step 3: Verify Clean State
```sql
-- Verify original schema
DESCRIBE transactions;

-- Verify data integrity
SELECT COUNT(*) FROM transactions;
SELECT status, COUNT(*) FROM transactions GROUP BY status;
```

### Step 4: Update Migration Table
```sql
-- Remove migration records
DELETE FROM migrations 
WHERE migration IN (
    '2026_02_21_000001_phase1_add_transaction_normalization_columns',
    '2026_02_21_000002_phase2_backfill_transaction_data',
    '2026_02_21_000003_phase3_enforce_transaction_constraints',
    '2026_02_21_000004_create_transaction_status_logs_table'
);
```

## Post-Rollback Verification

After any rollback, verify system health:

### Database Checks
```sql
-- Verify transaction count unchanged
SELECT COUNT(*) FROM transactions;

-- Verify no data loss
SELECT 
    MIN(created_at) as oldest,
    MAX(created_at) as newest,
    COUNT(*) as total
FROM transactions;

-- Verify status distribution
SELECT status, COUNT(*) as count 
FROM transactions 
GROUP BY status;
```

### Application Checks
```bash
# Clear application cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Run application tests
php artisan test

# Check logs for errors
tail -f storage/logs/laravel.log
```

### Functional Tests
1. Create a new transaction
2. Query transaction list
3. View transaction details
4. Process a webhook
5. Run scheduled jobs

## Rollback Decision Matrix

| Scenario | Recommended Action |
|----------|-------------------|
| Phase 3 constraint violation | Rollback Phase 3 only, fix data, re-run |
| Phase 2 incorrect data mapping | Rollback Phase 2, fix logic, re-run |
| Phase 1 performance issues | Rollback Phase 1, optimize indexes, re-run |
| Application errors after Phase 3 | Rollback Phase 3, fix application code, re-deploy |
| Data loss detected | STOP - Restore from backup, investigate |
| Duplicate transaction_ref | Rollback Phase 3, resolve duplicates, re-run |

## Support Contacts

If rollback issues occur:
- Database Administrator: [Contact Info]
- Lead Developer: [Contact Info]
- DevOps Team: [Contact Info]

## Rollback Logs

All rollback operations are logged to:
- Application log: `storage/logs/laravel.log`
- Database log: Check MySQL error log
- Migration log: `migrations` table

## Lessons Learned Template

After rollback, document:
1. What went wrong?
2. Why did it go wrong?
3. How was it detected?
4. What was the impact?
5. How was it resolved?
6. How can we prevent it in the future?

## Re-Deployment After Rollback

Before attempting re-deployment:
1. Identify and fix root cause
2. Test fixes on staging environment
3. Update migration scripts if needed
4. Update rollback procedures based on lessons learned
5. Conduct dry-run on staging with production data copy
6. Get approval from stakeholders
7. Schedule new deployment window
