# PointWave Payment Gateway - Complete Project Understanding

## Executive Summary

PointWave is a comprehensive B2B payment gateway platform built with Laravel (backend) and React (frontend). It provides merchant operations, virtual account management, transaction processing, and admin operations for managing the entire payment ecosystem.

---

## 1. SYSTEM ARCHITECTURE

### Technology Stack
- **Backend**: Laravel 9+ (PHP)
- **Frontend**: React.js with Material-UI
- **Database**: MySQL
- **Authentication**: Laravel Sanctum (Token-based)
- **Payment Providers**: PalmPay, Monnify, Wema, XixaPay
- **Transfer Providers**: Paystack, XixaPay, Monnify

### Core Components
1. **API Layer** (`routes/api.php`) - 992 lines of RESTful endpoints
2. **Web Layer** (`routes/web.php`) - SPA routing + public docs
3. **Models** - Eloquent ORM for database entities
4. **Services** - Business logic layer (PalmPay, Banking, KYC, etc.)
5. **Controllers** - Request handling and response formatting
6. **Middleware** - Authentication, authorization, system locks

---

## 2. USER ROLES & ACCESS LEVELS

### A. Merchant/Company Users (`/dashboard/*`)
- **Purpose**: Business owners using the platform for collections and disbursements
- **Access**: Company-specific data, wallet, customers, virtual accounts
- **Key Features**:
  - Business activation/KYC workflow
  - Virtual account creation for customers
  - Transaction monitoring
  - Wallet management
  - Transfer/disbursement
  - API integration and webhooks
  - Developer documentation

### B. Admin/Operations Users (`/secure/*`)
- **Purpose**: Platform operators managing the entire system
- **Access**: System-wide data, all merchants, all transactions
- **Key Features**:
  - Merchant KYC approval/rejection
  - Transaction monitoring and reconciliation
  - Settlement management
  - Fee/pricing configuration
  - Service locks and provider selection
  - Fraud monitoring
  - Compliance and audit logs
  - System configuration

### C. Customer-Care Users (`/customer/*`)
- **Purpose**: Support staff handling customer issues
- **Access**: Limited to support tickets and customer queries
- **Key Features**:
  - Support ticket management
  - Customer assistance
  - Transaction lookup

---

## 3. CORE BUSINESS FLOWS

### A. Merchant Onboarding Journey
```
1. Register → 2. Verify Email/OTP → 3. Login → 4. Business Activation (KYC)
   ↓
5. Submit Business Info → 6. Submit BVN/NIN → 7. Upload Documents
   ↓
8. Admin Review → 9. Approval/Rejection → 10. Activation Complete
   ↓
11. Access Full Platform Features
```

**KYC Sections**:
1. Business Information (name, type, category, address)
2. Account Information (settlement bank details)
3. BVN Information (director BVN verification)
4. Board Member Info (directors, shareholders)
5. Business Registration Documents (CAC, utility bills, etc.)
6. Final Activation (submit for review)

**Status Flow**: `pending` → `under_review` → `approved`/`rejected` → `active`

### B. Virtual Account Creation Flow
```
Merchant → Create Customer → Verify KYC (BVN/NIN) → Create Virtual Account
   ↓
Provider Selection (PalmPay/Monnify/Wema/XixaPay)
   ↓
Account Created → Customer Receives Account Number
   ↓
Customer Deposits → Webhook Notification → Wallet Credit
```

**Virtual Account Providers**:
- **PalmPay** (Primary) - Fast, reliable, T+1 settlement
- **Monnify** - Alternative provider
- **Wema** - Bank-based virtual accounts
- **XixaPay** - Additional provider

### C. Transaction Processing Flow
```
1. COLLECTION (Deposit):
   Customer Transfer → Virtual Account → Provider Webhook → Validate
   ↓
   Credit Company Wallet → Deduct Fee → Log Transaction → Send Webhook to Merchant

2. DISBURSEMENT (Transfer):
   Merchant Request → Validate Balance → Deduct Amount + Fee
   ↓
   Route to Provider (Paystack/XixaPay/Monnify) → Process Transfer
   ↓
   Update Status → Send Webhook → Log Transaction
```

