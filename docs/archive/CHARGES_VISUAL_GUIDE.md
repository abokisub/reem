# 📊 Charges Visual Guide

## The Two Different Charges Explained Visually

---

## Charge 1: PalmPay Virtual Account Charge (0.5%)

### This is YOUR PLATFORM REVENUE! 💰

```
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│  CUSTOMER                                                   │
│  (End user paying for goods/services)                       │
│                                                             │
│  Sends ₦100                                                │
│       │                                                     │
│       ↓                                                     │
│                                                             │
│  PALMPAY VIRTUAL ACCOUNT                                    │
│  Account: 6644694207                                        │
│  (Belongs to Company via your platform)                     │
│                                                             │
│  Receives ₦100                                             │
│       │                                                     │
│       ↓                                                     │
│                                                             │
│  YOUR PLATFORM (Webhook Handler)                            │
│  Calculates: ₦100 × 0.5% = ₦0.50                          │
│       │                                                     │
│       ├─────────────────┬──────────────────┐              │
│       ↓                 ↓                  ↓              │
│                                                             │
│  COMPANY WALLET    PLATFORM REVENUE    TRANSACTION         │
│  +₦99.50          +₦0.50              RECORDED            │
│                                                             │
└─────────────────────────────────────────────────────────────┘

RESULT:
✅ Company gets: ₦99.50
✅ You earn: ₦0.50
✅ Automatic: Yes
✅ Manual work: None
```

### Where to Configure
- Admin Page: `/secure/discount/other`
- Section: "PalmPay Virtual Account Charge"
- Current: 0.5% capped at ₦500

---

## Charge 2: Funding with Bank Transfer (₦100)

### This is a PROCESSING FEE for manual funding

```
┌─────────────────────────────────────────────────────────────┐
│                                                             │
│  COMPANY                                                    │
│  (Your client - the business)                               │
│                                                             │
│  Transfers ₦10,000 from GTBank                             │
│       │                                                     │
│       ↓                                                     │
│                                                             │
│  YOUR BANK ACCOUNT                                          │
│  (Your company's bank account)                              │
│                                                             │
│  Receives ₦10,000                                          │
│       │                                                     │
│       ↓                                                     │
│                                                             │
│  ADMIN (You)                                                │
│  Manually confirms payment                                  │
│  Charges ₦100 processing fee                               │
│       │                                                     │
│       ├─────────────────┬──────────────────┐              │
│       ↓                 ↓                  ↓              │
│                                                             │
│  COMPANY WALLET    PROCESSING FEE    TRANSACTION           │
│  +₦9,900          +₦100              RECORDED             │
│                                                             │
└─────────────────────────────────────────────────────────────┘

RESULT:
✅ Company wallet: ₦9,900
✅ You earn: ₦100
✅ Automatic: No
✅ Manual work: Yes (you must confirm)
```

### Where to Configure
- Admin Page: `/secure/discount/banks`
- Section: "Funding with Bank Transfer"
- Current: ₦100 flat fee

---

## Side-by-Side Comparison

```
┌──────────────────────────┬──────────────────────────┐
│  PalmPay VA Charge       │  Bank Transfer Charge    │
│  (0.5%)                  │  (₦100)                  │
├──────────────────────────┼──────────────────────────┤
│                          │                          │
│  WHO PAYS?               │  WHO PAYS?               │
│  Customer → Company      │  Company → Platform      │
│  (Company pays from      │  (Company pays directly) │
│   what they receive)     │                          │
│                          │                          │
│  WHEN?                   │  WHEN?                   │
│  Every payment           │  Only when funding       │
│  Automatic               │  Manual                  │
│                          │                          │
│  HOW MUCH?               │  HOW MUCH?               │
│  0.5% of payment         │  ₦100 flat               │
│  (max ₦500)             │  (any amount)            │
│                          │                          │
│  FREQUENCY?              │  FREQUENCY?              │
│  High (many payments)    │  Low (occasional)        │
│                          │                          │
│  YOUR WORK?              │  YOUR WORK?              │
│  None (automatic)        │  Manual confirmation     │
│                          │                          │
│  REVENUE?                │  REVENUE?                │
│  Main income source      │  Small processing fee    │
│                          │                          │
└──────────────────────────┴──────────────────────────┘
```

---

## Real Example: One Day of Business

### Company ABC has these transactions:

