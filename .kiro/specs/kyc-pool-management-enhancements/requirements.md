# Requirements Document

## Introduction

This document specifies requirements for enhancing the KYC Pool Management system to address three critical operational issues: sparse global KYC pool data, non-functional refresh operations, and missing virtual account detection/regeneration. The enhancements will improve system reliability, automate KYC rotation, and ensure all customers have functional virtual accounts.

## Glossary

- **Global_KYC_Pool**: A shared database table containing BVN and NIN entries that all companies can use as fallback when their director KYC fails or reaches capacity limits
- **KYC**: Know Your Customer - identity verification data (BVN or NIN) required by PalmPay to create virtual accounts
- **BVN**: Bank Verification Number - a unique 11-digit identifier for bank customers in Nigeria
- **NIN**: National Identification Number - a unique identifier issued by NIMC in Nigeria
- **Virtual_Account**: A PalmPay-generated bank account number assigned to a customer for receiving payments
- **Company**: A business entity using the platform that has multiple customers requiring virtual accounts
- **Director_KYC**: The BVN or NIN of a company's director used to create virtual accounts for that company's customers
- **KYC_Refresh**: The process of assigning a fresh, unused KYC from the global pool to a company when their current KYC is exhausted
- **Exhausted_KYC**: A KYC entry that has reached its maximum usage limit (typically 130 virtual accounts)
- **Blacklisted_KYC**: A KYC entry temporarily disabled due to API failures, with automatic expiration after 24 hours
- **Admin_Dashboard**: The web interface at `/secure/kyc-pool` where administrators monitor and manage KYC pool health
- **VA_Limit**: The maximum number of virtual accounts that can be created using a single KYC (default: 130)
- **Circuit_Breaker**: A failure protection mechanism that temporarily blocks API calls after repeated failures
- **Missing_VA**: A customer record that exists in the database but has no associated virtual account entry

## Requirements

### Requirement 1: Global KYC Pool Population

**User Story:** As a system administrator, I want to populate the global KYC pool with sufficient BVN and NIN entries, so that companies have reliable fallback KYC options when their director KYC is exhausted.

#### Acceptance Criteria

1. THE Admin_Dashboard SHALL display the current count of total, available, exhausted, and blacklisted KYC entries in the Global_KYC_Pool
2. WHEN an administrator adds new BVN or NIN entries through the Admin_Dashboard, THE System SHALL validate each entry is between 10 and 20 characters
3. WHEN an administrator adds new entries, THE System SHALL skip duplicate entries and report the count of added versus skipped entries
4. WHEN an administrator adds new entries, THE System SHALL set the max_usage limit for each entry (default: 130, configurable between 10 and 500)
5. THE System SHALL mark newly added KYC entries as active with usage_count initialized to 0
6. THE Admin_Dashboard SHALL support bulk entry addition by accepting multiple BVN or NIN values separated by newlines
7. WHEN the Global_KYC_Pool contains fewer than 10 available entries, THE Admin_Dashboard SHALL display a warning alert
8. THE System SHALL store each KYC entry with fields: kyc_type (bvn/nin), kyc_number, is_active, usage_count, success_count, failure_count, max_usage, last_used_at, last_success_at, blacklisted_until, notes

### Requirement 2: KYC Refresh Functionality

**User Story:** As a system administrator, I want the refresh button to actually assign fresh KYC from the pool and update company status, so that companies can continue creating virtual accounts when their current KYC is exhausted.

#### Acceptance Criteria

1. WHEN an administrator clicks the "Refresh" button for a company, THE System SHALL query the Global_KYC_Pool for the least-used available NIN entry
2. WHEN an administrator clicks the "Refresh" button for a company, THE System SHALL query the Global_KYC_Pool for the least-used available BVN entry
3. WHEN fresh KYC entries are found, THE System SHALL update the company's director_nin and director_bvn fields with the selected entries
4. WHEN fresh KYC entries are assigned, THE System SHALL set the company's kyc_refreshed_at timestamp to the current time
5. WHEN fresh KYC entries are assigned, THE System SHALL clear the company's kyc_method_blacklist field
6. WHEN the refresh operation completes successfully, THE System SHALL recalculate the company's KYC health status
7. WHEN a company's usage is below 80% of VA_Limit after refresh, THE System SHALL set the company status to "healthy" (green)
8. WHEN a company's usage is between 80% and 100% of VA_Limit after refresh, THE System SHALL set the company status to "warning" (yellow)
9. WHEN a company's current director_nin is exhausted in the pool, THE System SHALL set the company status to "critical" (red)
10. IF no available KYC entries exist in the Global_KYC_Pool, THEN THE System SHALL return an error message "No available NIN or BVN in pool"
11. THE System SHALL log each refresh operation with company_id, assigned nin_kyc_id, and assigned bvn_kyc_id
12. WHEN selecting KYC from the pool, THE System SHALL prefer NIN over BVN entries (NIN has higher stability)
13. WHEN selecting KYC from the pool, THE System SHALL order candidates by lowest usage_count first for fair distribution