**Transaction Types**:
- `va_deposit` - Virtual account credit
- `api_transfer` - Merchant-initiated transfer
- `company_withdrawal` - Wallet withdrawal
- `fee_charge` - Platform fee
- `kyc_charge` - KYC verification fee
- `refund` - Transaction refund
- `manual_adjustment` - Admin adjustment

**Transaction Statuses**:
- `pending` - Initiated, awaiting processing
- `processing` - In progress
- `successful` - Completed successfully
- `failed` - Failed to process
- `reversed` - Reversed/refunded

### D. Settlement Flow
```
T+0: Transaction Occurs → Funds in Provider Account
   ↓
T+1: Provider Settles (2am PalmPay) → Platform Receives Funds
   ↓
T+1: Platform Auto-Settlement (3am) → Merchant Receives Funds
   ↓
Settlement Queue → Process Batch → Update Wallet → Mark Settled
```

**Settlement Rules**:
- **Delay**: 24 hours (T+1)
- **Time**: 3:00 AM daily
- **Weekends**: Skip Saturday/Sunday, settle Monday
- **Holidays**: Skip and settle next business day
- **Minimum**: ₦100.00 (configurable per merchant)

---

## 4. DATABASE SCHEMA (Key Tables)

### Core Tables

#### `companies`
- Merchant/business entities
- Fields: `id`, `uuid`, `business_id`, `name`, `email`, `phone`, `status`, `kyc_status`
- API Keys: `api_public_key`, `api_secret_key`, `test_public_key`, `test_secret_key`
- Webhook: `webhook_url`, `webhook_secret`, `webhook_enabled`
- Settlement: `settlement_bank_name`, `settlement_account_number`, `settlement_account_name`
- KYC: `bvn`, `nin`, `director_bvn`, `director_nin`, `kyc_documents`, `directors`, `shareholders`
- Limits: `daily_limit`, `monthly_limit`, `single_transaction_limit`, `minimum_balance`

#### `company_wallets`
- Merchant wallet balances
- Fields: `id`, `company_id`, `currency`, `balance`, `ledger_balance`, `pending_balance`
- Operations: `credit()`, `debit()`, `addPending()`, `removePending()`

#### `company_users` (Customers)
- End customers created by merchants
- Fields: `id`, `company_id`, `customer_id`, `name`, `email`, `phone`, `bvn`, `nin`
- KYC: `kyc_status`, `kyc_verified_at`, `identity_type`

#### `virtual_accounts`
- Reserved accounts for customers
- Fields: `id`, `uuid`, `account_id`, `company_id`, `company_user_id`
- Account: `account_number`, `account_name`, `bank_name`, `bank_code`
- Provider: `provider`, `provider_reference`, `palmpay_customer_id`, `palmpay_reference`
- KYC: `bvn`, `nin`, `identity_type`, `kyc_source`, `kyc_upgraded`
- Status: `status` (active/inactive), `is_master`, `is_test`

#### `transactions`
- All financial transactions
- Fields: `id`, `transaction_id`, `reference`, `company_id`, `company_user_id`, `virtual_account_id`
- Type: `type` (credit/debit), `category`, `transaction_type`
- Amounts: `amount`, `fee`, `provider_fee`, `net_amount`, `total_amount`
- Status: `status`, `settlement_status`, `reconciliation_status`
- Provider: `provider`, `provider_reference`, `palmpay_reference`, `external_reference`
- Recipient: `recipient_account_number`, `recipient_account_name`, `recipient_bank_code`
- Metadata: `description`, `metadata`, `channel`, `error_message`
- Balance: `balance_before`, `balance_after`
- Flags: `is_test`, `is_refunded`, `refund_transaction_id`

#### `settlement_queue`
- Pending settlements to merchants
- Fields: `id`, `company_id`, `amount`, `status`, `batch_no`, `settlement_time`

#### `global_kyc_pool`
- Shared KYC pool for virtual account creation
- Fields: `id`, `identity_type` (bvn/nin), `identity_number`, `full_name`, `phone`, `email`
- Usage: `usage_count`, `max_usage_limit`, `is_active`, `is_blacklisted`
- Source: `source_company_id`, `director_bvn`, `director_nin`

#### `global_kyc_usage_log`
- Tracks KYC usage from pool
- Fields: `id`, `global_kyc_pool_id`, `company_id`, `virtual_account_id`, `used_at`

