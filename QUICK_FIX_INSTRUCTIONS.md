# Quick Fix Instructions

## Issue Fixed

The `php artisan kyc:setup-charges` command was failing because it was missing the `display_name` field that the `service_charges` table requires.

## What to Do Now

### Step 1: Pull Latest Fix

```bash
cd /home/aboksdfs/app.pointwave.ng
git pull origin main
```

### Step 2: Run Setup Command Again

```bash
php artisan kyc:setup-charges
```

This should now work without errors and create the missing KYC charges:
- face_recognition
- liveness_detection
- blacklist_check
- credit_score
- loan_features

### Step 3: Verify Configuration

```bash
php artisan kyc:test-charges
```

You should see:
- ✅ All 10 KYC charges configured
- ✅ EaseID API configured (it's already in your .env)
- ✅ Companies and wallets ready

### Step 4: Clear Caches

```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

---

## About EaseID Configuration

Your `.env` already has EaseID configured:
```
EASEID_APP_ID=K8865857536
EASEID_MERCHANT_ID=K8865857536
EASEID_PRIVATE_KEY="MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQDLJkOOAwZwrS6rznRr4OuEc1H714EFL2qVP2Dvi8RJetTlpfM5XW7onssBHtOFjhKt3/MUvigY+3bZwAArkO5BVDYdtpthn6wkNhC0IglCeOAluXPnXmLnrbe8FovCXQDpmBHA1c9sPMmgRIyecUq7W+9NdTs+Zpq/fZ5gK5GhwS4ULVjzxwcz3sRurIRCaSDE2VrVXIro210Qp67kaBXOXj2Txnb/Zk0h8/NlElMpnibaEM8tsB8NiRNzaSKSlfTTVo5KarkIzPQx5/sIOS9FX2/n2X3N7hqjOYsnYaabdk+oit8qRkez8OQIvr2i3zY5/gyHEZQ800B2kvUkN8oXAgMBAAECggEAD7mhhw+i4vv03eW4S1V4xaLrmLQAz7bw6CakyUYnZoy0iCZFYo5h9G2+RxLsyqzJs13fgh6KGgz1ETv0h2rLlpD/M2OcOX1TpOXuexMbZmLW7vShDSrYOxjOXSfdn6j1Vh+oWCX0zWsTLG3B+M3KWCCMsJE/icAFgIcnyEf3GO8ou1NnaQ5uPnNHrsU44ugOuwNh0JnEpWjked36Q7YMuOeizCkHnLgz8VzajkC5K3kCVJCz7kpE9hhI856yJxL/MwPodvlCPB80xouCN8Fal35EwqAHhO4e5S/uENv90MEG+MiOj1G5EgF2CaOgwhN3BL1rrGjUskrdGa5qk3NJyQKBgQD680WHBa/a9IXx8NjIeuFtESkOepC79z0tS+k2jMLFADCz/sTcz9SdVUznlXv5A2GGhoYJg2KljT1wl3K/dutUoLXVa934pjNdb2q+DAboXQAlZwMUS9FdVp2b2DreqOr/0qVG5PESaSthE46VW5SrDGNvM4U2QXxyRMpmXqrANQKBgQDPPMEgx+Xa6B67ekypJuiPKbdsGM+ufKrbz1GDaNZ+1ISm6VbLs/GIj5ouoAlv/XQYZlz6GM6Y697iszS4gONiE7A9l/c36dG1jgsnHwmi9dpbqofiUbx24zNC4w5seG5cG49V9+hws3S3vLyTlAjV9E5X/5o9OZUbk9uerKECmwKBgQC5tZvnTvMTss8I+3ZB7oWyQ/fBKjy/jTits7aTUtm0Fe701P30dqzd/ckavEnxPmpGtnisw5kV8I7eKoWVYTjH+OJ3XQr4Pm5Wn+Q7XgUioehAxZnGxFDcpQOf2AXAzvqRdN4wt64bNM8QWPu0VgCQEGvpWBQl0ZJ5saSi2z27XQKBgBx5n5vOe8HZdSeThWcUpo3NUJu0yQyTqrJrSSsCQ77HmraIh1mUDxMRkEDp0oIl1EbqAcqHkBOpDUYfE5Zqd1PpmqTL9bckFKGas+ObOyq+F1PTGbq6OmgnjcqaAkhbP+a+DrLkTnb14YrBjzPD+nbTi9RBlcLl35wbc+jYlYMTAoGAV5/GE7nmRlk+5dWE189sffG/UfeXTnbwPmfLRfuhqI7qEp6+kHkNu2+IzKgpRp7GBZG0gN1ZrQU+GMqkoK6ySlx/ZqHGulb0Jh2q9qH3hUPWk3Vq6caK+R/Er9felxys64Fp+xnH372HbIqrIawsPxWjSPStDGKhU7uuCGleV1w="
EASEID_BASE_URL=https://open-api.easeid.ai
```

The test command was showing "not configured" because it was checking the config cache. After running `php artisan config:clear`, it should detect the configuration correctly.

---

## Current KYC Charges in Your Database

From the test output, you already have these charges configured:
- bank_account_verification: ₦65.00
- basic_bvn: ₦10.00
- basic_nin: ₦35.00
- blacklist: ₦550.00
- credit_score: ₦65.00
- enhanced_bvn: ₦25.00
- enhanced_nin: ₦45.00
- face_comparison: ₦10.00
- liveness_detection: ₦40.00
- loan_feature: ₦90.00

The setup command will add any missing charges and skip existing ones.

---

## Test the System

After setup, test with a real API call:

```bash
# Test BVN verification (Company #1 is verified, so it will charge)
curl -X POST https://app.pointwave.ng/api/v1/kyc/verify-bvn \
  -H 'Authorization: Bearer d8a3151a8993c157c1a4ee5ecda8983107004b1fbdec3f55011f1b7ee4e63f517b7403e021e96995e4c5c90aab834ea70845672dbe6ccf7910dda45c' \
  -H 'x-api-key: 7db8dbb3991382487a1fc388a05d96a7139d92ba' \
  -H 'x-business-id: 3450968aa027e86e3ff5b0169dc17edd7694a846' \
  -H 'Content-Type: application/json' \
  -d '{"bvn":"22154883751"}'
```

Expected response:
```json
{
  "status": true,
  "message": "BVN verified successfully",
  "data": { ... },
  "charged": true,
  "charge_amount": 25,
  "transaction_reference": "KYC_ENHANCED_BVN_..."
}
```

Check wallet balance after:
```bash
php artisan tinker
>>> DB::table('company_wallets')->where('company_id', 1)->value('balance');
>>> exit
# Should be: 199 - 25 = 174
```

---

## Summary

✅ Fixed the setup command (added display_name field)
✅ EaseID is already configured in your .env
✅ You have 2 companies with verified status (will be charged)
✅ Both companies have wallet balance
✅ Ready to test KYC API endpoints

**Next:** Pull the fix and run `php artisan kyc:setup-charges` again!
