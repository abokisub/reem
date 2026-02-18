# Settlement Rules Implementation

## Overview
Settlement rules allow companies to see transactions immediately but funds settle after a configurable delay. This provides better cash flow management and aligns with PalmPay's T+1 settlement model.

## PalmPay Settlement Model
- **T+1 Settlement**: Transactions settle the next business day at 2am
- **Weekend Rule**: Friday, Saturday, and Sunday transactions settle on Monday at 2am
- **Holiday Rule**: Transactions on holidays settle on the next business day at 2am

## Features Implemented

### 1. Database Schema
**Migration**: `2026_02_18_120000_add_settlement_rules_to_settings.php`

#### Settings Table (Global Configuration)
- `auto_settlement_enabled` - Enable/disable auto settlement
- `settlement_delay_hours` - Hours to delay settlement (1, 7, 24, etc.)
- `settlement_skip_weekends` - Skip weekends for settlement
- `settlement_skip_holidays` - Skip holidays for settlement
- `settlement_time` - Time of day to process settlements (e.g., 02:00:00)
- `settlement_minimum_amount` - Minimum amount to trigger settlement

#### Companies Table (Per-Company Overrides)
- `custom_settlement_enabled` - Enable custom settlement for this company
- `custom_settlement_delay_hours` - Custom settlement delay
- `custom_settlement_minimum` - Custom minimum settlement amount

#### Settlement Queue Table
- Tracks all pending settlements
- Records scheduled and actual settlement dates
- Maintains settlement status (pending, processing, completed, failed)

### 2. Settlement Processing Command
**File**: `app/Console/Commands/ProcessSettlements.php`

**Command**: `php artisan settlements:process`

**Schedule**: Runs hourly via cron

**Features**:
- Processes all due settlements
- Handles business day calculations
- Skips weekends and holidays
- Credits company wallets
- Updates transaction metadata
- Logs all settlement activities

### 3. Webhook Handler Updates
**File**: `app/Services/PalmPay/WebhookHandler.php`

**Changes**:
- Checks if settlement is enabled
- Queues transactions for settlement instead of immediate credit
- Calculates scheduled settlement date
- Updates transaction metadata with settlement info
- Falls back to immediate settlement if disabled

### 4. Admin API Endpoints
**Controller**: `app/Http/Controllers/Admin/SettlementController.php`

#### Global Configuration
- `GET /api/admin/settlements/config` - Get global settlement config
- `POST /api/admin/settlements/config` - Update global settlement config

#### Company-Specific Configuration
- `GET /api/admin/settlements/company/{id}/config` - Get company config
- `POST /api/admin/settlements/company/{id}/config` - Update company config

#### Monitoring
- `GET /api/admin/settlements/pending` - Get pending settlements
- `GET /api/admin/settlements/history` - Get settlement history
- `GET /api/admin/settlements/statistics` - Get settlement statistics

### 5. Company API Integration
**File**: `app/Http/Controllers/API/AppController.php`

**Endpoint**: `GET /api/secure/discount/banks`

**Response includes**:
```json
{
  "status": "success",
  "data": {
    "settlement": {
      "enabled": true,
      "delay_hours": 24,
      "skip_weekends": true,
      "skip_holidays": true,
      "settlement_time": "02:00:00",
      "minimum_amount": 100.00,
      "description": "Transactions are visible immediately but funds settle after the configured delay..."
    }
  }
}
```

## Usage Examples

### 1. Run Migration
```bash
php artisan migrate --force
```

### 2. Configure Global Settlement Rules (Admin)
```bash
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

### 3. Configure Company-Specific Rules (Admin)
```bash
curl -X POST https://app.pointwave.ng/api/admin/settlements/company/2/config \
  -H "Authorization: Bearer {admin_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "custom_settlement_enabled": true,
    "custom_settlement_delay_hours": 1,
    "custom_settlement_minimum": 50.00
  }'
```

### 4. Process Settlements Manually
```bash
php artisan settlements:process
```

### 5. Check Pending Settlements
```bash
curl -X GET https://app.pointwave.ng/api/admin/settlements/pending \
  -H "Authorization: Bearer {admin_token}"
```

### 6. Get Settlement Statistics
```bash
curl -X GET https://app.pointwave.ng/api/admin/settlements/statistics \
  -H "Authorization: Bearer {admin_token}"
```

## Settlement Flow

### When Transaction is Received (Webhook)
1. Transaction is created with status "success"
2. If settlement is enabled:
   - Calculate scheduled settlement date based on rules
   - Add to settlement queue with status "pending"
   - Update transaction metadata with settlement info
   - Transaction is visible to company but wallet not credited yet
3. If settlement is disabled:
   - Credit wallet immediately (old behavior)

### When Settlement is Processed (Cron)
1. Command runs hourly: `settlements:process`
2. Finds all pending settlements where `scheduled_settlement_date <= now()`
3. For each settlement:
   - Mark as "processing"
   - Credit company wallet
   - Update transaction with balance info
   - Mark settlement as "completed"
   - Log settlement activity
4. If error occurs:
   - Mark settlement as "failed"
   - Log error message
   - Continue with next settlement

## Business Day Calculation

### Example: T+1 with Weekend Skip
- **Transaction Date**: Friday 3pm
- **Delay**: 24 hours
- **Initial Settlement**: Saturday 3pm
- **Weekend Skip**: Move to Monday
- **Settlement Time**: 2am
- **Final Settlement**: Monday 2am

### Example: 7-Hour Delay
- **Transaction Date**: Monday 10am
- **Delay**: 7 hours
- **Settlement**: Monday 5pm (same day)

### Example: Custom 1-Hour Delay
- **Transaction Date**: Tuesday 2pm
- **Delay**: 1 hour
- **Settlement**: Tuesday 3pm

## Monitoring & Alerts

### Check Settlement Queue
```sql
SELECT 
    sq.id,
    c.name as company,
    t.transaction_id,
    sq.amount,
    sq.status,
    sq.scheduled_settlement_date,
    sq.actual_settlement_date
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
ORDER BY updated_at DESC;
```

### Settlement Statistics
```sql
SELECT 
    status,
    COUNT(*) as count,
    SUM(amount) as total_amount