---

## 5. API ARCHITECTURE

### Authentication
- **Method**: Bearer Token (Laravel Sanctum)
- **Header**: `Authorization: Bearer {token}`
- **Middleware**: `auth.token` custom middleware
- **Token Storage**: `personal_access_tokens` table

### API Endpoints Structure

#### Public Endpoints (No Auth)
- `POST /api/register` - User registration
- `POST /api/login/verify/user` - Login
- `POST /api/verify/user/account` - Email verification
- `GET /api/website/app/setting` - System settings
- `GET /api/transfer/banks` - Bank list

#### Merchant Endpoints (`/api/user/*`)
- **Customer Management**:
  - `POST /api/user/customer/create` - Create customer
  - `POST /api/user/customer/update` - Update customer
  - `POST /api/user/customer/delete` - Delete customer
  
- **Virtual Accounts**:
  - `PATCH /api/user/virtual-account/status` - Update VA status
  
- **KYC**:
  - `POST /api/user/kyc/verify` - Submit KYC
  - `POST /api/user/verify-bvn` - Verify BVN
  - `POST /api/user/verify-nin` - Verify NIN
  - `GET /api/user/kyc/details` - Get KYC details
  
- **Business**:
  - `POST /api/user/activate-business` - Submit business activation
  - `POST /api/user/business/create` - Create new business
  - `POST /api/user/business/switch` - Switch active business
  
- **Transfers**:
  - `POST /api/transfer/verify` - Verify bank account
  - Transfer endpoint (needs to be added)

#### Admin Endpoints (`/api/secure/*` or `/api/system/*`)
- **Merchant Management**:
  - `GET /api/system/admin/company/verification/{id}` - Get company details
  - `POST /api/system/admin/company/document/review` - Review KYC documents
  - `POST /api/system/admin/kyc/approve/{id}/secure` - Approve KYC
  - `POST /api/system/admin/kyc/reject/{id}/secure` - Reject KYC
  
- **Transaction Management**:
  - `GET /api/system/all/ra-history/records/{id}/secure` - All transactions
  - `GET /api/system/all/history/records/{id}/secure` - Transaction history
  
- **Virtual Accounts**:
  - `GET /api/system/admin/virtual-accounts/secure` - List all VAs
  - `POST /api/system/admin/virtual-accounts/toggle-status/secure` - Toggle VA status
  - `POST /api/system/admin/virtual-accounts/delete/secure` - Delete VA
  
- **Settings**:
  - `POST /api/secure/lock/virtualaccounts/{id}/habukhan/secure` - Lock/unlock VAs
  - `POST /api/secure/selection/virtualaccounts/{id}/habukhan/secure` - Set default provider
  - `POST /api/secure/discount/other/{id}/habukhan/secure` - Update charges

### Webhook System
- **Incoming Webhooks** (from providers):
  - `POST /api/webhooks/smeplug` - SME Plug
  - `POST /api/webhooks/vtpass` - VTPass
  - `POST /api/webhooks/easyaccess` - Easy Access
  - `POST /api/webhooks/autopilot` - Autopilot
  - `POST /api/webhook/transfer/{provider}` - Transfer webhooks
  
- **Outgoing Webhooks** (to merchants):
  - Sent when transactions occur
  - Signature verification with `webhook_secret`
  - Retry mechanism for failed deliveries
  - Logged in `company_webhook_logs` table

---

## 6. FRONTEND ARCHITECTURE

### Technology
- **Framework**: React.js
- **UI Library**: Material-UI (MUI)
- **Routing**: React Router v6
- **State Management**: React Context + Hooks
- **HTTP Client**: Axios
- **Animation**: Framer Motion
- **Charts**: ApexCharts / React-ApexCharts

### Route Structure

#### Auth Routes (`/auth/*`)
- `/auth/login` - Login page
- `/auth/register` - Registration
- `/auth/register/:name` - Registration with referral
- `/auth/verify` - Email/OTP verification
- `/auth/reset-password` - Password reset
- `/auth/terms-of-service` - Terms
- `/auth/privacy-policy` - Privacy policy