### Requirement 3: Missing Virtual Account Detection

**User Story:** As a system administrator, I want to automatically detect which customers are missing virtual accounts, so that I can identify and fix data integrity issues across all companies.

#### Acceptance Criteria

1. THE System SHALL scan all CompanyUser records to identify customers without associated VirtualAccount entries
2. WHEN scanning for missing VAs, THE System SHALL exclude VirtualAccount entries marked as deleted
3. THE Admin_Dashboard SHALL display a list of customers missing virtual accounts with columns: customer_name, company_name, customer_id, company_id
4. THE Admin_Dashboard SHALL display the total count of customers missing virtual accounts across all companies
5. WHEN no customers are missing virtual accounts, THE Admin_Dashboard SHALL display a success message "All customers have virtual accounts ✅"
6. WHEN customers are missing virtual accounts, THE Admin_Dashboard SHALL display a warning alert with the total count
7. THE System SHALL provide a filter option to view missing VAs for a specific company_id
8. THE System SHALL calculate missing VAs on-demand when the Admin_Dashboard loads
9. THE Admin_Dashboard SHALL display company-specific missing VA counts in the "Company KYC Health" table
10. WHEN a company exceeds its VA_Limit (e.g., 141/130), THE Admin_Dashboard SHALL display the actual count without capping at 100%

### Requirement 4: Virtual Account Regeneration

**User Story:** As a system administrator, I want to bulk regenerate missing virtual accounts for all companies, so that all customers have functional payment accounts without manual intervention.

#### Acceptance Criteria

1. WHEN an administrator clicks "Regenerate All", THE System SHALL create virtual accounts for all customers identified as missing VAs
2. WHEN an administrator clicks "Fix" for a specific company, THE System SHALL create virtual accounts only for that company's missing VAs
3. WHEN regeneration starts, THE System SHALL reset the PalmPay circuit_breaker cache to allow API calls
4. WHEN regeneration starts, THE System SHALL reset the palmpay_failure_count cache to zero
5. WHEN creating each virtual account, THE System SHALL use the VirtualAccountService with company_id, customer uuid, customer data (first_name, last_name, email, phone), bank_code "100033", and company_user_id
6. WHEN a virtual account is created successfully, THE System SHALL increment the created counter
7. WHEN a virtual account creation fails, THE System SHALL increment the failed counter and log the error message
8. WHEN a virtual account creation fails, THE System SHALL reset the circuit_breaker cache to prevent blocking subsequent attempts
9. THE System SHALL add a 2-second delay between each virtual account creation to avoid rate limiting
10. WHEN regeneration completes, THE System SHALL return a summary with created count, failed count, and up to 10 error messages
11. THE Admin_Dashboard SHALL display a loading indicator on the "Regenerate All" button during bulk operations
12. THE Admin_Dashboard SHALL display a loading indicator on individual "Fix" buttons during company-specific operations
13. WHEN regeneration completes successfully with no failures, THE System SHALL display a success notification
14. WHEN regeneration completes with some failures, THE System SHALL display a warning notification with failure count
15. WHEN regeneration completes, THE System SHALL refresh the Admin_Dashboard data to show updated counts

### Requirement 5: KYC Pool Health Monitoring

**User Story:** As a system administrator, I want real-time visibility into KYC pool health and company status, so that I can proactively manage capacity and prevent service disruptions.

#### Acceptance Criteria

1. THE Admin_Dashboard SHALL display six summary cards: Total Entries, Available, Exhausted, Blacklisted, NIns, BVNs
2. WHEN calculating "Available" count, THE System SHALL exclude entries where usage_count >= max_usage
3. WHEN calculating "Available" count, THE System SHALL exclude entries where blacklisted_until is in the future
4. WHEN calculating "Exhausted" count, THE System SHALL include entries where max_usage is set and usage_count >= max_usage
5. WHEN calculating "Blacklisted" count, THE System SHALL include entries where blacklisted_until is greater than current time
6. THE Admin_Dashboard SHALL display a "Company KYC Health" table with columns: Company, VAs, Usage, Status, Action
7. WHEN displaying VA count, THE System SHALL show "current_count/max_limit" format (e.g., "128/130")
8. WHEN displaying usage percentage, THE System SHALL show a colored progress bar: green for healthy (<80%), yellow for warning (80-100%), red for critical (>100% or exhausted KYC)
9. THE Admin_Dashboard SHALL display a "Global KYC Pool Entries" table with columns: Type, Number (masked), Used, Max, Success, Failures, Status, Last Used, Action
10. WHEN displaying KYC numbers, THE System SHALL mask all but the first 5 characters (e.g., "12345***")
11. THE Admin_Dashboard SHALL provide a "Disable" button for active KYC entries to manually deactivate them
12. WHEN an administrator clicks "Disable", THE System SHALL set is_active to false for that KYC entry
13. THE System SHALL automatically blacklist KYC entries when success_rate drops below 20% after at least 5 usage attempts
14. WHEN a KYC entry is auto-blacklisted, THE System SHALL set blacklisted_until to 24 hours from current time
15. THE System SHALL automatically remove blacklist status when blacklisted_until timestamp is in the past

