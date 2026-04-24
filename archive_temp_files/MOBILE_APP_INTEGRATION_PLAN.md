# Kobopoint Mobile App Integration Plan

## Overview
Adapting the bills payment template to match Kobopoint's company and admin dashboard functionality.

## Phase 1: API Configuration ✅
**Status**: Ready
- Base URL configured in `lib/config/environment.dart`
- API endpoints in `lib/infrastructure/api/api_endpoints.dart`
- API client in `lib/infrastructure/api/api_client.dart`

### Required Changes:
1. Update API endpoints to match backend routes
2. Add company-specific endpoints
3. Add admin-specific endpoints

## Phase 2: Company Dashboard Implementation
**Location**: `lib/modules/dashboard/screens/dashboard_screen.dart`

### Current Features (Bills Payment):
- Balance display with visibility toggle
- Virtual account info
- Recent transactions (2 items)
- Single service (Buy Vouchers)
- Promo carousel

### Required Features (Company Dashboard):
1. **Welcome Section** ✅ (Already exists)
   - "Welcome Back, Kobopoint! 😎"
   - "Here's a Quick Overview of Your Account"

2. **Balance Overview** ✅ (Modify existing)
   - Total Balance (from `system_wallet_balance`)
   - Show/hide toggle ✅
   - Virtual account details ✅

3. **Time Filter** (NEW)
   - Today
   - Yesterday
   - Last 7 days
   - Last 30 days
   - All time
   - Custom date range

4. **Dashboard Stats** (NEW)
   - Total Revenue
   - Total Transactions
   - Pending Transactions
   - Pending Settlement

5. **Analytics Charts** (NEW)
   - Revenue Analytics (line/bar chart)
   - Transaction Status (pie chart)

6. **Quick Actions** (Modify existing)
   - Fund Wallet ✅
   - Withdraw
   - Transactions ✅

7. **Recent Transactions** ✅ (Enhance existing)
   - Show 5-10 recent transactions
   - Pull from `/system/all/ra-history/records/{id}/secure`

### API Endpoints Needed:
- `/user/dashboard-stats?filter={filter}` - Dashboard statistics
- `/system/all/ra-history/records/{id}/secure` - Recent transactions
- `/user/settlements` - Settlement history
- `/user/withdrawals` - Withdrawal history

## Phase 3: Navigation Bar
**Location**: `lib/widgets/scaffold_with_navbar.dart`

### Current Navigation:
- Home
- Services
- More (bottom sheet)

### Required Navigation (Company):
- **Home** - Dashboard
- **Collections** - Virtual accounts/payments received
- **More** - Settings, Calculator, Support, etc.

### More Menu Items:
- Calculator
- Settings
- Transactions
- Settlements
- Withdrawals
- Webhook Events
- Developer API
- Support

## Phase 4: FAB (Floating Action Button)
**Purpose**: Quick access to support

### Implementation:
- Add FAB to scaffold
- Icon: Headset/Support
- Action: Open support chat/contact

## Phase 5: Admin Dashboard
**Location**: Create `lib/modules/admin/screens/admin_dashboard_screen.dart`

### Admin Features:
1. **System Overview**
   - System Balance
   - Total Revenue
   - Total Businesses
   - Total Customers
   - Pending Settlement
   - Profit Margin
   - System Performance

2. **Admin Actions**
   - System Settings
   - Welcome Message
   - Notification Sender
   - Fee Calculator
   - All Companies
   - All Customers
   - Pending KYC
   - Settlement Manual Track
   - Company Fees
   - KYC Pool
   - Transaction History
   - Data Clean Up
   - Settlement Monitor

### API Endpoints Needed:
- `/admin/dashboard-stats` - Admin statistics
- `/admin/companies` - All companies
- `/admin/customers` - All customers
- `/admin/kyc/pending` - Pending KYC
- `/admin/settlements` - Settlement tracking
- `/admin/system-settings` - System configuration

## Phase 6: Collections Screen (NEW)
**Location**: Create `lib/modules/collections/screens/collections_screen.dart`

### Features:
- Virtual account list
- Payment notifications
- Collection history
- Filter by date/status
- Export functionality

## Phase 7: Withdrawal Feature
**Location**: Create `lib/modules/withdrawals/`

### Features:
- Initiate withdrawal
- Beneficiary management
- Withdrawal history
- Status tracking

## Phase 8: Settlement Feature
**Location**: Create `lib/modules/settlements/`

### Features:
- Settlement history
- Pending settlements
- Settlement details
- Filter by date

## Implementation Priority

### HIGH PRIORITY (Week 1):
1. ✅ API endpoint configuration
2. Company dashboard stats integration
3. Time filter implementation
4. Navigation bar update
5. Recent transactions enhancement

### MEDIUM PRIORITY (Week 2):
6. Collections screen
7. Withdrawal feature
8. Settlement feature
9. FAB support button
10. More menu items

### LOW PRIORITY (Week 3):
11. Admin dashboard
12. Admin features
13. Analytics charts
14. Advanced filters

## API Endpoints Summary

### Company Endpoints:
```
GET /user/dashboard-stats?filter={filter}
GET /system/all/ra-history/records/{id}/secure
GET /user/settlements
GET /user/withdrawals
POST /user/withdrawal/initiate
GET /user/beneficiaries
POST /user/beneficiary/add
GET /user/virtual-accounts
```

### Admin Endpoints:
```
GET /admin/dashboard-stats
GET /admin/companies
GET /admin/customers
GET /admin/kyc/pending
GET /admin/settlements
GET /admin/transactions
POST /admin/system-settings
POST /admin/welcome-message
GET /admin/kyc-pool
```

## File Structure

```
lib/
├── modules/
│   ├── dashboard/
│   │   └── screens/
│   │       ├── dashboard_screen.dart (MODIFY)
│   │       └── company_dashboard_screen.dart (NEW)
│   ├── admin/
│   │   └── screens/
│   │       └── admin_dashboard_screen.dart (NEW)
│   ├── collections/
│   │   └── screens/
│   │       └── collections_screen.dart (NEW)
│   ├── withdrawals/
│   │   └── screens/
│   │       ├── withdrawal_screen.dart (NEW)
│   │       └── withdrawal_history_screen.dart (NEW)
│   └── settlements/
│       └── screens/
│           ├── settlement_screen.dart (NEW)
│           └── settlement_history_screen.dart (NEW)
├── widgets/
│   ├── scaffold_with_navbar.dart (MODIFY)
│   ├── time_filter_widget.dart (NEW)
│   ├── stats_card_widget.dart (NEW)
│   └── chart_widgets.dart (NEW)
└── infrastructure/
    └── api/
        └── api_endpoints.dart (MODIFY)
```

## Next Steps

1. Review and approve this plan
2. Start with HIGH PRIORITY items
3. Test each feature as implemented
4. Iterate based on feedback

## Notes
- Keep existing bills payment features intact
- Add company/admin features alongside
- Use role-based routing to show appropriate dashboard
- Maintain existing authentication flow
- Preserve offline capabilities where applicable
