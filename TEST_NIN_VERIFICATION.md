# Test Real NIN Verification - Step by Step

## NIN to Test: 35257106066

---

## Option 1: Quick Test (Copy & Paste)

Run this single command on your server terminal:

```bash
curl -X POST "https://app.pointwave.ng/api/v1/kyc/verify-nin" \
  -H "Authorization: Bearer d8a3151a8993c157c1a4ee5ecda8983107004b1fbdec3f55011f1b7ee4e63f517b7403e021e96995e4c5c90aab834ea70845672dbe6ccf7910dda45c" \
  -H "x-api-key: 7db8dbb3991382487a1fc388a05d96a7139d92ba" \
  -H "x-business-id: 3450968aa027e86e3ff5b0169dc17edd7694a846" \
  -H "Idempotency-Key: test_nin_$(date +%s)" \
  -H "Content-Type: application/json" \
  -d '{"nin":"35257106066"}' | python3 -m json.tool
```

---

## Option 2: Complete Test with Verification (Recommended)

### Step 1: Check Wallet Balance BEFORE

```bash
curl -s -X GET "https://app.pointwave.ng/api/v1/balance" \
  -H "Authorization: Bearer d8a3151a8993c157c1a4ee5ecda8983107004b1fbdec3f55011f1b7ee4e63f517b7403e021e96995e4c5c90aab834ea70845672dbe6ccf7910dda45c" \
  -H "x-api-key: 7db8dbb3991382487a1fc388a05d96a7139d92ba" \
  -H "x-business-id: 3450968aa027e86e3ff5b0169dc17edd7694a846" \
  -H "Content-Type: application/json" | python3 -m json.tool
```

**Note the balance amount!**

---

### Step 2: Verify NIN (This will charge ₦45)

```bash
curl -X POST "https://app.pointwave.ng/api/v1/kyc/verify-nin" \
  -H "Authorization: Bearer d8a3151a8993c157c1a4ee5ecda8983107004b1fbdec3f55011f1b7ee4e63f517b7403e021e96995e4c5c90aab834ea70845672dbe6ccf7910dda45c" \
  -H "x-api-key: 7db8dbb3991382487a1fc388a05d96a7139d92ba" \
  -H "x-business-id: 3450968aa027e86e3ff5b0169dc17edd7694a846" \
  -H "Idempotency-Key: test_nin_$(date +%s)" \
  -H "Content-Type: application/json" \
  -d '{"nin":"35257106066"}' | python3 -m json.tool
```

**Expected Response:**
```json
{
  "status": true,
  "message": "NIN verified successfully",
  "data": {
    "nin": "35257106066",
    "first_name": "...",
    "middle_name": "...",
    "last_name": "...",
    "date_of_birth": "...",
    "phone": "...",
    "gender": "...",
    "address": "...",
    "photo": "base64_encoded_image..."
  },
  "charged": true,
  "charge_amount": 45,
  "transaction_reference": "KYC_ENHANCED_NIN_1708531200_5678"
}
```

---

### Step 3: Check Wallet Balance AFTER

```bash
curl -s -X GET "https://app.pointwave.ng/api/v1/balance" \
  -H "Authorization: Bearer d8a3151a8993c157c1a4ee5ecda8983107004b1fbdec3f55011f1b7ee4e63f517b7403e021e96995e4c5c90aab834ea70845672dbe6ccf7910dda45c" \
  -H "x-api-key: 7db8dbb3991382487a1fc388a05d96a7139d92ba" \
  -H "x-business-id: 3450968aa027e86e3ff5b0169dc17edd7694a846" \
  -H "Content-Type: application/json" | python3 -m json.tool
```

**Balance should be ₦45 less than before!**

---

### Step 4: Check Transaction Record in Database

```bash
cd /home/aboksdfs/app.pointwave.ng
php artisan tinker
```

Then run these commands in tinker:

