# Implementation Plan: KYC Pool Management Enhancements

## Overview

This implementation plan converts the KYC Pool Management Enhancements design into actionable coding tasks. The implementation enhances the existing GlobalKycService and VirtualAccountService to provide robust KYC rotation, automated VA regeneration, and comprehensive health monitoring through an improved admin dashboard.

The tasks are organized to build incrementally: backend API endpoints first, then frontend dashboard components, followed by integration and testing. Each task references specific requirements for traceability.

## Tasks

- [ ] 1. Create KycPoolController with core API endpoints
  - Create `app/Http/Controllers/Admin/KycPoolController.php`
  - Implement constructor with dependency injection for GlobalKycService
  - Add route registration in `routes/api.php` under `/api/admin/kyc-pool` prefix
  - Add admin middleware protection
  - _Requirements: 1.1, 2.1, 3.1, 4.1, 5.1_

- [ ] 2. Implement KYC pool population endpoints
  - [ ] 2.1 Implement `entries()` method to list all pool entries
    - Query GlobalKycPool with pagination
    - Mask KYC numbers (first 5 chars + "***")
    - Return JSON with data array
    - _Requirements: 5.9, 5.10_
  
  - [ ] 2.2 Implement `add()` method for bulk KYC addition
    - Validate request: entries[] array, optional max_usage (10-500, default 130)
    - Validate each entry length (10-20 characters)
    - Check for duplicates in database
    - Insert new entries with is_active=true, usage_count=0
    - Return summary with added count and skipped count
    - _Requirements: 1.2, 1.3, 1.4, 1.5, 1.6_
  
  - [ ]* 2.3 Write property test for KYC entry length validation
    - **Property 1: KYC Entry Length Validation**
    - **Validates: Requirements 1.2**
  
  - [ ]* 2.4 Write property test for duplicate entry deduplication
    - **Property 2: Duplicate Entry Deduplication**
    - **Validates: Requirements 1.3**
  
  - [ ]* 2.5 Write property test for max usage initialization
    - **Property 3: Max Usage Initialization**
    - **Validates: Requirements 1.4**

- [ ] 3. Implement KYC refresh functionality
  - [ ] 3.1 Implement `assignFresh()` method for company KYC refresh
    - Validate company_id exists
    - Query GlobalKycPool for least-used available NIN (usage_count ASC, last_used_at ASC)
    - Query GlobalKycPool for least-used available BVN
    - Return error if no available entries found
    - Update company.director_nin and company.director_bvn
    - Set company.kyc_refreshed_at to current timestamp
    - Clear company.kyc_method_blacklist (set to null)
    - Return success message with assigned KYC types
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.10, 2.12, 2.13_
  
  - [ ]* 3.2 Write property test for least-used KYC selection
    - **Property 6: Least-Used KYC Selection**
    - **Validates: Requirements 2.1, 2.2, 2.13**
  
  - [ ]* 3.3 Write property test for company KYC field update
    - **Property 7: Company KYC Field Update**
    - **Validates: Requirements 2.3**
  
  - [ ]* 3.4 Write property test for blacklist clearing on refresh
    - **Property 9: Blacklist Clearing on Refresh**
    - **Validates: Requirements 2.5**

- [ ] 4. Implement dashboard overview endpoint
  - [ ] 4.1 Implement `overview()` method for dashboard data aggregation
    - Calculate pool_stats: total, available, exhausted, blacklisted, nins, bvns
    - Calculate company_health: query all companies with VA counts and health status
    - Calculate missing_vas using getMissingVas() helper
    - Return JSON with all three data sections
    - _Requirements: 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 8.1_
  
  - [ ] 4.2 Implement `getMissingVas()` private helper method
    - Query CompanyUser records
    - Left join VirtualAccount (exclude soft-deleted)
    - Filter where virtual_account_id is null
    - Support optional company_id filter
    - Group by company_id and return array with customer details
    - _Requirements: 3.1, 3.2, 3.7_
  
  - [ ] 4.3 Implement health status calculation logic
    - Calculate usage percentage: (current_va_count / va_limit) * 100
    - Return "healthy" if usage < 80%
    - Return "warning" if 80% <= usage <= 100%
    - Return "critical" if director_nin exhausted in pool
    - _Requirements: 2.6, 2.7, 2.8, 2.9_
  
  - [ ]* 4.4 Write property test for health status calculation
    - **Property 10: Health Status Calculation**
    - **Validates: Requirements 2.6, 2.7, 2.8, 2.9**
  
  - [ ]* 4.5 Write property test for missing VA detection
    - **Property 13: Missing VA Detection**
    - **Validates: Requirements 3.1, 3.2**
  
  - [ ]* 4.6 Write property test for available count calculation
    - **Property 22: Available Count Calculation**
    - **Validates: Requirements 5.2, 5.3**

