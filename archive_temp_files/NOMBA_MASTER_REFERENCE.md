# 🏗️ Master Nomba API Reference Guide (A to Z)

This document is the definitive technical reference for the Nomba API, containing granular details on every core endpoint, required parameters, and response schemas.

---

## 🚀 1. Core Integration Specs
- **Production Base URL:** `https://api.nomba.com`
- **Sandbox Base URL:** `https://sandbox.nomba.com`
- **Required Headers:**
  - `Authorization: Bearer <TOKEN>`
  - `accountId: <BUSINESS_UUID>`
  - `Content-Type: application/json`

---

## 🔐 2. Authentication Module
Securely obtain and manage access tokens.

### A. Obtain Access Token
`POST /v1/auth/token/issue`

**Headers:**
- `accountId`: `string <uuid>` [Required] - The parent accountId of the business.
- `Content-Type`: `application/json`

**Body (application/json):**
- `grant_type`: `enum <string>` [Required] - options: `client_credentials`, `refresh_token`.
- `client_id`: `string` [Required] - Your 36-char Client ID.
- `client_secret`: `string` [Required] - Your 44-char secure Client Secret.

**Response (200 Success):**
```json
{
  "code": "00",
  "description": "Success",
  "data": {
    "businessId": "01a10aeb-d989-460a-bbde-9842f2b4320f",
    "access_token": "eyJhbGciOiJIUzI1NiJ9...",
    "refresh_token": "01h4gdx2tctxfjgacbdwrcvs5d1688473602892",
    "expiresAt": "2022-07-08T14:33:00Z"
  }
}
```

**Common Error Codes:**
- `400`: Request failed.
- `401`: Unauthorized.
- `403`: Forbidden.
- `404`: Record not found.
- `429`: Too many requests (Rate limited).
- `500`: Server error.

**cURL Example:**
```bash
curl --request POST \
  --url https://api.nomba.com/v1/auth/token/issue \
  --header 'Content-Type: application/json' \
  --header 'accountId: <accountid>' \
  --data '{
    "grant_type": "client_credentials",
    "client_id": "YOUR_CLIENT_ID",
    "client_secret": "YOUR_CLIENT_SECRET"
  }'
```

---

## 🏦 3. Accounts & Virtual Accounts

### A. Create Virtual Account
`POST /v1/accounts/virtual`
- **Body:**
  - `accountRef`: `string` (Your unique reference) [Required]
  - `accountName`: `string` (Name displayed to the payer) [Required]
  - `currency`: `string` ("NGN") [Optional]
- **Sub-Account Creation:** `POST /v1/accounts/virtual/{subAccountId}`

### B. List Virtual Accounts
`POST /v1/accounts/virtual/list`
- **Filters:** `accountName`, `accountRef`, `bankAccountNumber`, `expired`.

---

## 💳 4. Online Checkout & Card Payments

### A. Create Checkout Order
`POST /v1/checkout/order`
- **Body:**
  - `order`: `object` { `amount`, `currency`, `customerEmail`, `callbackUrl` } [Required]
  - `tokenizeCard`: `boolean` (Set to true for recurring payments) [Optional]

### B. Tokenized Card Payment
`POST /v1/checkout/tokenized-card-payment`
- **Purpose:** Charge a card previously saved via tokenization.

---

## 📝 5. Direct Debits & Mandates
Automated recurring collection from bank accounts.

| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `/v1/direct-debits` | `POST` | Create a Direct Debit Mandate. |
| `/v1/direct-debits/{id}/status` | `GET` | Get Mandate Status (Active/Inactive). |
| `/v1/direct-debits/debit` | `POST` | Debit a mandate (Execution). |

**Mandate Payload:** `customerAccountNumber`, `bankCode`, `customerName`, `amount`, `frequency`, `startDate`, `endDate`.

---

## 💸 6. Transfers & Global Payouts

### A. Bank Account Lookup (Name Enquiry)
`POST /v1/transfers/bank/lookup`
- **Body:** `accountNumber`, `bankCode`.

### B. Bank Transfer (Parent)
`POST /v1/transfers/bank`
- **Body:** `amount`, `bankCode`, `accountNumber`, `accountName`, `narration`.

### C. Global Payout (Authorize Transfer)
`POST /v1/global-payout/authorize-transfer`
- **Prerequisite:** Fetch exchange rates and use `exchangeRateId`.

---

## 🌍 7. Global Collection (DRC)
Collection in the Democratic Republic of Congo.

- **Mobile Money Inflow:** `POST /v1/global-collection/mobile-money/initiate`
- **Payload:** `phoneNumber`, `amount`, `currency`, `topupVendor` (Mpesa, Airtel, Orange).

---

## 📟 8. Terminal Management (Cloud POS)

| Endpoint | Method | Purpose |
| :--- | :--- | :--- |
| `/v1/accounts/terminals` | `GET` | Fetch all terminals on the account. |
| `/v1/terminals/assign` | `POST` | Assign terminal to a business/parent. |
| `/v1/terminals/assign/{subId}` | `POST` | Assign terminal to a specific sub-account. |
| `/v1/terminals/payment-request/{id}` | `POST` | Push a charge to a physical terminal. |

---

## 📊 9. Transactions & History Audit

| Endpoint | Method | Purpose |
| :--- | :--- | :--- |
| `/v1/transactions/accounts` | `GET` | Fetch transactions for parent account. |
| `/v1/transactions/accounts/{subId}` | `GET` | Fetch transactions for sub-account. |
| `/v1/transactions/{ref}` | `GET` | Audit a specific transaction by reference. |

---

## 📡 10. Webhook Events Reference
Verify the Source using `x-nomba-signature`.

| Event Type | Trigger |
| :--- | :--- |
| `payment_success` | Inbound checkout or virtual account payment successful. |
| `payout_success` | Outbound transfer or bill payment successful. |
| `payout_failed` | Outbound transfer failed (funds returned to wallet). |
| `payment_reversal` | Inbound payment reversed/refunded. |

---

## 📡 11. Bill Payments, Airtime & Betting

- **Airtime Purchase:** `POST /v1/bill/topup`
- **Data Vending:** `POST /v1/bill/data`
- **Electricity (Disco):** `POST /v1/bill/electricity`
- **Betting:** `GET /v1/bill/betting/providers` -> `POST /v1/bill/betting`

---

> [!CAUTION]
> **Idempotency:** Always use a unique `accountRef` for every transaction attempt to ensure no double-debits occur during network retries.