```
TIME    EVENT                           CHARGE TYPE              YOU EARN
────────────────────────────────────────────────────────────────────────
09:00   Customer pays ₦500             PalmPay VA (0.5%)        ₦2.50
09:15   Customer pays ₦1,000           PalmPay VA (0.5%)        ₦5.00
09:30   Customer pays ₦2,000           PalmPay VA (0.5%)        ₦10.00
10:00   Customer pays ₦5,000           PalmPay VA (0.5%)        ₦25.00
11:00   Company funds ₦50,000          Bank Transfer            ₦100.00
12:00   Customer pays ₦10,000          PalmPay VA (0.5%)        ₦50.00
14:00   Customer pays ₦3,000           PalmPay VA (0.5%)        ₦15.00
16:00   Customer pays ₦8,000           PalmPay VA (0.5%)        ₦40.00
────────────────────────────────────────────────────────────────────────
TOTAL                                                           ₦247.50

Breakdown:
- PalmPay VA Charges: ₦147.50 (7 payments, automatic)
- Bank Transfer Fee: ₦100.00 (1 funding, manual)
```

---

## Which Charge Applies When?

### Scenario 1: Customer Buys Product
```
Customer → Pays ₦1,000 → Company's PalmPay Account
                              ↓
                    PalmPay VA Charge (0.5%)
                              ↓
                    Company gets ₦995
                    You earn ₦5
```
**Uses: PalmPay Virtual Account Charge**

### Scenario 2: Company Needs More Balance
```
Company → Transfers ₦20,000 → Your Bank Account
                                    ↓
                          Bank Transfer Charge (₦100)
                                    ↓
                          Company wallet gets ₦19,900
                          You earn ₦100
```
**Uses: Funding with Bank Transfer Charge**

### Scenario 3: Customer Pays Again
```
Customer → Pays ₦5,000 → Company's PalmPay Account
                              ↓
                    PalmPay VA Charge (0.5%)
                              ↓
                    Company gets ₦4,975
                    You earn ₦25
```
**Uses: PalmPay Virtual Account Charge**

---

## Common Questions

### Q: Why do I need both?
**A:** They serve different purposes:
- PalmPay VA = Your main business (automatic revenue)
- Bank Transfer = Backup funding method (manual)

### Q: Can I disable Bank Transfer?
**A:** Yes, but keep it as backup in case PalmPay has issues.

### Q: Which makes more money?
**A:** PalmPay VA Charge - it's your main revenue stream!

### Q: Do companies pay both?
**A:** No! They pay:
- PalmPay VA Charge: When CUSTOMERS pay them
- Bank Transfer Charge: When THEY fund their wallet manually

### Q: Can I change the rates?
**A:** Yes!
- PalmPay VA: `/secure/discount/other`
- Bank Transfer: `/secure/discount/banks`

---

## Recommended Settings

### For Maximum Revenue
```
PalmPay VA Charge: 1% (capped at ₦1,000)
Bank Transfer: ₦200

Why?
- Higher percentage = more revenue per transaction
- Higher bank fee = encourages PalmPay usage
```

### For Competitive Pricing
```
PalmPay VA Charge: 0.5% (capped at ₦500)  ← CURRENT
Bank Transfer: ₦100  ← CURRENT

Why?
- Competitive with other platforms
- Attracts more companies
- Still profitable
```

### For High Volume
```
PalmPay VA Charge: 0.3% (capped at ₦300)
Bank Transfer: ₦50

Why?
- Lower fees = more transactions
- Volume makes up for lower percentage
```

---

## Summary in One Sentence

**PalmPay VA Charge (0.5%)** = Your platform fee when customers pay companies (AUTOMATIC)

**Bank Transfer Charge (₦100)** = Processing fee when companies fund their wallet manually (MANUAL)

---

## What Should You Do?

### ✅ KEEP BOTH
- PalmPay VA as PRIMARY (automatic, main revenue)
- Bank Transfer as BACKUP (manual, occasional)

### ✅ PROMOTE PalmPay VA
- Show it first in company dashboard
- Make it easy to use
- This is your main business!

### ✅ USE Bank Transfer for
- Backup when PalmPay is down
- Large amounts (companies prefer flat ₦100 vs percentage)
- Companies without PalmPay access

---

**Still Confused?**

Think of it this way:
- **PalmPay VA Charge** = Like Stripe/Paystack fees (automatic on every payment)
- **Bank Transfer Charge** = Like a wire transfer fee (manual, occasional)