```php
// Get the latest KYC transaction
$tx = DB::table('transactions')->where('category', 'kyc_charge')->latest()->first();
print_r($tx);

// Check wallet balance
$wallet = DB::table('company_wallets')->where('company_id', 1)->first();
echo "Wallet Balance: ₦" . $wallet->balance . "\n";

// Exit tinker
exit
```

---

### Step 5: Check Transaction in Logs

```bash
tail -f storage/logs/laravel.log | grep -i "kyc"
```

Then make another NIN verification request to see real-time logs.

---

## Option 3: Use the Test Script

### Step 1: Make script executable

```bash
cd /home/aboksdfs/app.pointwave.ng
chmod +x test_real_kyc_nin.sh
```

### Step 2: Run the script

```bash
./test_real_kyc_nin.sh
```

This will automatically:
1. Check balance before
2. Verify NIN
3. Check balance after
4. Show summary

---

## What to Verify

After running the test, confirm:

### ✅ API Response
- [ ] `status: true`
- [ ] `charged: true`
- [ ] `charge_amount: 45`
- [ ] `transaction_reference` is present
- [ ] NIN data returned (name, DOB, phone, etc.)

### ✅ Wallet Balance
- [ ] Balance decreased by ₦45
- [ ] New balance = Old balance - 45

### ✅ Transaction Record
- [ ] Transaction exists in database
- [ ] `category = 'kyc_charge'`
- [ ] `type = 'debit'`
- [ ] `amount = 45`
- [ ] `status = 'success'`
- [ ] `description = 'KYC Verification Charge - Enhanced Nin'`
- [ ] `reference` starts with `KYC_ENHANCED_NIN_`

### ✅ Dashboard Visibility
- [ ] Transaction visible in company dashboard
- [ ] Transaction visible in admin dashboard
- [ ] Transaction shows correct amount and description

---

## Troubleshooting

### If you get "Insufficient balance"

Add balance to wallet:
```bash
php artisan tinker
>>> DB::table('company_wallets')->where('company_id', 1)->update(['balance' => 1000]);
>>> exit
```

### If you get "Invalid credentials"

Check your credentials are correct:
```bash
php artisan tinker
>>> $company = DB::table('companies')->where('id', 1)->first();
>>> echo "API Key: " . $company->api_key . "\n";
>>> echo "Secret Key: " . $company->secret_key . "\n";
>>> echo "Business ID: " . $company->business_id . "\n";
>>> exit
```

### If you get "EaseID API error"

Check EaseID configuration:
```bash
php artisan tinker
>>> echo config('services.easeid.app_id') . "\n";
>>> echo config('services.easeid.base_url') . "\n";
>>> exit
```

### If transaction not created

Check logs:
```bash
tail -100 storage/logs/laravel.log | grep -i "kyc"
```

---

## Expected Results

### Successful Test Output:

```
============================================
  TEST SUMMARY
============================================

✅ NIN Verification: SUCCESS
✅ Charge Deducted: YES (₦45)
✅ Wallet Balance Updated: ₦199.00 → ₦154.00
✅ Transaction Reference: KYC_ENHANCED_NIN_1708531200_5678

============================================
```

### Transaction in Database:

```
id: 123
company_id: 1
reference: KYC_ENHANCED_NIN_1708531200_5678
type: debit
category: kyc_charge
amount: 45.00
fee: 0.00
net_amount: 45.00
status: success
description: KYC Verification Charge - Enhanced Nin
balance_before: 199.00
balance_after: 154.00
created_at: 2026-02-21 10:30:00
```

---

## Next Steps After Successful Test

1. ✅ Test BVN verification (₦25 charge)
2. ✅ Test Bank Account verification (₦65 charge)
3. ✅ Test with different company (verify charges work for all companies)
4. ✅ Test cached result (verify no double charge for same NIN)
5. ✅ Share API documentation with developers

---

**Ready to test? Copy and paste the commands above!** 🚀
