# Settlement Rules - Production Deployment Checklist

## Status: Ready for Deployment ✅

## What Was Implemented

### 1. Database Schema ✅
- Migration created: `2026_02_18_120000_add_settlement_rules_to_settings.php`
- Adds settlement configuration to `settings` table
- Adds custom settlement fields to `companies` table
- Creates `settlement_queue` table for tracking pending settlements

### 2. Settlement Processing ✅
- Command: `app/Console/Commands/ProcessSettlements.php`
- Processes pending settlements hourly
- Handles business day calculations (skips weekends/holidays)
- Credits company wallets when settlements are due
- Logs all settlement activities

### 3. Webhook Integration ✅
- Updated: `app/Services/PalmPay/WebhookHandler.php`
- Queues transactions for settlement instead of immediate credit
- Calculates scheduled settlement date based on rules
- Falls back to immediate settlement if disabled

### 4. Admin API ✅
- Controller: `app/Http/Controllers/Admin/SettlementController.php`
- Routes added to `routes/api.php`
- Endpoints for managing global and company-specific settlement rules
- Endpoints for monitoring pending settlements and history

### 5. Company API ✅
- Updated: `app/Http/Controllers/API/AppController.php`
- Endpoint `/api/secure/discount/banks` now includes settlement configuration
- Companies can see their settlement rules

### 6. Cron Scheduler ✅
- Updated: `app/Console/Kernel.php`
- Settlement command runs hourly automatically

### 7. Models ✅
- Created: `app/Models/SettlementQueue.php`
- Relationships with Company and Transaction models

## Deployment Steps

### Step 1: Run Migration
```bash
cd /home/aboksdfs/app.pointwave.ng/reem
php artisan migrate --force
```

Expected output:
```
Migrating: 2026_02_18_120000_add_settlement_rules_to_settings
Migrated:  2026_02_18_120000_add_settlement_rules_to_settings
```

### Step 2: Verify Migration
```bash
php artisan migrate:status | grep settlement
```

Expected output:
```
| Yes  | 2026_02_18_120000_add_settlement_rules_to_settings | 1 |
```

### Step 3: Configure Settlement Rules (Admin)
```bash
# Using admin credentials
curl -X POST https://app.pointwave.ng/api/admin/settlements/config \
  -H "Authorization: Bearer {admin_token}" \
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

### Step 4: Test Settlement Command
```bash
php artisan settlements:process
```

Expected output:
```
Starting settlement processing...
No pending settlements to process
```

### Step 5: Test with Real Transaction
1. Send money to company master wallet (PalmPay: 6644694207)
2. Check settlement queue:
```bash
php artisan tinker
>>> DB::table('settlement_queue')->latest()->first();
```

3. Verify transaction metadata:
```bash
>>> DB::table('transactions')->latest()->first()->metadata;
```

### Step 6: Verify Cron is Running
```bash
crontab -l
```

Should see:
```
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

### Step 7: Monitor Logs
```bash
tail -f storage/logs/laravel.log
```

## Testing Scenarios

### Scenario 1: Immediate Settlement (Disabled)
1. Disable settlement: `auto_settlement_enabled = false`
2. Send test webhook
3. Verify wallet credited immediately
4. No entry in settlement_queue

### Scenario 2: 24-Hour Settlement (Default)
1. Enable settlement: `auto_settlement_enabled = true`, `settlement_delay_hours = 24`
2. Send test webhook
3. Verify transaction created but wallet NOT credited
4. Check settlement_queue has pending entry
5. Wait for scheduled time or run command manually
6. Verify wallet credited after settlement

### Scenario 3: Weekend Skip
1. Configure: `settlement_skip_weekends = true`
2. Send transaction on Friday 3pm
3. Verify scheduled_settlement_date is Monday 2am (not Saturday)

### Scenario 4: Custom Company Rules
1. Enable custom settlement for company: `custom_settlement_enabled = true`
2. Set custom delay: `custom_settlement_delay_hours = 1`
3. Send transaction
4. Verify settlement scheduled for 1 hour later (not 24 hours)

