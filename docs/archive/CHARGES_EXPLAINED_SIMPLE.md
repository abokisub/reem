# 🎯 Charges Explained - Simple Guide

## The Confusion: Two Different Charges

You have TWO different charges that seem similar but are for DIFFERENT things:

---

## 1. PalmPay Virtual Account Charge (0.5%)

### What Is It?
This is YOUR platform fee - the money YOU earn when customers pay into virtual accounts.

### When Is It Charged?
When someone sends money TO a company's PalmPay virtual account.

### Example Flow:
```
Customer sends ₦100 to Company's PalmPay account (6644694207)
  ↓
PalmPay receives ₦100
  ↓
YOUR PLATFORM charges 0.5% = ₦0.50
  ↓
Company receives ₦99.50 in their wallet
  ↓
YOU keep ₦0.50 as platform revenue
```

### Who Pays?
The COMPANY pays (deducted from what they receive)

### Where Configured?
`/secure/discount/other` - "PalmPay Virtual Account Charge"

### Purpose?
This is YOUR BUSINESS REVENUE - how you make money from the platform!

---

## 2. Funding with Bank Transfer (₦100)

### What Is It?
This is a charge for when companies want to fund their wallet via bank transfer (NOT PalmPay virtual account).

### When Is It Charged?
When a company manually transfers money from their bank to fund their wallet.

### Example Flow:
```
Company transfers ₦10,000 from GTBank to your platform bank account
  ↓
You manually confirm the transfer
  ↓
You charge ₦100 as processing fee
  ↓
Company's wallet is credited ₦9,900
  ↓
You keep ₦100 as processing fee
```

### Who Pays?
The COMPANY pays

### Where Configured?
`/secure/discount/banks` - "Funding with Bank Transfer"

### Purpose?
To cover the cost of manual processing and bank charges for direct bank transfers.

---

## Key Differences

| Feature | PalmPay VA Charge | Funding with Bank Transfer |
|---------|-------------------|---------------------------|
| **What** | Platform revenue on payments | Processing fee for manual funding |
| **When** | Automatic (every payment) | Manual (when company funds wallet) |
| **How** | Via PalmPay virtual account | Via direct bank transfer |
| **Amount** | 0.5% (percentage) | ₦100 (flat fee) |
| **Frequency** | Every transaction | Only when funding |
| **Automation** | Fully automatic | Manual confirmation needed |

---

## Real-World Scenarios

### Scenario 1: Customer Pays Company
```
1. Customer pays ₦1,000 to Company's PalmPay account
2. PalmPay sends webhook to your platform
3. YOUR PLATFORM automatically:
   - Calculates charge: ₦1,000 × 0.5% = ₦5
   - Credits company wallet: ₦995
   - Records platform revenue: ₦5
4. Company sees ₦995 in their wallet
5. You earned ₦5
```
**This uses: PalmPay Virtual Account Charge (0.5%)**

### Scenario 2: Company Funds Their Wallet
```
1. Company transfers ₦50,000 from their GTBank to your bank
2. You receive bank alert
3. You manually confirm in admin panel
4. You charge ₦100 processing fee
5. Company wallet credited: ₦49,900
6. You keep ₦100
```
**This uses: Funding with Bank Transfer (₦100)**

---

## Which One Should You Use?

### PalmPay Virtual Account Charge (0.5%)
✅ **USE THIS** for your main business model
- Automatic
- Scales with transaction volume
- No manual work
- This is your PRIMARY revenue source

### Funding with Bank Transfer (₦100)
✅ **USE THIS** only for special cases
- When PalmPay is down
- When company prefers bank transfer
- For large amounts (to avoid percentage fee)
- Requires manual confirmation

---

## Recommended Setup

### For Most Companies
**Primary Method**: PalmPay Virtual Account
- Charge: 0.5% (capped at ₦500)
- Automatic
- No manual work
- Instant crediting

**Backup Method**: Bank Transfer
- Charge: ₦100 flat
- Manual confirmation
- Use only when needed

---

## Current Configuration