- [ ] 5. Checkpoint - Ensure backend API endpoints are functional
  - Test all endpoints manually using Postman or similar tool
  - Verify validation errors return HTTP 422
  - Verify success responses return correct data structures
  - Ask the user if questions arise

- [ ] 6. Implement VA regeneration endpoint
  - [ ] 6.1 Implement `regenerateMissing()` method for bulk VA regeneration
    - Validate optional company_id parameter
    - Reset circuit breaker cache keys: palmpay_circuit_breaker, palmpay_circuit_breaker_time, palmpay_failure_count
    - Get missing VAs using getMissingVas() helper
    - Initialize counters: created=0, failed=0, errors=[]
    - Loop through each missing customer
    - _Requirements: 4.1, 4.2, 4.3, 7.1, 7.2, 7.3_
  
  - [ ] 6.2 Implement VA creation loop with error handling
    - For each customer: call VirtualAccountService::createVirtualAccount()
    - Pass company_id, customer uuid, customer data, bank_code "100033", company_user_id
    - On success: increment created counter
    - On failure: increment failed counter, log error message (max 10 errors)
    - Reset circuit breaker cache after each failure
    - Add 2-second sleep between attempts
    - Continue processing remaining customers on individual failures
    - _Requirements: 4.5, 4.6, 4.7, 4.8, 4.9, 7.4, 7.5, 7.6_
  
  - [ ] 6.3 Return regeneration summary response
    - Return JSON with created count, failed count, errors array (max 10)
    - _Requirements: 4.10_
  
  - [ ]* 6.4 Write property test for batch processing resilience
    - **Property 38: Batch Processing Resilience**
    - **Validates: Requirements 7.5, 7.6**
  
  - [ ]* 6.5 Write unit tests for regeneration error handling
    - Test circuit breaker reset on failure
    - Test error message collection (max 10)
    - Test counter increments
    - _Requirements: 4.6, 4.7, 4.8_

- [ ] 7. Implement KYC entry deactivation endpoint
  - [ ] 7.1 Implement `deactivate()` method to disable pool entries
    - Validate pool entry ID exists
    - Set is_active to false
    - Return success message
    - _Requirements: 5.11, 5.12_
  
  - [ ]* 7.2 Write unit test for manual KYC deactivation
    - **Property 26: Manual KYC Deactivation**
    - **Validates: Requirements 5.12**

- [ ] 8. Create frontend KycPoolManagement component structure
  - Create `frontend/src/pages/admin/KycPoolManagement.js`
  - Set up component with Material-UI imports
  - Initialize state variables: loading, overview, entries, dialogs, counters
  - Create useCallback hook for `load()` function
  - Implement parallel data fetching using Promise.all for overview and entries
  - Add useEffect to call load() on component mount
  - _Requirements: 8.1, 8.8, 8.9_

- [ ] 9. Implement dashboard statistics cards
  - [ ] 9.1 Create pool statistics card section
    - Display 6 cards: Total Entries, Available, Exhausted, Blacklisted, NIns, BVNs
    - Use Grid layout with responsive sizing
    - Use Card component with icon, title, and count
    - Color-code cards: green for available, red for exhausted/blacklisted
    - _Requirements: 1.1, 5.1_
  
  - [ ] 9.2 Add warning alert for low pool capacity
    - Display warning when available count < 10
    - Use Alert component with warning severity
    - Show message: "Low KYC pool capacity - add more entries"
    - _Requirements: 1.7_

