# 🚀 Payment Gateway Upgrade - Quick Start Guide

## 📋 TL;DR

**What**: Transform PointWave into world-class payment gateway
**How**: 7 phases over 8 weeks, zero downtime
**Risk**: LOW (all changes are additive, feature-flagged)

---

## ⚡ Quick Commands

### Phase 1: Foundation (Week 1-2)
```bash
# On live server
git pull origin main
php artisan migrate --force
php artisan config:clear
php artisan cache:clear

# Verify
php artisan tinker --execute="
echo 'New tables: ' . DB::select('SHOW TABLES LIKE \"%merchant_profiles%\"') ? 'OK' : 'MISSING';
"
```

### Phase 2: Transaction Engine (Week 2-3)
```bash
git pull origin main
php artisan migrate --force

# Enable metadata (passive mode)
echo "FEATURE_TRANSACTION_METADATA=true" >> .env
php artisan config:clear

# Monitor for 3 days, then enable fraud detection
echo "FEATURE_FRAUD_DETECTION=true" >> .env
```

### Phase 3: Merchant Center (Week 3-4)
```bash
git pull origin main
php artisan migrate --force
php artisan merchants:migrate-profiles

# Enable for beta merchants first
echo "FEATURE_MERCHANT_CENTER=true" >> .env
echo "BETA_MERCHANTS=4,10,17" >> .env
```

### Phase 4: Fraud Shield (Week 4-5)
```bash
git pull origin main

# Week 1: Passive (log only)
echo "FRAUD_MODE=passive" >> .env

# Week 2: Active (flag only)
echo "FRAUD_MODE=active_flag" >> .env

# Week 3: Active (block enabled)
echo "FRAUD_MODE=active_block" >> .env
```

### Phase 5: Compliance Vault (Week 5-6)
```bash
git pull origin main
php artisan migrate --force
php artisan compliance:migrate-documents

echo "FEATURE_COMPLIANCE_VAULT=true" >> .env
echo "AUDIT_LOGGING=true" >> .env
```

### Phase 6: Revenue Engine (Week 6-7)
```bash
git pull origin main
php artisan migrate --force
php artisan revenue:backfill --from=2026-01-01

echo "FEATURE_REVENUE_ENGINE=true" >> .env
```

### Phase 7: Admin Dashboard (Week 7-8)
```bash
git pull origin main
cd frontend
npm run build
cd ..

echo "FEATURE_NEW_ADMIN_UI=true" >> .env
```

---

## 🚨 Emergency Rollback

```bash
# Instant disable (any phase)
php artisan down
nano .env
# Set all FEATURE_* to false
php artisan up

# Full rollback
mysql -u user -p database < backup_YYYY-MM-DD.sql
git reset --hard <previous-commit>
php artisan config:clear && php artisan cache:clear
php artisan up
```

---

## ✅ Health Checks

### After Each Phase
```bash
# Check system health
curl https://app.pointwave.ng/api/health

# Check error logs
tail -100 storage/logs/laravel.log | grep ERROR

# Check transaction success rate
php artisan tinker --execute="
\$today = DB::table('transactions')->whereDate('created_at', today());
echo 'Success Rate: ' . (\$today->where('status', 'success')->count() / \$today->count() * 100) . '%';
"

# Check database
php artisan tinker --execute="
echo 'Companies: ' . App\Models\Company::count() . PHP_EOL;
echo 'Transactions: ' . DB::table('transactions')->count() . PHP_EOL;
echo 'VAs: ' . App\Models\VirtualAccount::count() . PHP_EOL;
"
```

---

## 📊 Key Metrics to Monitor

```bash
# Transaction success rate (should stay >98%)
# API response time (should stay <500ms)
# Error rate (should stay <0.5%)
# Fraud alerts (monitor for false positives)
```

---

## 🎯 Success Criteria Per Phase

**Phase 1**: New tables exist, no errors
**Phase 2**: Metadata captured, fraud logs working
**Phase 3**: Merchants migrated, API keys working
**Phase 4**: Fraud detection active, <1% false positives
**Phase 5**: Documents stored, audit logs working
**Phase 6**: Revenue accurate, reports generated
**Phase 7**: New UI loads, metrics display correctly

---

## 📞 When to Rollback

- Transaction success rate drops below 95%
- API response time exceeds 2 seconds
- Error rate exceeds 2%
- Critical bug discovered
- Database corruption detected

---

## 🔧 Useful Commands

```bash
# Check feature flags
php artisan tinker --execute="print_r(config('features'));"

# Check active merchants
php artisan tinker --execute="echo App\Models\Company::where('status', 'active')->count();"

# Check today's transactions
php artisan tinker --execute="echo DB::table('transactions')->whereDate('created_at', today())->count();"

# Check fraud alerts
php artisan tinker --execute="echo DB::table('fraud_alerts')->where('status', 'pending')->count();"

# Generate revenue report
php artisan revenue:report --date=today

# Run safety check
php CHECK_ALL_COMPANIES_VAS.php
```

---

**Full Plan**: See `PAYMENT_GATEWAY_UPGRADE_PLAN.md`
**Questions**: Review full plan or ask for clarification

