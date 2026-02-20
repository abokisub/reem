# Transaction Normalization - Migration Deployment Guide

## Overview

This guide provides step-by-step instructions for deploying the transaction normalization migrations to production. The deployment follows a three-phase approach to ensure zero data loss and minimal downtime.

## Migration Files

The following migration files have been created:

1. **Phase 1**: `2026_02_21_000001_phase1_add_transaction_normalization_columns.php`
   - Adds nullable columns and indexes
   - Zero downtime deployment
   - Safe to run during business hours

2. **Phase 2**: `2026_02_21_000002_phase2_backfill_transaction_data.php`
   - Backfills historical data
   - Should run during low traffic window
   - Estimated time: 2-4 hours for 1M transactions

3. **Phase 3**: `2026_02_21_000003_phase3_enforce_transaction_constraints.php`
   - Enforces NOT NULL constraints
   - Requires maintenance window
   - Estimated downtime: 15-30 minutes

4. **Audit Log**: `2026_02_21_000004_create_transaction_status_logs_table.php`
   - Creates transaction status audit log table
   - Zero downtime deployment
   - Can be run with Phase 1

## Pre-Deployment Checklist

### 1. Staging Environment Testing

- [ ] Copy production database to staging
- [ ] Run all migrations on staging
- [ ] Verify data integrity after each phase
- [ ] Measure execution time for each migration
- [ ] Test rollback procedures
- [ ] Run application tests on staging
- [ ] Verify no N/A values in UI

### 2. Database Backup

- [ ] Create full database backup
- [ ] Verify backup integrity
- [ ] Test backup restoration on separate server
- [ ] Document backup location and timestamp
- [ ] Ensure backup retention policy is followed

### 3. Application Preparation

- [ ] Review application code changes
- [ ] Ensure application handles nullable fields (Phase 1)
- [ ] Ensure application populates new fields (Phase 2)
- [ ] Ensure application validates required fields (Phase 3)
- [ ] Update API documentation
- [ ] Prepare rollback plan

### 4. Monitoring Setup

- [ ] Configure database performance monitoring
- [ ] Set up error rate alerts
- [ ] Prepare dashboard for migration metrics
- [ ] Configure log aggregation
- [ ] Set up notification channels

### 5. Communication

- [ ] Notify stakeholders of deployment schedule
- [ ] Prepare maintenance window announcement (Phase 3)
- [ ] Prepare rollback communication template
- [ ] Assign on-call personnel
- [ ] Schedule post-deployment review

## Phase 1 Deployment: Add Nullable Columns (Zero Downtime)

### Timing
- **When**: During business hours (safe)
- **Duration**: 2-10 minutes
- **Downtime**: None

### Pre-Deployment

```bash
# 1. Verify current migration status
php artisan migrate:status

# 2. Check database connection
php artisan db:show

# 3. Verify no pending migrations
php artisan migrate:status | grep Pending
```

### Deployment Steps

```bash
# 1. Put application in read-only mode (optional, for extra safety)
# php artisan down --render="errors::503" --retry=60

# 2. Run Phase 1 migration
php artisan migrate --path=database/migrations/2026_02_21_000001_phase1_add_transaction_normalization_columns.php

# 3. Verify migration success
php artisan migrate:status

# 4. Bring application back online (if put in maintenance mode)
# php artisan up
```

### Verification

```sql
-- Verify columns were added
DESCRIBE transactions;

-- Verify indexes were created
SHOW INDEX FROM transactions WHERE Key_name IN (
    'transactions_session_id_index',
    'transactions_transaction_ref_index',
    'transactions_type_status_index'
);

-- Verify columns are nullable
SELECT 
    COLUMN_NAME, 
    IS_NULLABLE, 
    COLUMN_TYPE 
FROM information_schema.COLUMNS 
WHERE TABLE_NAME = 'transactions' 
AND COLUMN_NAME IN ('session_id', 'transaction_ref', 'transaction_type', 'settlement_status', 'net_amount');
```

### Post-Deployment