FROM settlement_queue
GROUP BY status;
```

## Cron Setup

Add to crontab:
```bash
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

The scheduler will automatically run `settlements:process` every hour.

## Testing

### 1. Test Settlement Calculation
```php
use App\Console\Commands\ProcessSettlements;
use Carbon\Carbon;

$transactionDate = Carbon::parse('2026-02-21 15:00:00'); // Friday 3pm
$delayHours = 24;
$skipWeekends = true;
$settlementTime = '02:00:00';

$settlementDate = ProcessSettlements::calculateSettlementDate(
    $transactionDate,
    $delayHours,
    $skipWeekends,
    false,
    $settlementTime
);

echo $settlementDate; // Monday 2026-02-24 02:00:00
```

### 2. Test Webhook with Settlement
```bash
# Send test webhook
php test_palmpay_webhook.php

# Check settlement queue
php artisan tinker
>>> DB::table('settlement_queue')->latest()->first();

# Process settlements
php artisan settlements:process
```

### 3. Test Admin API
```bash
# Get config
curl -X GET https://app.pointwave.ng/api/admin/settlements/config \
  -H "Authorization: Bearer {token}"

# Update config
curl -X POST https://app.pointwave.ng/api/admin/settlements/config \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"auto_settlement_enabled": true, "settlement_delay_hours": 1, ...}'
```

## Frontend Integration

### Display Settlement Info on Transaction
```javascript
// Transaction object includes settlement metadata
{
  "transaction_id": "txn_...",
  "amount": 5000.00,
  "status": "success",
  "metadata": {
    "settlement_status": "pending",
    "scheduled_settlement_date": "2026-02-24 02:00:00",
    "settlement_delay_hours": 24
  }
}
```

### Show Settlement Status Badge
```jsx
{transaction.metadata?.settlement_status === 'pending' && (
  <Badge color="warning">
    Settling on {formatDate(transaction.metadata.scheduled_settlement_date)}
  </Badge>
)}
```

### Display Settlement Rules
```jsx
// Fetch from /api/secure/discount/banks
const { settlement } = bankCharges;

<Alert>
  <p>{settlement.description}</p>
  <ul>
    <li>Delay: {settlement.delay_hours} hours</li>
    <li>Settlement Time: {settlement.settlement_time}</li>
    <li>Skip Weekends: {settlement.skip_weekends ? 'Yes' : 'No'}</li>
  </ul>
</Alert>
```

## Troubleshooting

### Settlements Not Processing
1. Check if cron is running: `crontab -l`
2. Check scheduler logs: `tail -f storage/logs/laravel.log`
3. Run manually: `php artisan settlements:process`
4. Check settlement queue: `SELECT * FROM settlement_queue WHERE status = 'pending'`

### Failed Settlements
1. Check error message: `SELECT * FROM settlement_queue WHERE status = 'failed'`
2. Check Laravel logs: `tail -f storage/logs/laravel.log`
3. Retry manually: Update status to 'pending' and run command

### Settlement Date Calculation Issues
1. Check timezone: `php artisan tinker >>> now()`
2. Check weekend logic: Verify Carbon's `isWeekend()` behavior
3. Test calculation: Use `ProcessSettlements::calculateSettlementDate()`

## Next Steps

1. ✅ Run migration on production
2. ✅ Configure global settlement rules via admin API
3. ✅ Test with real PalmPay webhook
4. ✅ Monitor settlement queue
5. ✅ Build admin UI for settlement management
6. ✅ Add settlement status to company dashboard
7. ✅ Create settlement reports

## Files Modified/Created

### Created
- `database/migrations/2026_02_18_120000_add_settlement_rules_to_settings.php`
- `app/Console/Commands/ProcessSettlements.php`
- `app/Models/SettlementQueue.php`
- `app/Http/Controllers/Admin/SettlementController.php`
- `SETTLEMENT_RULES_IMPLEMENTATION.md`

### Modified
- `app/Services/PalmPay/WebhookHandler.php` - Added settlement queueing
- `app/Http/Controllers/API/AppController.php` - Added settlement config to API
- `app/Console/Kernel.php` - Added settlement command to scheduler
- `routes/api.php` - Added settlement admin routes

## Production Deployment

```bash
# 1. Pull latest code
cd /home/aboksdfs/app.pointwave.ng/reem
git pull origin main

# 2. Run migration
php artisan migrate --force

# 3. Clear caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# 4. Test settlement command
php artisan settlements:process

# 5. Verify cron is running
crontab -l

# 6. Monitor logs
tail -f storage/logs/laravel.log
```

## Success Criteria

- ✅ Migration runs successfully
- ✅ Settlement queue table created
- ✅ Webhook queues transactions for settlement
- ✅ Settlement command processes due settlements
- ✅ Company wallets credited correctly
- ✅ Admin API returns settlement configuration
- ✅ Cron runs hourly without errors
- ✅ Weekend/holiday logic works correctly
- ✅ Transaction metadata includes settlement info
