# 🏛️ Nomba API Reference Guide (A to Z) - Complete Edition

This document is a comprehensive, deep-dive reference for the Nomba API, covering all aspects from authentication to specialized payment collection and terminal management.

---

## 🚀 1. Core Integration Basics
- **Base URLs:**
  - Sandbox: `https://sandbox.nomba.com`
  - Production: `https://api.nomba.com`
- **Mandatory Headers:**
  - `Authorization: Bearer <ACCESS_TOKEN>`
  - `accountId: <UUID>` (Your Parent Business ID)
  - `Content-Type: application/json`

---

## 🔐 2. Authentication Flow
Endpoints for secure OAuth2 token management.

| Endpoint | Method | Purpose |
| :--- | :--- | :--- |
| `/v1/auth/token/issue` | `POST` | Exchange API keys for access & refresh tokens. |
| `/v1/auth/token/refresh` | `POST` | Renew access token using refresh token. |
| `/v1/auth/token/revoke` | `POST` | Invalidate an active token for security. |

---

## 💰 3. Payments & Collection (Nigeria & DRC)

### A. Nigeria Collection (Checkout & Direct)
| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `/v1/checkout/order` | `POST` | Create a checkout order/link for consumers. |
| `/v1/checkout/order/{ref}` | `GET` | Fetch details of a checkout order. |
| `/v1/checkout/order/{ref}/verify` | `POST` | Verify the final status of a payment. |

### B. Virtual Accounts (Permanent & Temporary)
| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `/v1/accounts/virtual` | `POST` | Create a virtual account for collection. |
| `/v1/accounts/virtual/{ref}` | `GET` | Fetch metadata for a specific virtual account. |
| `/v1/accounts/virtual/list` | `GET` | List all accounts (supports pagination). |

### C. DRC Collection (Mobile Money & Card)
Nomba supports mobile money (Mpesa, Airtel, Orange) and card collection in the Democratic Republic of Congo.

---

## 💸 4. Transfers & Global Payouts

### A. Nigeria Payout (Local Transfers)
| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `/v1/transfers` | `POST` | Initiate an instant bank transfer. |
| `/v1/transfers/banks` | `GET` | List supported banks and institution codes. |
| `/v1/transfers/account-lookup` | `POST` | Perform name enquiry for bank accounts. |
| `/v1/transfers/bulk` | `POST` | Handle multiple payouts in a single request. |

### B. Global Payout (International)
| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `/v1/global-payouts` | `POST` | Authorize an international transfer. |
| `/v1/global-payouts/rates` | `GET` | Fetch and lock exchange rates (5-min lock). |
| `/v1/global-payouts/methods` | `GET` | List payment methods/corridors (UK, CA, EU, DRC). |

---

## 📟 5. Terminal Management & Cloud POS
Control physical POS terminals and push payments directly to them.

| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `/v1/accounts/terminals` | `GET` | List all terminals linked to the business. |
| `/v1/terminals/assign` | `POST` | Link a terminal to a specific sub-account. |
| `/v1/terminals/assign/{subId}` | `POST` | Bulk assign terminals to a sub-account. |
| `/v1/terminals/payment-request/{id}` | `POST` | Push a payment request to a POS screen. |

---

## 📊 6. Transactions, Balances & History
Manage and audit your business financials.

| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `/v1/accounts/balance` | `GET` | Fetch live wallet balance (Parent/Sub). |
| `/v1/transactions` | `GET` | Fetch historical transactions (with filters). |
| `/v1/transactions/{ref}` | `GET` | Detailed audit for a specific reference. |

---

## 🧾 7. Bill Payments, Airtime & Data
Vend utility services programmatically.

| Endpoint | Method | Description |
| :--- | :--- | :--- |
| `/v1/bills/providers/airtime` | `GET` | List airtime/data providers. |
| `/v1/bills/purchase/airtime` | `POST` | Purchase airtime/data for a recipient. |
| `/v1/bills/vendors` | `GET` | List electricity and cable TV providers. |
| `/v1/bills/validate` | `POST` | Validate customer ID (Meter/IUC). |

---

## 📡 8. Webhooks & Events
Receive real-time notifications for every transaction event.

- **Event Types:**
  - `payment_success`: Confirms inbound payment.
  - `payout_success`: Confirms outbound transfer/bill payment.
  - `payment_failed` / `payout_failed`: Error notifications.
  - `payment_reversal` / `payout_refund`: Corrective events.
- **Security:** Verify the `x-nomba-signature` header to ensure requests are authentic.

---

## 🛠️ 9. API Best Practices & Limits
- **Idempotency:** Always use a unique `accountRef` or `requestRef` for transfers to prevent duplicate charges if a request is retried.
- **Token Caching:** Do not hit `/authenticate` for every request. Tokens are long-lived; cache them and use the refresh flow.
- **Pagination:** For list endpoints, use `page` and `limit` (max 100) to safely traverse history.
- **Rate Limits:** Ensure your system handles `429 Too Many Requests` by implementing exponential backoff.

> [!TIP]
> **A to Z Integration:** Start with **Authentication**, set up **Webhooks** for reliability, and use the **Sandbox Environment** first to test error cases (Declined, 3DS, Insufficient Funds) using test cards provided in the documentation.