#### Merchant Dashboard (`/dashboard/*`)
- `/dashboard/app` - Overview dashboard
- `/dashboard/wallet` - Wallet management
- `/dashboard/ra-transactions` - RA transaction history
- `/dashboard/customers` - Customer list
- `/dashboard/customers/new` - Create customer
- `/dashboard/customers/view/:id` - Customer details
- `/dashboard/reserved-account` - Virtual account list
- `/dashboard/reserved-account/create` - Create VA
- `/dashboard/reserved-account/:accountId` - VA details
- `/dashboard/withdraw` - Transfer/withdrawal
- `/dashboard/webhook` - Webhook events
- `/dashboard/webhook-logs` - Webhook logs
- `/dashboard/api-logs` - API logs
- `/dashboard/audit-logs` - Audit logs
- `/dashboard/developer` - API keys and settings
- `/dashboard/settings` - Account settings
- `/dashboard/support` - Support center
- `/dashboard/calculator` - Fee calculator

#### Admin Dashboard (`/secure/*`)
- `/secure/app` - Admin dashboard
- `/secure/trans/history` - All transactions
- `/secure/trans/purchase` - Purchase history
- `/secure/trans/complaints` - Complaints
- `/secure/trans/statement` - Statements
- `/secure/trans/report` - Reports
- `/secure/companies` - All merchants
- `/secure/companies/pending-kyc` - Pending KYC
- `/secure/company-fees` - Pricing management
- `/secure/customers` - All customers
- `/secure/user/automated-account` - All virtual accounts
- `/secure/pending-settlements` - Settlement queue
- `/secure/kyc-pool` - Global KYC pool
- `/secure/api/requests` - API request logs
- `/secure/webhooks` - Webhook logs
- `/secure/support` - Support center
- `/secure/calculator` - Fee calculator
- `/secure/info` - System info
- `/secure/message` - Welcome message
- `/secure/api/system` - API settings

#### Documentation (`/documentation/*`)
- `/documentation/home` - Docs home
- `/documentation/quick-start` - Quick start guide
- `/documentation/authentication` - Auth guide
- `/documentation/customers` - Customer API
- `/documentation/virtual-accounts` - VA API
- `/documentation/transfers` - Transfer API
- `/documentation/webhooks` - Webhook guide
- `/documentation/errors` - Error codes
- `/documentation/banks` - Bank list
- `/documentation/refunds` - Refund API
- `/documentation/settlement` - Settlement info
- `/documentation/rate-limiting` - Rate limits
- `/documentation/pagination` - Pagination guide
- `/documentation/sandbox` - Sandbox environment

### Key Components

#### Layouts
- `DashboardLayout` - Merchant dashboard wrapper
- `AdminLayout` - Admin dashboard wrapper
- `AuthLayout` - Authentication pages wrapper
- `DocumentationLayout` - Docs wrapper

#### Dashboard Components
- `AppWidgetSummary` - Stat cards with animations
- `DashboardChart` - Revenue/analytics charts
- `CompactRecentTransactions` - Transaction table
- `ProfitLossAnalytics` - P&L charts
- `KycAnalytics` - KYC statistics
- `CompanyNetworkBalance` - Network balances

#### Shared Components
- `Page` - Page wrapper with title
- `Iconify` - Icon component
- `Label` - Badge/chip component
- `Chart` - Chart wrapper

### State Management
- `useAuth` - Authentication context
- `useSettings` - App settings context
- `useDashboardRefresh` - Auto-refresh hook

---

## 7. BUSINESS LOGIC SERVICES

### A. PalmPay Virtual Account Service
**File**: `app/Services/PalmPay/VirtualAccountService.php`

**Responsibilities**:
- Create virtual accounts via PalmPay API
- Manage KYC requirements (BVN/NIN)
- Handle account upgrades (Tier 1 → Tier 2)
- Global KYC pool management
- Fallback to backup directors when KYC exhausted

**Key Methods**:
- `createVirtualAccount()` - Create new VA
- `upgradeVirtualAccount()` - Upgrade to Tier 2
- `getAvailableKyc()` - Get KYC from pool
- `logKycUsage()` - Track KYC usage

### B. Global KYC Service
**File**: `app/Services/GlobalKycService.php`

**Responsibilities**:
- Manage shared KYC pool across companies
- Track usage limits per KYC
- Blacklist management
- Automatic fallback when limits reached

