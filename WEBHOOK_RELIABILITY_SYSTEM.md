# Webhook Reliability & Fallback System

## The Problem
Webhooks can fail for many reasons:
- Network issues
- Server downtime
- PalmPay delays
- Signature verification failures
- Rate limiting

## Our Multi-Layer Solution

### Layer 1: Real-Time Webhooks (Primary)
**How it works:**
- PalmPay sends webhook immediately when payment received
- Our system processes it in real-time
- Transaction created instantly
- Wallet credited immediately

**Reliability:** ~95-98%

### Layer 2: Automatic Polling (Backup)
**How it works:**
- Cron job runs every 15 minutes
- Queries PalmPay API for recent transactions
- Compares with our database
- Processes any missing transactions

**Command:** `php artisan palmpay:sync-transactions`

**Setup in cron:**
```bash
*/15 * * * * cd /home/aboksdfs/app.pointwave.ng && php artisan palmpay:sync-transactions >> /dev/null 2>&1
```

**Reliability:** Catches 100% of missed webhooks within 15 minutes

### Layer 3: Manual Sync (Emergency)
**How it works:**
- Admin can manually trigger sync from dashboard
- Or run command: `php artisan palmpay:sync-transactions`

**Use when:**
- Investigating discrepancies
- After system maintenance
- Bulk reconciliation

### Layer 4: Webhook Retry System
**How it works:**
- Failed webhooks are stored in `palmpay_webhooks` table
- Marked as `verified=0` or `processed=0`
- Can be reprocessed with: `php reprocess_webhook.php`

## Transaction States

### Success Flow:
1. Payment received by PalmPay
2. Webhook sent → Processed → Transaction created → Wallet credited
3. Status: `success`

### Webhook Failed Flow:
1. Payment received by PalmPay
2. Webhook sent → Failed signature verification
3. Stored in `palmpay_webhooks` with `verified=0`
4. Polling system picks it up within 15 minutes
5. Transaction created → Wallet credited
6. Status: `success`

### Webhook Missed Flow:
1. Payment received by PalmPay
2. Webhook never sent (PalmPay issue)
3. Polling system queries PalmPay API
4. Finds missing transaction
5. Creates transaction → Credits wallet
6. Status: `success`

## Monitoring & Alerts

### What to Monitor:
1. **Webhook Success Rate**
   - Check: `SELECT COUNT(*) FROM palmpay_webhooks WHERE verified=1`
   - Target: >95%

2. **Processing Lag**
   - Check: Time between `created_at` and `processed_at`
   - Target: <5 seconds

3. **Failed Webhooks**
   - Check: `SELECT COUNT(*) FROM palmpay_webhooks WHERE verified=0`
   - Alert if: >10 in last hour

4. **Transaction Gaps**
   - Compare PalmPay transaction count vs our database
   - Alert if: Difference >5

### Monitoring Commands:
```bash
# Check webhook health
php artisan tinker
DB::table('palmpay_webhooks')->selectRaw('
    COUNT(*) as total,
    SUM(verified) as verified,
    SUM(processed) as processed,
    COUNT(*) - SUM(verified) as failed
')->first();

# Check recent transactions
DB::table('transactions')
    ->where('created_at', '>=', now()->subHours(24))
    ->count();
```

## Recommended Cron Jobs

Add to your crontab (`crontab -e`):

```bash
# Sync PalmPay transactions every 15 minutes (backup for missed webhooks)
*/15 * * * * cd /home/aboksdfs/app.pointwave.ng && php artisan palmpay:sync-transactions >> /dev/null 2>&1

# Process settlement queue every hour (for T+1 settlements)
0 * * * * cd /home/aboksdfs/app.pointwave.ng && php artisan settlements:process >> /dev/null 2>&1

# Daily reconciliation report at 6 AM
0 6 * * * cd /home/aboksdfs/app.pointwave.ng && php artisan palmpay:reconcile >> /home/aboksdfs/logs/reconciliation.log 2>&1
```

## Best Practices

### For Webhooks:
1. ✅ Always return HTTP 200 quickly (within 5 seconds)
2. ✅ Process heavy tasks asynchronously (use queues)
3. ✅ Store raw webhook payload for debugging
4. ✅ Verify signature before processing
5. ✅ Handle duplicate webhooks (idempotency)

### For Polling:
1. ✅ Query only recent transactions (last 24 hours)
2. ✅ Use pagination for large datasets
3. ✅ Check for duplicates before creating transactions
4. ✅ Log all sync operations
5. ✅ Rate limit API calls to avoid throttling

### For Reliability:
1. ✅ Use database transactions for atomic operations
2. ✅ Implement retry logic with exponential backoff
3. ✅ Monitor webhook delivery rates
4. ✅ Set up alerts for failures
5. ✅ Keep webhook logs for 90 days minimum

## Disaster Recovery

### If Webhooks Stop Working:
1. Check PalmPay dashboard - is webhook URL correct?
2. Check server logs - any errors?
3. Test webhook endpoint manually
4. Run sync command: `php artisan palmpay:sync-transactions`
5. Contact PalmPay support if issue persists

### If Polling Fails:
1. Check PalmPay API credentials
2. Verify IP whitelist includes server IP
3. Check API rate limits
4. Review error logs
5. Manual sync as last resort

## Current Status

✅ **Webhooks:** Working (real-time processing)
✅ **Sync Command:** Available (manual fallback)
⚠️ **Automatic Polling:** Not configured yet (needs cron job)
⚠️ **Monitoring:** Not set up yet (needs alerting system)

## Next Steps

1. Set up cron job for automatic polling
2. Implement monitoring dashboard
3. Set up email/SMS alerts for failures
4. Create reconciliation report
5. Test disaster recovery procedures