### Requirement 6: Global KYC Fallback Integration

**User Story:** As a developer, I want the virtual account creation service to automatically use global KYC pool entries when company KYC fails, so that virtual account creation succeeds even when company-specific KYC is exhausted.

#### Acceptance Criteria

1. WHEN VirtualAccountService attempts to create a virtual account, THE System SHALL first try the company's director_nin and director_bvn
2. WHEN company KYC methods are exhausted or blacklisted, THE System SHALL query the Global_KYC_Pool for available entries
3. WHEN selecting from Global_KYC_Pool, THE System SHALL prefer NIN over BVN entries
4. WHEN selecting from Global_KYC_Pool, THE System SHALL order by highest success_rate first, then lowest usage_count
5. WHEN a global KYC entry is selected, THE System SHALL log the selection with kyc_id, kyc_type, kyc_number (masked), usage_count, success_rate
6. WHEN a virtual account is created using global KYC, THE System SHALL increment the KYC entry's usage_count
7. WHEN a virtual account is created successfully using global KYC, THE System SHALL increment the KYC entry's success_count and update last_success_at
8. WHEN a virtual account creation fails using global KYC, THE System SHALL increment the KYC entry's failure_count
9. WHEN a global KYC entry is used, THE System SHALL create a GlobalKycUsageLog record with global_kyc_id, company_id, virtual_account_id, kyc_number, kyc_type, success, error_message, request_data
10. WHEN a global KYC entry's failure_count causes success_rate to drop below 20% after 5+ uses, THE System SHALL automatically blacklist the entry for 24 hours
11. IF no available entries exist in Global_KYC_Pool, THEN THE System SHALL throw an exception "No KYC methods available: Company KYC exhausted and global pool empty"
12. THE System SHALL record the kyc_source in VirtualAccount as "global_nin" or "global_bvn" when global pool is used
13. WHEN global KYC is used successfully, THE System SHALL update the KYC entry's last_used_at timestamp

### Requirement 7: Circuit Breaker Management

**User Story:** As a system administrator, I want automatic circuit breaker reset during VA regeneration, so that temporary API failures don't block bulk regeneration operations.

#### Acceptance Criteria

1. WHEN regeneration starts, THE System SHALL delete the cache key "palmpay_circuit_breaker"
2. WHEN regeneration starts, THE System SHALL delete the cache key "palmpay_circuit_breaker_time"
3. WHEN regeneration starts, THE System SHALL delete the cache key "palmpay_failure_count"
4. WHEN a single VA creation fails during regeneration, THE System SHALL reset the circuit_breaker cache before attempting the next customer
5. THE System SHALL continue processing remaining customers even when individual VA creations fail
6. THE System SHALL not abort the entire regeneration batch due to circuit breaker activation
7. WHEN circuit breaker is reset, THE System SHALL log the reset action with timestamp and reason

### Requirement 8: Admin Dashboard Refresh and Real-time Updates

**User Story:** As a system administrator, I want the dashboard to automatically refresh after operations, so that I see accurate, up-to-date KYC pool and company health data.

#### Acceptance Criteria

1. WHEN the Admin_Dashboard loads, THE System SHALL fetch pool statistics, company health data, and missing VA list
2. WHEN an administrator adds new KYC entries, THE System SHALL refresh all dashboard data after successful addition
3. WHEN an administrator clicks "Refresh" for a company, THE System SHALL refresh all dashboard data after successful KYC assignment
4. WHEN an administrator clicks "Regenerate All" or "Fix", THE System SHALL refresh all dashboard data after completion
5. WHEN an administrator clicks "Disable" for a KYC entry, THE System SHALL refresh all dashboard data after deactivation
6. THE System SHALL display loading indicators during data fetch operations
7. WHEN data fetch fails, THE System SHALL display an error notification "Failed to load"
8. THE System SHALL fetch pool overview and entries data in parallel using Promise.all for faster loading
9. THE Admin_Dashboard SHALL display a loading spinner centered on the page while initial data loads
10. WHEN operations complete, THE System SHALL display success or error notifications using the snackbar component