## API Endpoints

### Admin Endpoints (Require Admin Auth)
```
GET  /api/admin/settlements/config
POST /api/admin/settlements/config
GET  /api/admin/settlements/company/{id}/config
POST /api/admin/settlements/company/{id}/config
GET  /api/admin/settlements/pending
GET  /api/admin/settlements/history
GET  /api/admin/settlements/statistics
```

### Company Endpoints (Require Company Auth)
```
GET /api/secure/discount/banks (includes settlement config)
```

## Database Queries for Monitoring

### Check Pending Settlements
```sql
SELECT 
    sq.id,
    c.name as company,
    t.transaction_id,
    sq.amount,
    sq.scheduled_settlement_date,
    TIMESTAMPDIFF(HOUR, NOW(), sq.scheduled_settlement_date) as hours_until_settlement
FROM settlement_queue sq
JOIN companies c ON sq.company_id = c.id
JOIN transactions t ON sq.transaction_id = t.id
WHERE sq.status = 'pending'
ORDER BY sq.scheduled_settlement_date;
```

### Check Failed Settlements
```sql
SELECT * FROM settlement_queue 
WHERE status = 'failed' 
ORDER BY updated_at DESC 
LIMIT 10;
```

### Settlement Statistics
```sql
SELECT 
    status,
    COUNT(*) as count,
    SUM(amount) as total_amount,
    MIN(scheduled_settlement_date) as earliest,
    MAX(scheduled_settlement_date) as latest
FROM settlement_queue
GROUP BY status;
```

### Today's Settlements
```sql
SELECT 
    sq.*,
    c.name as company,
    t.transaction_id
FROM settlement_queue sq
JOIN companies c ON sq.company_id = c.id
JOIN transactions t ON sq.transaction_id = t.id
WHERE DATE(sq.actual_settlement_date) = CURDATE()
AND sq.status = 'completed';
```

## Rollback Plan

If issues occur, you can disable settlement without rolling back:

```sql
-- Disable auto settlement
UPDATE settings SET auto_settlement_enabled = 0;

-- Process all pending settlements immediately
UPDATE settlement_queue SET scheduled_settlement_date = NOW() WHERE status = 'pending';

-- Run settlement command
php artisan settlements:process
```

Or rollback migration:
```bash
php artisan migrate:rollback --step=1
```

## Success Indicators

✅ Migration runs without errors
✅ Settlement queue table exists
✅ Webhook creates settlement queue entries
✅ Settlement command processes due settlements
✅ Company wallets credited correctly
✅ Admin API returns settlement configuration
✅ Transaction metadata includes settlement info
✅ Cron runs hourly without errors
✅ Weekend/holiday logic works correctly

## Support & Troubleshooting

### Issue: Settlements Not Processing
**Solution**: 
1. Check cron: `crontab -l`
2. Run manually: `php artisan settlements:process`
3. Check logs: `tail -f storage/logs/laravel.log`

### Issue: Wrong Settlement Date
**Solution**:
1. Check timezone: `php artisan tinker >>> now()`
2. Verify settings: `SELECT * FROM settings`
3. Test calculation manually

### Issue: Wallet Not Credited
**Solution**:
1. Check settlement status: `SELECT * FROM settlement_queue WHERE transaction_id = ?`
2. Check transaction: `SELECT * FROM transactions WHERE id = ?`
3. Run settlement command: `php artisan settlements:process`

## Next Steps After Deployment

1. Monitor settlement queue for 24 hours
2. Verify settlements process correctly at scheduled times
3. Build admin UI for settlement management (frontend)
4. Add settlement status to company dashboard (frontend)
5. Create settlement reports and analytics
6. Set up alerts for failed settlements

## Contact

For issues or questions:
- Check logs: `storage/logs/laravel.log`
- Check documentation: `SETTLEMENT_RULES_IMPLEMENTATION.md`
- Test manually: `php artisan settlements:process`

---

**Deployment Date**: 2026-02-18
**Status**: Ready for Production ✅
**Risk Level**: Low (can be disabled without rollback)