**Key Methods**:
- `getAvailableKyc()` - Get unused KYC
- `logUsage()` - Record KYC usage
- `checkUsageLimit()` - Validate limits
- `blacklistKyc()` - Blacklist identity

### C. Transfer Service
**File**: `app/Services/TransferService.php`

**Responsibilities**:
- Route transfers to providers (Paystack/XixaPay/Monnify)
- Validate beneficiary accounts
- Calculate fees
- Process transfers
- Handle webhooks

### D. Ledger Service
**File**: `app/Services/LedgerService.php`

**Responsibilities**:
- Double-entry bookkeeping
- Record all financial transactions
- Balance reconciliation
- Audit trail

### E. Settlement Service
**File**: `app/Services/SettlementService.php`

**Responsibilities**:
- Auto-settlement processing
- T+1 settlement logic
- Weekend/holiday handling
- Batch processing
- Settlement queue management

### F. Webhook Service
**File**: `app/Services/Webhook/*`

**Responsibilities**:
- Send webhooks to merchants
- Signature generation
- Retry logic
- Delivery tracking
- DNS resolution checks

### G. Fee Service
**File**: `app/Services/FeeService.php`

**Responsibilities**:
- Calculate transaction fees
- Apply company-specific pricing
- Handle percentage vs flat fees
- Fee caps

### H. Reconciliation Service
**File**: `app/Services/ReconciliationService.php`

**Responsibilities**:
- Match provider transactions with internal records
- Identify mismatches
- Generate reconciliation reports
- Auto-resolve discrepancies

---

## 8. PAYMENT PROVIDERS INTEGRATION

### A. PalmPay (Primary)
- **Use Case**: Virtual accounts, transfers
- **Settlement**: T+1 (2am daily)
- **KYC**: BVN/NIN required
- **Limits**: ~130 VAs per director BVN
- **API**: REST API with signature verification

### B. Monnify
- **Use Case**: Virtual accounts, transfers
- **Settlement**: T+1
- **KYC**: BVN required
- **API**: REST API

### C. Wema Bank
- **Use Case**: Virtual accounts
- **Settlement**: T+1
- **KYC**: BVN required
- **API**: REST API

### D. XixaPay
- **Use Case**: Virtual accounts, transfers, account verification
- **Settlement**: T+1
- **API**: REST API

### E. Paystack
- **Use Case**: Transfers, account verification
- **API**: REST API with secret key

---

## 9. SECURITY & COMPLIANCE

### Authentication & Authorization
- **Token-based**: Laravel Sanctum
- **API Keys**: Public/Secret key pairs for merchants
- **Webhook Secrets**: HMAC signature verification
- **PIN**: Transaction PIN for sensitive operations
- **2FA**: OTP verification for critical actions

### Data Protection
- **Encryption**: Sensitive fields encrypted at rest
- **Hashing**: Passwords hashed with bcrypt
- **PII**: Minimal storage, encrypted when necessary
- **Audit Logs**: All actions logged

### Compliance
- **KYC/AML**: BVN/NIN verification required
- **Transaction Limits**: Daily/monthly/single transaction limits
- **Blacklist**: Blocked accounts and identities
- **Fraud Detection**: (Coming soon - Fraud Shield module)

### Rate Limiting
- **API**: Rate limits per endpoint
- **Webhooks**: Retry limits
- **Login**: Brute force protection

---

## 10. CURRENT STATE ANALYSIS

### ✅ COMPLETED FEATURES

1. **Authentication & User Management**
   - Registration, login, OTP verification
   - Multi-business support
   - Role-based access control

2. **Merchant Onboarding**
   - Business activation workflow
   - KYC submission (6 sections)
   - Document upload
   - Admin review and approval

3. **Virtual Account Management**
   - Create VAs for customers
   - Multiple provider support
   - KYC verification (BVN/NIN)
   - Global KYC pool system
   - Account status management

4. **Transaction Processing**
   - Deposit via virtual accounts
   - Transfer/disbursement
   - Fee calculation
   - Transaction logging
   - Webhook notifications

5. **Wallet Management**
   - Company wallets
   - Balance tracking
   - Ledger entries
   - Pending balance

6. **Admin Operations**
   - Merchant management
   - KYC approval/rejection
   - Transaction monitoring
   - Virtual account management
   - Fee configuration
   - Service locks