- [ ] 10. Implement company health table
  - [ ] 10.1 Create company KYC health table component
    - Display columns: Company, VAs, Usage, Status, Action
    - Show VA count in "current/limit" format (e.g., "128/130")
    - Display usage percentage with colored progress bar
    - Color-code status: green (<80%), yellow (80-100%), red (>100% or exhausted)
    - Add "Refresh" button in Action column
    - _Requirements: 5.6, 5.7, 5.8, 2.6, 2.7, 2.8, 2.9_
  
  - [ ] 10.2 Implement `handleAssignFresh()` function
    - Call POST `/api/admin/kyc-pool/company/{id}/assign-fresh`
    - Show loading indicator on button during request
    - Display success notification on completion
    - Call load() to refresh dashboard data
    - _Requirements: 2.1, 8.3, 8.13_

- [ ] 11. Implement missing VA detection section
  - [ ] 11.1 Create missing VAs table component
    - Display columns: Customer Name, Company, Customer ID, Company ID, Action
    - Show total count of missing VAs
    - Display success message when count is 0: "All customers have virtual accounts ✅"
    - Display warning alert when count > 0
    - Add "Fix" button for each company
    - Add "Regenerate All" button at top
    - _Requirements: 3.3, 3.4, 3.5, 3.6, 3.9_
  
  - [ ] 11.2 Implement `handleRegenerate()` function
    - Accept optional company_id parameter
    - Set regenerating state to track loading (company_id or 'all')
    - Call POST `/api/admin/kyc-pool/regenerate-missing` with optional company_id
    - Display loading indicator on appropriate button
    - Show success/warning notification based on response
    - Call load() to refresh dashboard data
    - _Requirements: 4.1, 4.2, 4.11, 4.12, 4.13, 4.14, 4.15_

- [ ] 12. Implement global KYC pool entries table
  - [ ] 12.1 Create pool entries table component
    - Display columns: Type, Number (masked), Used, Max, Success, Failures, Status, Last Used, Action
    - Mask KYC numbers: first 5 chars + "***"
    - Calculate and display success rate percentage
    - Show status badge: Active (green), Blacklisted (red), Exhausted (orange)
    - Add "Disable" button for active entries
    - _Requirements: 5.9, 5.10_
  
  - [ ] 12.2 Implement `handleDeactivate()` function
    - Call DELETE `/api/admin/kyc-pool/{id}`
    - Show confirmation dialog before deactivation
    - Display success notification on completion
    - Call load() to refresh dashboard data
    - _Requirements: 5.11, 5.12, 8.5_

- [ ] 13. Implement bulk KYC addition dialog
  - [ ] 13.1 Create add BVN/NIN dialog component
    - Add floating action button with "+" icon to open dialog
    - Create dialog with title "Add BVN/NIN to Pool"
    - Add radio buttons to select type: NIN or BVN
    - Add multiline TextField for bulk entry input
    - Add TextField for max_usage (default: 130, range: 10-500)
    - Add Cancel and Add buttons
    - _Requirements: 1.6_
  
  - [ ] 13.2 Implement `handleAdd()` function
    - Parse bulkText by splitting on newlines and filtering empty lines
    - Call POST `/api/admin/kyc-pool/add` with entries array and max_usage
    - Show loading indicator on Add button during request
    - Display success notification with added/skipped counts
    - Close dialog and call load() to refresh dashboard
    - _Requirements: 1.2, 1.3, 1.4, 8.2_

- [ ] 14. Add loading states and error handling
  - [ ] 14.1 Implement loading indicators
    - Show centered CircularProgress spinner during initial load
    - Show button loading states during operations (refresh, regenerate, add, disable)
    - Disable buttons during loading to prevent duplicate requests
    - _Requirements: 8.6, 8.9_
  
  - [ ] 14.2 Implement error handling and notifications
    - Wrap API calls in try-catch blocks
    - Display error notifications using Snackbar component
    - Show "Failed to load" message on data fetch errors
    - Display validation errors from backend (HTTP 422)
    - _Requirements: 8.7_

