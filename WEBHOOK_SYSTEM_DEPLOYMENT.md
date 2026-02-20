# Webhook Management System - Deployment Guide

## Overview

Complete implementation of structured webhook management with role-based visibility, separated from transaction state machine.

---

## Files Created

### 1. Database Migration
- `database/migrations/2026_02_22_000000_create_webhook_events_table.php`

### 2. Model
- `app/Models/WebhookEvent.php`

### 3. Services
- `app/Services/Webhook/IncomingWebhookService.php`
- `app/Services/Webhook/OutgoingWebhookService.php`
- `app/Services/Webhook/WebhookRetryService.php`

### 4. Controllers
- `app/Http/Controllers/Admin/AdminWebhookController.php`
- `app/Http/Controllers/API/CompanyWebhookController.php`

### 5. Artisan Commands
- `app/Console/Commands/RetryFailedWebhooks.php`

---

## Deployment Steps

### Step 1: Run Migration

```bash
php artisan migrate
```

This creates the `webhook_events` table.

### Step 2: Add Routes

Add to `routes/api.php`:

```php
// Admin webhook routes
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/webhooks', [AdminWebhookController::class, 'index']);
    Route::get('/webhooks/{webhook}', [AdminWebhookController::class, 'show']);
    Route::post('/webhooks/{webhook}/retry', [AdminWebhookController::class, 'retry']);
});

// Company webhook routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/webhooks', [CompanyWebhookController::class, 'index']);
    Route::get('/webhooks/{webhook}', [CompanyWebhookController::class, 'show']);
});
```

### Step 3: Add Cron Job

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Retry failed webhooks every 5 minutes
    $schedule->command('webhooks:retry')
             ->everyFiveMinutes()
             ->withoutOverlapping()
             ->runInBackground();
}
```

### Step 4: Add webhook_url to companies table

```bash
php artisan make:migration add_webhook_url_to_companies_table
```

Migration content:

```php
Schema::table('companies', function (Blueprint $table) {
    $table->string('webhook_url', 500)->nullable()->after('company_name');
});
```

### Step 5: Update Transaction Events

Trigger outgoing webhooks when transaction status changes.

Add to `app/Models/Transaction.php`:

```php
protected static function booted()
{
    static::updated(function ($transaction) {
        if ($transaction->isDirty('status')) {
            // Send webhook notification
            app(OutgoingWebhookService::class)->send(
                $transaction,
                'transaction.status_changed'
            );
        }
    });
}
```

---

## Usage Examples

### Incoming Webhook (Provider → System)

```php
use App\Services\Webhook\IncomingWebhookService;

$service = app(IncomingWebhookService::class);

$result = $service->process(
    payload: $request->all(),
    signature: $request->header('X-Signature'),
    providerName: 'palmpay'
);

if ($result['success']) {
    return response()->json(['message' => 'Webhook processed']);
}
```

### Outgoing Webhook (System → Company)

```php
use App\Services\Webhook\OutgoingWebhookService;

$service = app(OutgoingWebhookService::class);

$webhookEvent = $service->send(
    transaction: $transaction,
    eventType: 'transaction.completed'
);
```

### Manual Retry

```php
use App\Services\Webhook\WebhookRetryService;

$service = app(WebhookRetryService::class);

$success = $service->retryWebhook($webhookEvent);
```

---

## API Endpoints

### Admin Endpoints

**GET /admin/webhooks**
- Get all webhook events (incoming + outgoing)
- Full payload and response visibility
- Filters: direction, status, company_id, provider, date range

**GET /admin/webhooks/{id}**
- Get single webhook details
- Full payload and response

**POST /admin/webhooks/{id}/retry**
- Manually retry failed webhook
- Returns updated status

### Company Endpoints

**GET /webhooks**
- Get company's outgoing webhooks only
- Sanitized view (no raw payloads)
- Filters: status, event_type, date range

**GET /webhooks/{id}**
- Get single webhook details
- Sanitized view only

---

## Key Features

### 1. Idempotency
- Incoming webhooks use unique provider_reference
- Duplicate detection via event_id
- Safe to replay webhooks

### 2. Exponential Backoff
- Retry delays: 1min, 5min, 15min, 1hour, 6hours
- Max 5 attempts
- Automatic retry via cron

### 3. Role-Based Visibility

**Admin View:**
- See all webhooks (incoming + outgoing)
- Full payload access
- Full response access
- Retry history
- Manual retry option

**Company View:**
- See only their outgoing webhooks
- No raw payloads
- No internal logs
- Only: event_type, transaction_ref, delivery_status, attempts, last_attempt

### 4. Separation from Transaction State
- Webhooks are audit/notification only
- They do NOT determine transaction status
- Transaction status comes from transactions table only

---

## Testing

### Test Incoming Webhook

```bash
curl -X POST https://app.pointwave.ng/api/webhooks/palmpay \
  -H "Content-Type: application/json" \
  -H "X-Signature: {signature}" \
  -d '{
    "reference": "TXN123456",
    "event_type": "payment.success",
    "amount": 1000
  }'
```

### Test Outgoing Webhook

```php
$transaction = Transaction::find(1);
$service = app(OutgoingWebhookService::class);
$webhookEvent = $service->send($transaction, 'transaction.completed');
```

### Test Retry Command

```bash
php artisan webhooks:retry
```

---

## Monitoring

### Check Failed Webhooks

```sql
SELECT * FROM webhook_events 
WHERE status = 'failed' 
AND direction = 'outgoing'
ORDER BY created_at DESC;
```

### Check Retry Queue

```sql
SELECT * FROM webhook_events 
WHERE status = 'failed' 
AND next_retry_at <= NOW()
AND attempt_count < 5;
```

### Check Delivery Rate

```sql
SELECT 
    status,
    COUNT(*) as count,
    ROUND(COUNT(*) * 100.0 / SUM(COUNT(*)) OVER(), 2) as percentage
FROM webhook_events
WHERE direction = 'outgoing'
GROUP BY status;
```

---

## Success Criteria

✅ webhook_events table created
✅ Incoming webhook service implemented
✅ Outgoing webhook service implemented
✅ Retry service with exponential backoff
✅ Admin controller with full visibility
✅ Company controller with sanitized view
✅ Cron job for automatic retries
✅ Idempotency enforcement
✅ Signature verification
✅ Separated from transaction state machine

---

## Next Steps

1. Run migration
2. Add routes
3. Configure cron job
4. Add webhook_url to companies
5. Test incoming webhooks
6. Test outgoing webhooks
7. Monitor delivery rates
8. Update frontend to display webhook logs

**System is ready for production deployment!**