7. **API & Webhooks**
   - RESTful API
   - Webhook delivery
   - API logs
   - Webhook logs

8. **Dashboard**
   - Merchant dashboard (recently upgraded)
   - Admin dashboard (recently upgraded)
   - Analytics and charts
   - Real-time updates

### 🚧 PARTIALLY IMPLEMENTED

1. **Settlement System**
   - Manual settlement queue exists
   - Auto-settlement logic needs completion
   - T+1 settlement rules defined but not fully automated

2. **Reconciliation**
   - Basic reconciliation service exists
   - Automated reconciliation needs enhancement
   - Mismatch resolution workflow incomplete

3. **Refunds**
   - Refund model exists
   - Refund processing needs full implementation
   - Refund workflow UI missing

4. **Chargebacks**
   - Model exists
   - Processing logic incomplete
   - Admin UI missing

5. **Fraud Detection**
   - Fraud Shield module planned
   - Basic blacklist exists
   - Advanced fraud rules missing

6. **Compliance**
   - KYC pool exists
   - Document management incomplete
   - Audit logs partial

7. **API Center**
   - API logs exist
   - API key management UI missing
   - Rate limiting needs enhancement

8. **Finance Module**
   - Revenue tracking exists
   - Expense tracking missing
   - Financial reports incomplete

### ❌ MISSING/COMING SOON

1. **Analytics Dashboard**
   - Advanced analytics
   - Live feed
   - Real-time monitoring

2. **Failed Transaction Management**
   - Failed transaction queue
   - Retry mechanism
   - Auto-resolution

3. **Pending Transaction Management**
   - Pending queue
   - Manual intervention
   - Status updates

4. **Merchant Suspension**
   - Suspension workflow
   - Suspension reasons
   - Reactivation process

5. **Settlement Modules**
   - Today's settlements
   - Failed settlements
   - Settlement ledger

6. **Fraud Shield**
   - Fraud alerts
   - Rule engine
   - Blacklist management UI

7. **Compliance Center**
   - Document vault
   - Audit log viewer
   - Compliance reports

8. **API Key Management**
   - Key generation UI
   - Key rotation
   - Key permissions

9. **Finance Center**
   - Revenue dashboard
   - Expense tracking
   - P&L reports
   - Variance analysis

10. **System Management**
    - Role management
    - Backup system
    - Maintenance mode

11. **Merchant Features**
    - Card management (virtual cards)
    - Team management
    - Granular permissions
    - Reconciliation center

12. **Notification Center**
    - In-app notifications
    - Notification preferences
    - Delivery status

13. **Release Notes**
    - Change log
    - Feature announcements
    - Version history

---

## 11. TECHNICAL DEBT & ISSUES

### Code Quality Issues
1. **Route Mismatches**: Sidebar links don't match actual routes in some places
2. **Inconsistent Naming**: Mix of snake_case and camelCase in API
3. **Large Controllers**: Some controllers have too many responsibilities
4. **Duplicate Logic**: Fee calculation logic scattered across codebase
5. **Missing Validation**: Some endpoints lack proper validation
6. **Error Handling**: Inconsistent error response formats

### Performance Issues
1. **N+1 Queries**: Some endpoints have N+1 query problems
2. **Missing Indexes**: Database indexes need optimization
3. **Large Payloads**: Some API responses too large
4. **No Caching**: Redis/cache layer not implemented

### Security Issues
1. **API Key Exposure**: Some endpoints expose sensitive keys
2. **Rate Limiting**: Not consistently applied
3. **Input Sanitization**: Needs improvement
4. **CORS**: Configuration needs review

### UX Issues
1. **Loading States**: Inconsistent loading indicators
2. **Error Messages**: Generic error messages
3. **Empty States**: Missing empty state designs
4. **Mobile Responsiveness**: Some pages not mobile-friendly

---

## 12. INTEGRATION POINTS

### External Services
1. **PalmPay API** - Virtual accounts, transfers
2. **Monnify API** - Virtual accounts, transfers
3. **Wema Bank API** - Virtual accounts
4. **XixaPay API** - Virtual accounts, transfers, verification
5. **Paystack API** - Transfers, verification
6. **SME Plug** - Data/airtime services
7. **VTPass** - Bill payments
8. **Easy Access** - Utility services
9. **Autopilot** - Automation services