- [ ] 15. Add route and navigation for KycPoolManagement page
  - Add route in `routes/web.php` or frontend router: `/secure/kyc-pool`
  - Add navigation link in admin sidebar menu
  - Ensure admin authentication middleware is applied
  - _Requirements: 5.1_

- [ ] 16. Checkpoint - Ensure frontend dashboard is functional
  - Test all dashboard features in browser
  - Verify data loads correctly on page load
  - Verify all buttons trigger correct API calls
  - Verify loading indicators appear during operations
  - Verify success/error notifications display correctly
  - Ask the user if questions arise

- [ ] 17. Verify GlobalKycService integration (existing service)
  - [ ] 17.1 Review existing GlobalKycService methods
    - Verify selectOptimalGlobalKyc() implements NIN preference and usage ordering
    - Verify recordUsage() tracks success/failure and auto-blacklisting
    - Verify getUsageStats() returns accurate pool statistics
    - _Requirements: 6.1, 6.2, 6.3, 6.4, 6.5, 6.6, 6.7, 6.8, 6.9, 6.10, 6.11, 6.12, 6.13_
  
  - [ ]* 17.2 Write property test for NIN preference over BVN
    - **Property 12: NIN Preference Over BVN**
    - **Validates: Requirements 2.12, 6.3**
  
  - [ ]* 17.3 Write property test for global KYC usage tracking
    - **Property 33-36: Global KYC Usage Tracking**
    - **Validates: Requirements 6.6, 6.7, 6.8, 6.9**

- [ ] 18. Verify VirtualAccountService integration (existing service)
  - [ ] 18.1 Review existing VirtualAccountService methods
    - Verify selectOptimalKycMethodWithGlobalFallback() tries company KYC first
    - Verify callPalmPayWithKycFallback() implements automatic retry with KYC rotation
    - Verify global KYC usage is tracked via GlobalKycService
    - Verify kyc_source field is set correctly for global KYC
    - _Requirements: 6.1, 6.2, 6.12_
  
  - [ ]* 18.2 Write integration test for global KYC fallback flow
    - Test company KYC exhausted scenario
    - Verify global pool is queried
    - Verify VA created with correct kyc_source
    - Verify usage logged in GlobalKycUsageLog
    - _Requirements: 6.1, 6.2, 6.9, 6.12_

- [ ] 19. Integration testing and end-to-end validation
  - [ ]* 19.1 Write integration test for complete KYC refresh flow
    - Populate global pool with test entries
    - Create test company with exhausted director KYC
    - Call assignFresh() API endpoint
    - Verify company fields updated, blacklist cleared, health status recalculated
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6_
  
  - [ ]* 19.2 Write integration test for complete VA regeneration flow
    - Create test company with customers missing VAs
    - Populate global pool with test entries
    - Call regenerateMissing() API endpoint
    - Verify VAs created, global KYC usage tracked, circuit breaker reset
    - _Requirements: 4.1, 4.2, 4.5, 4.6, 4.8, 7.4_
  
  - [ ]* 19.3 Write integration test for auto-blacklisting flow
    - Create global KYC entry
    - Simulate 5 VA creation attempts with 4 failures (20% success rate)
    - Verify entry auto-blacklisted with 24-hour expiration
    - Verify entry excluded from available() scope
    - _Requirements: 5.13, 5.14, 5.15, 6.10_

- [ ] 20. Final checkpoint and documentation
  - Run all tests to ensure no regressions
  - Verify all requirements are covered by implementation
  - Test dashboard with real data in staging environment
  - Document any configuration changes needed for deployment
  - Ensure all tests pass, ask the user if questions arise

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation at key milestones
- Property tests validate universal correctness properties
- Unit tests validate specific examples and edge cases
- Integration tests verify end-to-end flows across multiple components
- The implementation builds on existing GlobalKycService and VirtualAccountService without requiring changes to those services
- Circuit breaker management is critical for bulk VA regeneration to prevent API blocking
- All KYC numbers must be masked in the UI for security (first 5 chars + "***")
