# PointWave Project Summary
Last Updated: 2026-03-26

---

## What Was Built Today

### 1. Pay With Bank Card (Card Checkout)
- `app/Services/PalmPay/CardCheckoutService.php`
- `app/Http/Controllers/API/V1/CardCheckoutController.php`
- `app/Models/CardCheckoutOrder.php`
- Migration: `2026_03_26_000001_create_card_checkout_orders_table.php`
- Routes: `POST /api/v1/checkout/card/{create|query|refund}`
- Webhooks: `POST /api/webhooks/palmpay/{card-payment|card-refund}`
- Status: Code ready. PalmPay product not yet activated on merchant account. Set `CARD_CHECKOUT_ENABLED=true` in `.env` when ready.

### 2. Dynamic Virtual Account (Pay With Bank Transfer)
- `app/Services/PalmPay/DynamicAccountService.php`
- `app/Http/Controllers/API/V1/DynamicAccountController.php`
- Routes: `POST /api/v1/checkout/bank-transfer/{create|query|check-account}`
- Webhook: `POST /api/webhooks/palmpay/bank-transfer`
- Status: LIVE TESTED — working. Returns real account number per order.

### 3. Company Custom Fee System
- `app/Services/FeeService.php` — unified fee calculation, company override priority
- `app/Models/CompanyFeeSetting.php` — 13 fee types including all KYC services
- `app/Http/Controllers/Admin/CompanyFeeController.php`
- Migration: `2026_03_26_000002_add_minimum_fee_to_company_fee_settings.php`
- Admin UI: `/secure/company-fees` — search company, set custom fees, fee simulator
- Routes: `GET/POST /api/admin/company-fees/{search|simulate|{companyId}}`
- FeeService wired into: WebhookHandler, V1 WebhookController, PalmPayWebhookController, SettlementService, MerchantApiController

### 4. Settlement Diagnostics Endpoint
- `GET /api/settlements/diagnostics` — shows server time, settings, pending counts, simulation

### 5. Charges Page Cleanup (/secure/discount/banks)
- Consolidated into 4 clear sections: VA Deposit, Settlement Withdrawal, Pay With Bank Transfer, External Transfer
- VA Deposit charge moved from `/secure/discount/other` to banks page
- `/secure/discount/other` now shows redirect notice

### 6. Documentation Page
- `/documentation/virtual-accounts/dynamic` — full dynamic account API docs

---

## Fee Column Mapping (IMPORTANT — Do Not Change)

| UI Label | DB Column | Used By |
|---|---|---|
| Virtual Account Deposit Fee | `service_charges.palmpay_va` | WebhookHandler, V1 WebhookController |
| Settlement Withdrawal Fee | `payout_palmpay_charge` | TransferPurchase, SettlementService |
| Pay With Bank Transfer Fee | `transfer_charge` | MerchantApiController |
| External Transfer (Other Banks) | `payout_bank_charge` | TransferPurchase, MerchantApiController |

---

## Settlement Auto-Processing

- Cron is set up on live server: runs every 5 mins
- Settlements queue at T+1 (next day 3am)
- PalmPay settles you at 2am, your users settle at 3am (1hr buffer)

### PENDING ACTION — Add dedicated cron on live server:
```
crontab -e
```
Add:
```
5 3 * * * cd /home/aboksdfs/app.pointwave.ng && php artisan settlements:process >> /dev/null 2>&1
30 3 * * * cd /home/aboksdfs/app.pointwave.ng && php artisan settlements:process-overdue >> /dev/null 2>&1
```

---

## CRITICAL INCIDENT — 2026-03-26

**What happened:** FeeService was wired into WebhookHandler but the old code read `$feeResults['model']` which no longer existed in the new response format. This caused all VA deposit webhooks to fail — 15 deposits were not credited.

**Fix applied:** Updated `WebhookHandler.php` to read from `$feeResults['breakdown']['fee_model']` instead.

**Recovery:** All 15 failed webhooks (IDs 462–476) were manually reprocessed and users credited.

**Rule going forward:** Before changing any function return structure that touches money processing (webhooks, deposits, transfers), check EVERY caller of that function first.

---

## Live Server Commands Reference

```bash
# Pull latest code
git pull origin main
php artisan cache:clear
php artisan config:clear

# Run a specific migration
php artisan migrate --path=database/migrations/FILENAME.php --force

# Reprocess failed VA deposit webhooks
php artisan tinker --execute="
use App\Services\PalmPay\WebhookHandler;
\$handler = app(WebhookHandler::class);
\$failed = DB::table('palmpay_webhooks')->where('status','failed')->where('event_type','VIRTUAL_ACCOUNT_CASH_IN')->get();
foreach(\$failed as \$log) {
    \$payload = json_decode(\$log->payload, true);
    DB::table('palmpay_webhooks')->where('id',\$log->id)->update(['status'=>'pending','processing_error'=>null,'retry_count'=>0]);
    \$r = \$handler->handle(\$payload);
    echo 'ID '.\$log->id.': '.((\$r['success']??false)?'OK':'FAIL').PHP_EOL;
}
"

# Check settlement status
php artisan tinker --execute="
echo 'Pending: ' . DB::table('settlement_queue')->where('status','pending')->count() . PHP_EOL;
echo 'Completed today: ' . DB::table('settlement_queue')->where('status','completed')->whereDate('actual_settlement_date',today())->count() . PHP_EOL;
"

# Check fee columns
php artisan tinker --execute="
\$s = DB::table('settings')->first();
echo 'VA deposit (palmpay_va service_charge): ' . DB::table('service_charges')->where('service_name','palmpay_va')->first()->charge_value . PHP_EOL;
echo 'Settlement (payout_palmpay): ' . \$s->payout_palmpay_charge_value . PHP_EOL;
echo 'External transfer (payout_bank): ' . \$s->payout_bank_charge_value . PHP_EOL;
echo 'Bank transfer (transfer_charge): ' . \$s->transfer_charge_value . PHP_EOL;
"
```
