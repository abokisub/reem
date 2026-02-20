# ✅ Deploy API V1 Changes on Server

## What Was Pushed to GitHub

✅ **Simplified Customer Creation** - Only 4 fields required:
- `first_name` (required)
- `last_name` (required)  
- `email` (required)
- `phone_number` (required)

❌ **Removed Requirements:**
- NO BVN/NIN
- NO address
- NO file uploads
- NO date_of_birth

## Deploy on Server (3 Commands)

```bash
# 1. SSH to server
ssh aboksdfs@server350.web-hosting.com

# 2. Pull latest code
cd /home/aboksdfs/app.pointwave.ng
git pull origin main

# 3. Clear caches
php artisan route:clear
php artisan config:clear
php artisan cache:clear
```

## Test After Deployment

Create a test file on the server:

```bash
nano test_api_simple.php
```

Paste this:

```php
<?php
$ch = curl_init('https://app.pointwave.ng/api/v1/customers');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'first_name' => 'Test',
    'last_name' => 'User',
    'email' => 'test_' . time() . '@example.com',
    'phone_number' => '080' . rand(10000000, 99999999)
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer d8a3151a8993c157c1a4ee5ecda8983107004b1fbdec3f55011f1b7ee4e63f517b7403e021e96995e4c5c90aab834ea70845672dbe6ccf7910dda45c',
    'x-api-key: 7db8dbb3991382487a1fc388a05d96a7139d92ba',
    'x-business-id: 3450968aa027e86e3ff5b0169dc17edd7694a846',
    'Content-Type: application/json',
    'Idempotency-Key: test_' . uniqid() . '_' . time()
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response: $response\n";

$data = json_decode($response, true);
if ($httpCode === 201 && $data['status'] === 'success') {
    echo "\n✅ SUCCESS! Customer created: " . $data['data']['customer_id'] . "\n";
} else {
    echo "\n❌ FAILED: " . ($data['message'] ?? 'Unknown error') . "\n";
}
```

Run the test:

```bash
php test_api_simple.php
```

## Expected Result

```
HTTP Code: 201
Response: {"status":"success","message":"Customer created successfully","data":{"customer_id":"cust_abc123...","email":"test_...@example.com",...}}

✅ SUCCESS! Customer created: cust_abc123xyz456
```

## What Changed

### Before (Complex):
```json
{
  "email": "john@example.com",
  "first_name": "John",
  "last_name": "Doe",
  "phone": "08012345678",
  "bvn": "22222222222",
  "address": "123 Street",
  "state": "Lagos",
  "city": "Ikeja",
  "postal_code": "100001",
  "date_of_birth": "1990-01-01",
  "id_type": "bvn",
  "id_number": "22222222222"
}
```

### After (Simple):
```json
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "phone_number": "08012345678"
}
```

## Files Changed

1. ✅ `app/Http/Controllers/API/V1/MerchantApiController.php` - Simplified createCustomer()
2. ✅ `SEND_THIS_TO_DEVELOPERS.md` - Complete developer guide
3. ✅ `API_V1_SIMPLE_CUSTOMER_CREATION.md` - Deployment summary

## Next Steps After Testing

1. ✅ Verify test passes
2. ✅ Send `SEND_THIS_TO_DEVELOPERS.md` to developers
3. ✅ Developers can now integrate easily with just 4 fields

## Summary

The API is now much simpler:
- Create customer with 4 fields
- Create virtual account
- Start receiving payments
- KYC only when needed for higher limits

This matches industry standards (Paystack, Flutterwave) where basic account creation is simple and KYC is optional.
