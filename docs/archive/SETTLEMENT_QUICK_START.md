# Settlement Rules - Quick Start Guide

## 🚀 5-Minute Setup

### 1. Run Migration (1 minute)
```bash
cd /home/aboksdfs/app.pointwave.ng/reem
php artisan migrate --force
```

### 2. Configure Settlement (2 minutes)

**Option A: Default Configuration (Recommended)**
```sql
mysql -u root -p pointpay

UPDATE settings SET 
  auto_settlement_enabled = 1,
  settlement_delay_hours = 24,
  settlement_skip_weekends = 1,
  settlement_skip_holidays = 1,
  settlement_time = '02:00:00',
  settlement_minimum_amount = 100.00
WHERE id = 1;

exit;
```

**Option B: Via API**
```bash
curl -X POST https://app.pointwave.ng/api/admin/settlements/config \
  -H "Authorization: Bearer YOUR_ADMIN_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "auto_settlement_enabled": true,
    "settlement_delay_hours": 24,
    "settlement_skip_weekends": true,
    "settlement_skip_holidays": true,
    "settlement_time": "02:00:00",
    "settlement_minimum_amount": 100.00
  }'
```

### 3. Test Settlement Command (1 minute)
```bash
php artisan settlements:process
```

Expected output:
```
Starting settlement processing...
No pending settlements to process
```

### 4. Verify Cron (1 minute)
```bash
crontab -l
```

Should see:
```
* * * * * cd /home/aboksdfs/app.pointwave.ng/reem && php artisan schedule:run >> /dev/null 2>&1
```

If not, add it:
```bash
crontab -e
# Add the line above
```

### 5. Test with Real Transaction
Send money to PalmPay master wallet: **6644694207**

Check settlement queue:
```bash
php artisan tinker
>>> DB::table('settlement_queue')->latest()->first();
>>> exit
```

## ✅ Verification Checklist

```bash
# 1. Check migration
php artisan migrate:status | grep settlement

# 2. Check tables exist
mysql -u root -p pointpay -e "SHOW TABLES LIKE 'settlement_queue';"

# 3. Check settings
mysql -u root -p pointpay -e "SELECT auto_settlement_enabled, settlement_delay_hours FROM settings;"

# 4. Test command
php artisan settlements:process

# 5. Check logs
tail -f storage/logs/laravel.log
```

## 📊 Quick Monitoring

### Check Pending Settlements
```sql
SELECT COUNT(*), SUM(amount) FROM settlement_queue WHERE status = 'pending';
```

### Check Today's Settlements
```sql
SELECT COUNT(*), SUM(amount) FROM settlement_queue 
WHERE DATE(actual_settlement_date) = CURDATE() AND status = 'completed';
```

### Check Failed Settlements
```sql
SELECT * FROM settlement_queue WHERE status = 'failed' ORDER BY updated_at DESC LIMIT 5;
```

## 🔧 Common Commands

```bash
# Process settlements manually
php artisan settlements:process

# Check settlement queue
php artisan tinker
>>> DB::table('settlement_queue')->where('status', 'pending')->get();

# Check latest transaction
>>> DB::table('transactions')->latest()->first();

# Check company wallet
>>> DB::table('company_wallets')->where('company_id', 2)->first();
```

## 🎯 Configuration Options

### Global Settings (All Companies)
- `auto_settlement_enabled` - Enable/disable settlement (true/false)
- `settlement_delay_hours` - Hours to delay (1, 7, 24, etc.)
- `settlement_skip_weekends` - Skip weekends (true/false)
- `settlement_skip_holidays` - Skip holidays (true/false)
- `settlement_time` - Time to settle (02:00:00)
- `settlement_minimum_amount` - Minimum amount (100.00)

### Company-Specific Settings (Override Global)
- `custom_settlement_enabled` - Enable custom rules (true/false)
- `custom_settlement_delay_hours` - Custom delay (1, 7, 24, etc.)
- `custom_settlement_minimum` - Custom minimum (50.00)

## 🚨 Troubleshooting

### Settlements Not Processing
```bash
# Check cron
crontab -l

# Run manually
php artisan settlements:process

# Check logs
tail -f storage/logs/laravel.log
```

### Disable Settlement (Emergency)
```sql
UPDATE settings SET auto_settlement_enabled = 0;
```

### Process All Pending Immediately
```sql
UPDATE settlement_queue SET scheduled_settlement_date = NOW() WHERE status = 'pending';
```
Then run:
```bash
php artisan settlements:process
```

## 📱 API Endpoints

### Company API (Get Settlement Config)
```
GET /api/secure/discount/banks?id={user_id}
```

### Admin API (Manage Settlement)
```
GET  /api/admin/settlements/config
POST /api/admin/settlements/config
GET  /api/admin/settlements/pending
GET  /api/admin/settlements/history
GET  /api/admin/settlements/statistics
```

## 📚 Full Documentation

- `SETTLEMENT_RULES_IMPLEMENTATION.md` - Complete technical details
- `SETTLEMENT_DEPLOYMENT_CHECKLIST.md` - Detailed deployment steps
- `SETTLEMENT_COMPLETE_SUMMARY.md` - Implementation summary

## 🎉 Success!

If all checks pass, settlement rules are now active!

Transactions will:
1. ✅ Be visible immediately to companies
2. ✅ Queue for settlement based on rules
3. ✅ Credit wallets at scheduled time
4. ✅ Skip weekends and holidays
5. ✅ Process automatically every hour

---

**Need Help?**
- Check logs: `tail -f storage/logs/laravel.log`
- Test command: `php artisan settlements:process`
- Check queue: `SELECT * FROM settlement_queue`