```bash
# 1. Check application logs
tail -f storage/logs/laravel.log

# 2. Monitor error rates
# Check monitoring dashboard

# 3. Test transaction creation
# Create a test transaction via API

# 4. Verify new fields are being populated
# Check that new transactions have session_id and transaction_ref
```

### Success Criteria

- [ ] Migration completed without errors
- [ ] All columns added successfully
- [ ] All indexes created successfully
- [ ] Application continues to operate normally
- [ ] No increase in error rates
- [ ] New transactions populate new fields

## Phase 2 Deployment: Backfill Historical Data (Low Traffic Window)

### Timing
- **When**: During low traffic window (e.g., 2 AM - 6 AM)
- **Duration**: 2-4 hours for 1M transactions
- **Downtime**: None (but high database load)

### Pre-Deployment

```bash
# 1. Verify Phase 1 completed successfully
php artisan migrate:status

# 2. Check database performance metrics
# Monitor CPU, memory, disk I/O

# 3. Estimate execution time based on staging tests
# Calculate: (staging_time / staging_row_count) * production_row_count

# 4. Verify sufficient disk space
df -h
```

### Deployment Steps

```bash
# 1. Start monitoring database performance
# Open monitoring dashboard

# 2. Run Phase 2 migration
php artisan migrate --path=database/migrations/2026_02_21_000002_phase2_backfill_transaction_data.php

# 3. Monitor progress in logs
tail -f storage/logs/laravel.log | grep "Phase 2"

# 4. Wait for completion
# This may take several hours depending on transaction volume
```

### Verification

```sql
-- Check backfill completion statistics
SELECT 
    COUNT(*) as total_transactions,
    COUNT(session_id) as with_session_id,
    COUNT(transaction_ref) as with_transaction_ref,
    COUNT(transaction_type) as with_transaction_type,
    COUNT(settlement_status) as with_settlement_status,
    COUNT(net_amount) as with_net_amount
FROM transactions;

-- Verify all transactions have required fields
SELECT COUNT(*) as missing_required_fields
FROM transactions
WHERE session_id IS NULL
   OR transaction_ref IS NULL
   OR transaction_type IS NULL
   OR settlement_status IS NULL
   OR net_amount IS NULL;

-- Verify transaction_ref uniqueness
SELECT transaction_ref, COUNT(*) as count
FROM transactions
WHERE transaction_ref IS NOT NULL
GROUP BY transaction_ref
HAVING count > 1;

-- Verify status normalization
SELECT status, COUNT(*) as count
FROM transactions
GROUP BY status;

-- Verify transaction_type distribution
SELECT transaction_type, COUNT(*) as count
FROM transactions
GROUP BY transaction_type;

-- Verify settlement_status distribution
SELECT settlement_status, COUNT(*) as count
FROM transactions
GROUP BY settlement_status;

-- Verify net_amount calculation
SELECT COUNT(*) as incorrect_net_amount
FROM transactions
WHERE ABS(net_amount - (amount - fee)) > 0.01;
```

### Post-Deployment

```bash
# 1. Check application logs for errors
tail -f storage/logs/laravel.log

# 2. Verify database performance returned to normal
# Check CPU, memory, disk I/O

# 3. Test transaction queries
# Query transactions via API and verify new fields are present

# 4. Verify UI displays correctly
# Check that no N/A values are displayed
```

### Success Criteria

- [ ] Migration completed without errors
- [ ] All transactions have session_id populated
- [ ] All transactions have transaction_ref populated
- [ ] All transactions have transaction_type populated
- [ ] All transactions have settlement_status populated
- [ ] All transactions have net_amount populated
- [ ] No duplicate transaction_ref values
- [ ] Status values normalized to 5-state model
- [ ] Database performance returned to normal
- [ ] Application continues to operate normally

## Phase 3 Deployment: Enforce Constraints (Maintenance Window)

### Timing
- **When**: Scheduled maintenance window
- **Duration**: 15-30 minutes
- **Downtime**: Required

### Pre-Deployment