### PalmPay Virtual Account Charge
```
Location: /secure/discount/other
Type: PERCENT
Value: 0.5%
Cap: ₦500
Status: Active

Examples:
- ₦100 payment → ₦0.50 fee → ₦99.50 to company
- ₦10,000 payment → ₦50 fee → ₦9,950 to company
- ₦100,000 payment → ₦500 fee (capped) → ₦99,500 to company
```

### Funding with Bank Transfer
```
Location: /secure/discount/banks
Type: FLAT
Value: ₦100
Cap: N/A
Status: Active

Examples:
- ₦10,000 transfer → ₦100 fee → ₦9,900 to wallet
- ₦50,000 transfer → ₦100 fee → ₦49,900 to wallet
- ₦1,000,000 transfer → ₦100 fee → ₦999,900 to wallet
```

---

## Should You Disable One?

### Option 1: Keep Both (RECOMMENDED)
✅ PalmPay VA as primary (automatic)
✅ Bank Transfer as backup (manual)

**Pros**:
- Flexibility for companies
- Backup when PalmPay has issues
- Can handle large amounts via bank

**Cons**:
- Need to manage two funding methods

### Option 2: PalmPay Only
✅ PalmPay VA only
❌ Disable Bank Transfer

**Pros**:
- Fully automatic
- No manual work
- Simpler for companies

**Cons**:
- No backup if PalmPay is down
- Companies can't use bank transfer

### Option 3: Bank Transfer Only
❌ Disable PalmPay VA
✅ Bank Transfer only

**Pros**:
- Full control
- No dependency on PalmPay

**Cons**:
- ALL funding is manual
- Very slow
- Not scalable
- NOT RECOMMENDED

---

## My Recommendation

**Keep BOTH but promote PalmPay Virtual Account as primary:**

1. **PalmPay Virtual Account (0.5%)**
   - Main funding method
   - Show this prominently in company dashboard
   - Automatic and instant
   - This is your main revenue

2. **Bank Transfer (₦100)**
   - Backup/alternative method
   - Show as "Alternative Funding Method"
   - Use only when needed
   - Manual confirmation required

---

## How Companies See It

### Company Dashboard
```
┌─────────────────────────────────────────┐
│  Fund Your Wallet                       │
├─────────────────────────────────────────┤
│                                         │
│  ⭐ Recommended: PalmPay Virtual Account│
│  Account: 6644694207                    │
│  Fee: 0.5% (max ₦500)                  │
│  Processing: Instant                    │
│  [Copy Account Number]                  │
│                                         │
│  ─────────────────────────────────────  │
│                                         │
│  Alternative: Bank Transfer             │
│  Fee: ₦100 flat                        │
│  Processing: Manual (up to 24 hours)   │
│  [View Bank Details]                    │
│                                         │
└─────────────────────────────────────────┘
```

---

## Summary

**PalmPay Virtual Account Charge (0.5%)**
- For: Payments INTO virtual accounts
- When: Automatic (every payment)
- Purpose: Your platform revenue
- Example: Customer pays ₦100 → Company gets ₦99.50 → You earn ₦0.50

**Funding with Bank Transfer (₦100)**
- For: Manual wallet funding via bank
- When: Company transfers from their bank
- Purpose: Processing fee for manual work
- Example: Company transfers ₦10,000 → Wallet gets ₦9,900 → You earn ₦100

**They are DIFFERENT charges for DIFFERENT purposes!**

---

## Quick Decision Guide

**Question**: Should I keep both?
**Answer**: YES! Keep both.

**Question**: Which is more important?
**Answer**: PalmPay Virtual Account (0.5%) - this is your main business!

**Question**: Can I disable Bank Transfer?
**Answer**: Yes, but keep it as backup.

**Question**: Can I change the percentages?
**Answer**: Yes! Go to admin pages and adjust.

---

**Need Help?**
- PalmPay VA Charge: `/secure/discount/other`
- Bank Transfer Charge: `/secure/discount/banks`
- Test: `php test_complete_charges_system.php`

