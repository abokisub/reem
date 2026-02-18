# Backup & Cleanup Execution Guide

> **Date**: February 9, 2026  
> **Phase**: 2A - Backup & Preparation  
> **Status**: Ready to Execute

---

## 🎯 Objective

Create comprehensive backups before removing old payment provider integrations (Monnify, Xixapay, Paystack).

---

## ✅ Step 1: Database Backup

### Option A: Using mysqldump (Recommended)

```bash
# Get database credentials from .env or config
# Replace with your actual credentials

mysqldump -u your_username -p your_database_name > /home/habukhan/Documents/pointpay/backups/db_backup_before_cleanup_$(date +%Y%m%d_%H%M%S).sql
```

### Option B: Using Laravel Artisan

```bash
cd /home/habukhan/Documents/pointpay
php artisan db:backup
```

### Option C: Manual SQL Export

```sql
-- Connect to your database and run:
-- This creates archive tables with current data

-- Archive transactions
CREATE TABLE transactions_archive_20260209 AS 
SELECT * FROM transfers 
WHERE created_at < NOW();

-- Archive user virtual accounts
CREATE TABLE user_virtual_accounts_archive_20260209 AS 
SELECT 
    id, username, email,
    paystack_account, paystack_bank,
    sterlen, vdf, fed,
    kolomoni_mfb, palmpay,
    xixapay_kyc_data,
    created_at
FROM user;

-- Archive Xixapay data (if tables exist)
CREATE TABLE xixapay_customers_archive AS SELECT * FROM xixapay_customers;
CREATE TABLE xixapay_virtual_accounts_archive AS SELECT * FROM xixapay_virtual_accounts;
CREATE TABLE xixapay_transactions_archive AS SELECT * FROM xixapay_transactions;

-- Archive Paystack data
CREATE TABLE paystack_key_archive AS SELECT * FROM paystack_key;
```

---

## ✅ Step 2: Code Backup (Git)

### Initialize Git (if not already done)

```bash
cd /home/habukhan/Documents/pointpay
git init
```

### Create .gitignore

```bash
cat > .gitignore << 'EOF'
/node_modules
/public/hot
/public/storage
/storage/*.key
/vendor
.env
.env.backup
.phpunit.result.cache
Homestead.json
Homestead.yaml
npm-debug.log
yarn-error.log
EOF
```

### Create Backup Branch

```bash
git add .
git commit -m "Backup before payment provider cleanup - $(date +%Y-%m-%d)"
git branch backup-before-cleanup
git tag backup-$(date +%Y%m%d)
```

---

## ✅ Step 3: File System Backup

### Create Backups Directory

```bash
mkdir -p /home/habukhan/Documents/pointpay/backups
```

### Backup Provider Classes

```bash
# Create provider backup directory
mkdir -p /home/habukhan/Documents/pointpay/backups/old_providers

# Copy provider files
cp app/Services/Banking/Providers/MonnifyProvider.php backups/old_providers/ 2>/dev/null || true
cp app/Services/Banking/Providers/XixapayProvider.php backups/old_providers/ 2>/dev/null || true
cp app/Services/Banking/Providers/PaystackProvider.php backups/old_providers/ 2>/dev/null || true
```

### Backup Environment Variables

```bash
# Backup current .env (if exists)
cp .env backups/env_backup_$(date +%Y%m%d).txt 2>/dev/null || true

# Extract provider-specific variables
grep -E "(MONNIFY|XIXAPAY|PAYSTACK)" .env > backups/old_provider_env_vars.txt 2>/dev/null || true
```

---

## ✅ Step 4: Create Cleanup Migration

This migration will be used to clean up the database schema.

### Create Migration File

```bash
php artisan make:migration cleanup_old_payment_providers
```

### Migration Content

The migration file will be created in the next step with proper SQL commands.

---

## 📋 Verification Checklist

Before proceeding with cleanup, verify:

- [ ] Database backup created successfully
- [ ] Backup file size is reasonable (not 0 bytes)
- [ ] Git repository initialized
- [ ] Code committed to backup branch
- [ ] Provider classes backed up to `/backups/old_providers/`
- [ ] Environment variables backed up
- [ ] Archive tables created in database
- [ ] Backup directory created: `/home/habukhan/Documents/pointpay/backups/`

---

## 🚨 Rollback Procedure (If Needed)

### Restore Database

```bash
mysql -u your_username -p your_database_name < /path/to/backup.sql
```

### Restore Code

```bash
git checkout backup-before-cleanup
```

### Restore Archive Tables

```sql
-- If you need to restore specific data
INSERT INTO user (id, username, paystack_account, ...)
SELECT id, username, paystack_account, ...
FROM user_virtual_accounts_archive_20260209;
```

---

## 📊 Next Steps After Backup

Once all backups are verified:

1. ✅ Proceed to Phase 2B: Code Cleanup
2. ✅ Delete provider service classes
3. ✅ Refactor controllers
4. ✅ Update models
5. ✅ Clean database schema

---

**Status**: Backup procedures documented  
**Next Action**: Execute backup commands  
**Last Updated**: 2026-02-09