```bash
# 1. Verify Phase 2 completed successfully
php artisan migrate:status

# 2. Verify data integrity
php artisan tinker
>>> DB::table('transactions')->whereNull('session_id')->count();
>>> DB::table('transactions')->whereNull('transaction_ref')->count();
>>> DB::table('transactions')->whereNull('transaction_type')->count();
>>> DB::table('transactions')->whereNull('settlement_status')->count();
>>> DB::table('transactions')->whereNull('net_amount')->count();

# 3. Announce maintenance window to users
# Send notification via email, SMS, in-app message

# 4. Prepare rollback plan
# Review ROLLBACK_GUIDE.md
```

### Deployment Steps

```bash
# 1. Put application in maintenance mode
php artisan down --render="errors::503" --message="System maintenance in progress. We'll be back shortly."

# 2. Stop all background jobs
# Stop cron jobs, queue workers, scheduled tasks

# 3. Wait for active transactions to complete
# Monitor active database connections
# Wait 1-2 minutes

# 4. Run Phase 3 migration
php artisan migrate --path=database/migrations/2026_02_21_000003_phase3_enforce_transaction_constraints.php

# 5. Verify migration success
php artisan migrate:status

# 6. Deploy updated application code
# Deploy code that validates required fields

# 7. Clear application cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# 8. Restart queue workers
php artisan queue:restart

# 9. Bring application back online
php artisan up

# 10. Restart background jobs
# Restart cron jobs, scheduled tasks
```

### Verification

```sql
-- Verify constraints are enforced
SHOW CREATE TABLE transactions;

-- Verify NOT NULL constraints
SELECT 
    COLUMN_NAME, 
    IS_NULLABLE, 
    COLUMN_TYPE 
FROM information_schema.COLUMNS 
WHERE TABLE_NAME = 'transactions' 
AND COLUMN_NAME IN ('session_id', 'transaction_ref', 'transaction_type', 'settlement_status', 'net_amount');

-- Verify UNIQUE constraint on transaction_ref
SELECT CONSTRAINT_NAME, CONSTRAINT_TYPE
FROM information_schema.TABLE_CONSTRAINTS
WHERE TABLE_NAME = 'transactions'
AND CONSTRAINT_NAME = 'transactions_transaction_ref_unique';

-- Verify CHECK constraint on amount
SELECT CONSTRAINT_NAME, CHECK_CLAUSE
FROM information_schema.CHECK_CONSTRAINTS
WHERE CONSTRAINT_NAME = 'chk_amount_positive';

-- Test constraint enforcement (should fail)
-- INSERT INTO transactions (session_id, transaction_ref, amount) VALUES (NULL, 'TEST', 100);
```

### Post-Deployment

```bash
# 1. Test transaction creation
# Create test transactions via API

# 2. Verify validation errors for invalid data
# Try to create transaction with missing required fields
# Should receive 400 Bad Request with validation errors

# 3. Check application logs
tail -f storage/logs/laravel.log

# 4. Monitor error rates
# Check monitoring dashboard

# 5. Verify UI displays correctly
# Check that no N/A values are displayed
# Verify all transaction fields are populated

# 6. Test all critical user flows
# - Create virtual account
# - Deposit funds
# - Transfer funds
# - View transaction history
# - Process webhook
```

### Success Criteria

- [ ] Migration completed without errors
- [ ] All constraints enforced successfully
- [ ] Application back online
- [ ] Transaction creation works correctly
- [ ] Validation rejects invalid transactions
- [ ] No N/A values in UI
- [ ] All critical user flows working
- [ ] Error rates within normal range
- [ ] Database performance normal

## Audit Log Table Deployment

### Timing
- **When**: Can be deployed with Phase 1 or separately
- **Duration**: 1-2 minutes
- **Downtime**: None

### Deployment Steps

```bash
# Run migration
php artisan migrate --path=database/migrations/2026_02_21_000004_create_transaction_status_logs_table.php

# Verify table created
php artisan db:show transaction_status_logs
```

### Verification

