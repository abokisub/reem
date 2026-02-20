# Update KYC Service to Deduct Charges

## Summary

The KYC Service needs to be updated to:
1. Check company wallet balance before verification
2. Deduct appropriate charge based on verification type
3. Create transaction record for the charge
4. Only proceed with verification if payment successful

## Charge Structure

| Verification Type | Service Name | Amount |
|-------------------|--------------|--------|
| Enhanced BVN | enhanced_bvn | ₦100 |
| Basic BVN | basic_bvn | ₦50 |
| Enhanced NIN | enhanced_nin | ₦100 |
| Basic NIN | basic_nin | ₦50 |
| Bank Account | bank_account_verification | ₦50 |

## Implementation

The updated methods will:

1. **Get charge amount** from service_charges table
2. **Check wallet balance** - ensure company has sufficient funds
3. **Deduct charge** - create debit transaction
4. **Call EaseID API** - perform actual verification
5. **Cache result** - store verification data
6. **Return response** - include charge information

## Flow

```
API Call → Check Balance → Deduct Charge → Verify with EaseID → Cache Result → Return
```

If balance insufficient: Return error immediately
If EaseID fails: Charge is still deducted (API call was made)

---

**Status:** Ready to implement
**Files to update:** `app/Services/KYC/KycService.php`