### Webhook Endpoints
- **Incoming**: Receive notifications from providers
- **Outgoing**: Send notifications to merchants
- **Signature Verification**: HMAC-SHA256
- **Retry Logic**: Exponential backoff

---

## 13. DEPLOYMENT & INFRASTRUCTURE

### Current Setup
- **Backend**: Laravel on Linux server
- **Frontend**: React SPA (build folder)
- **Database**: MySQL
- **Web Server**: Apache/Nginx
- **PHP**: 8.0+
- **Node**: 16+

### Deployment Process
1. Backend: Push to git, pull on server, run migrations
2. Frontend: Build locally, upload build folder to server
3. Database: Manual migrations

### Missing Infrastructure
- **CI/CD**: No automated deployment
- **Monitoring**: No application monitoring
- **Logging**: Basic file logging only
- **Backup**: Manual backups
- **Staging**: No staging environment
- **Load Balancing**: Single server
- **CDN**: No CDN for static assets
- **Queue Workers**: No background job processing

---

## 14. DATA FLOW DIAGRAMS

### Virtual Account Creation Flow
```
Merchant Dashboard
    ↓
Create Customer Form
    ↓
Submit Customer Data (name, email, phone, BVN/NIN)
    ↓
Backend Validation
    ↓
Check Global KYC Pool
    ↓
Select Provider (PalmPay/Monnify/Wema/XixaPay)
    ↓
Call Provider API
    ↓
Receive Account Number
    ↓
Save to Database (virtual_accounts table)
    ↓
Return Account Details to Merchant
```

### Transaction Processing Flow
```
Customer Deposits to Virtual Account
    ↓
Provider Receives Funds
    ↓
Provider Sends Webhook to Platform
    ↓
Platform Validates Webhook Signature
    ↓
Platform Identifies Virtual Account
    ↓
Platform Credits Company Wallet
    ↓
Platform Deducts Fee
    ↓
Platform Logs Transaction
    ↓
Platform Sends Webhook to Merchant
    ↓
Merchant Receives Notification
```

### Settlement Flow
```
Cron Job Runs at 3:00 AM
    ↓
Query Unsettled Transactions (>24 hours old)
    ↓
Group by Company
    ↓
Calculate Settlement Amount
    ↓
Check Minimum Amount (₦100)
    ↓
Check Weekend/Holiday
    ↓
Create Settlement Queue Entry
    ↓
Process Settlement (Transfer to Merchant Bank)
    ↓
Update Transaction Status (settled)
    ↓
Send Settlement Notification
```

---

## 15. KEY METRICS & KPIs

### Business Metrics
- **Total Merchants**: Count of active companies
- **Total Customers**: Count of end customers
- **Total Virtual Accounts**: Count of active VAs
- **Total Transactions**: Count of all transactions
- **Total Volume**: Sum of transaction amounts
- **Total Revenue**: Platform fees collected
- **Average Transaction Value**: Volume / Count
- **Success Rate**: Successful / Total transactions

### Operational Metrics
- **KYC Approval Time**: Time from submission to approval
- **Settlement Time**: Time from transaction to settlement
- **Webhook Delivery Rate**: Successful / Total webhooks
- **API Response Time**: Average API latency
- **Uptime**: Platform availability

### Financial Metrics
- **Gross Revenue**: Total fees collected
- **Net Revenue**: Revenue - Provider fees
- **Profit Margin**: (Net Revenue / Gross Revenue) * 100
- **Cost per Transaction**: Provider fees / Transaction count
- **Revenue per Merchant**: Revenue / Merchant count

---

## CONCLUSION

PointWave is a robust payment gateway platform with solid foundations in place. The core functionality for merchant onboarding, virtual account management, and transaction processing is operational. However, significant opportunities exist for enhancement in areas like settlement automation, fraud detection, compliance tooling, and operational dashboards.

The platform is production-ready for basic operations but requires the planned upgrades to become a world-class payment gateway comparable to industry leaders like Paystack, Flutterwave, or Stripe.

---

**Document Version**: 1.0  
**Last Updated**: April 24, 2026  
**Prepared By**: Kiro AI Assistant  
**Purpose**: Complete project understanding for upgrade planning