```sql
-- Verify table structure
DESCRIBE transaction_status_logs;

-- Verify indexes
SHOW INDEX FROM transaction_status_logs;

-- Verify foreign key constraint
SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    REFERENCED_TABLE_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_NAME = 'transaction_status_logs'
AND REFERENCED_TABLE_NAME = 'transactions';
```

## Complete Deployment Timeline

### Week 1: Preparation
- Day 1-2: Staging environment testing
- Day 3-4: Performance testing and optimization
- Day 5: Rollback procedure testing
- Day 6-7: Final review and approval

### Week 2: Deployment
- **Monday**: Phase 1 deployment (during business hours)
  - 10:00 AM: Deploy Phase 1 migration
  - 10:30 AM: Verify success
  - 11:00 AM: Monitor for 1 hour
  - 12:00 PM: Deploy Audit Log table
  
- **Tuesday-Thursday**: Monitor Phase 1
  - Verify new transactions populate new fields
  - Monitor error rates
  - Verify application stability

- **Friday 2:00 AM**: Phase 2 deployment (low traffic window)
  - 2:00 AM: Start Phase 2 migration
  - 2:00 AM - 6:00 AM: Monitor progress
  - 6:00 AM: Verify completion
  - 6:30 AM: Verify data integrity
  - 7:00 AM: Monitor application during business hours

- **Saturday-Sunday**: Monitor Phase 2
  - Verify all transactions have required fields
  - Verify no duplicate transaction_ref values
  - Verify UI displays correctly

- **Next Monday 2:00 AM**: Phase 3 deployment (maintenance window)
  - 1:45 AM: Announce maintenance window
  - 2:00 AM: Put application in maintenance mode
  - 2:05 AM: Run Phase 3 migration
  - 2:15 AM: Deploy updated application code
  - 2:20 AM: Verify constraints enforced
  - 2:25 AM: Bring application back online
  - 2:30 AM: Verify transaction creation works
  - 3:00 AM: Monitor for 1 hour
  - 4:00 AM: End maintenance window

### Week 3: Post-Deployment
- Day 1-7: Monitor production
  - Track error rates
  - Verify data integrity
  - Monitor performance
  - Collect user feedback
  - Conduct post-deployment review

## Rollback Procedures

If any phase fails, follow the rollback procedures in `ROLLBACK_GUIDE.md`.

Quick rollback commands:

```bash
# Rollback Phase 3
php artisan migrate:rollback --step=1

# Rollback Phase 2
php artisan migrate:rollback --step=1

# Rollback Phase 1
php artisan migrate:rollback --step=1

# Rollback all phases
php artisan migrate:rollback --step=4
```

## Monitoring Checklist

During and after deployment, monitor:

- [ ] Database CPU usage
- [ ] Database memory usage
- [ ] Database disk I/O
- [ ] Database connection count
- [ ] Application error rate
- [ ] API response times (p50, p95, p99)
- [ ] Transaction creation success rate
- [ ] Webhook processing success rate
- [ ] Queue job success rate
- [ ] User-reported issues

## Success Metrics

After complete deployment:

- ✓ Zero N/A displays in production UI
- ✓ Transaction creation success rate > 99.9%
- ✓ Status reconciliation accuracy > 99.5%
- ✓ API response time p95 < 200ms
- ✓ Zero status conflicts between admin and company views
- ✓ All transactions have required fields populated
- ✓ No duplicate transaction_ref values
- ✓ Database performance within normal range
- ✓ Error rates within normal range

## Post-Deployment Review

After 1 week of production operation:

1. Review deployment process
2. Document lessons learned
3. Update procedures based on experience
4. Celebrate success with team
5. Plan next feature deployment

## Support Contacts

- Database Administrator: [Contact Info]
- Lead Developer: [Contact Info]
- DevOps Team: [Contact Info]
- On-Call Engineer: [Contact Info]

## Additional Resources

- Rollback Guide: `ROLLBACK_GUIDE.md`
- Design Document: `design.md`
- Requirements Document: `requirements.md`
- Tasks Document: `tasks.md`
