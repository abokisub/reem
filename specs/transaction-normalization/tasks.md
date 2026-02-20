# Implementation Plan: Transaction Normalization

## Overview

This implementation plan transforms the transaction management system from an inconsistent, nullable-field architecture to a bank-grade normalized schema with strict constraints, canonical status management, and proper separation between customer-facing and internal accounting transactions. The implementation follows a three-phase migration strategy to ensure zero data loss and minimal downtime.

## Implementation Approach

- **Three-phase migration**: Schema preparation (non-breaking) → Data backfill → Constraint enforcement
- **Validation-first**: Application-level validation prevents invalid data before database insertion
- **Dual testing**: Property-based tests for universal correctness + unit tests for specific scenarios
- **View separation**: Distinct API endpoints for customer-facing (RA Dashboard) vs. internal (Admin Dashboard) transactions
- **Automated reconciliation**: Webhook-driven status synchronization with payment providers

## Tasks

- [x] 1. Database schema preparation and migration infrastructure
  - [x] 1.1 Create Phase 1 migration: Add nullable columns and indexes
    - Add session_id, transaction_ref, transaction_type, settlement_status, net_amount as nullable
    - Create indexes: idx_session_id, idx_transaction_ref, idx_type_status
    - Add provider_reference column if not exists
    - _Requirements: 1.1, 1.2, 1.3, 5.1, 6.1, 10.3_
  
  - [ ]* 1.2 Write property test for migration Phase 1
    - **Property: Schema changes are non-breaking**
    - **Validates: Requirements 1.1**
    - Test that existing queries continue to work after Phase 1 migration
  
  - [x] 1.3 Create Phase 2 migration: Backfill historical data
    - Generate session_id for all existing transactions (CONCAT('sess_', UUID()))
    - Generate transaction_ref for all existing transactions (CONCAT('TXN', UPPER(SUBSTRING(MD5(CONCAT(id, created_at)), 1, 12))))
    - Map old transaction types to new transaction_type enum
    - Calculate net_amount = amount - fee for all transactions
    - Set settlement_status based on status and transaction_type
    - Normalize status values to 5-state model
    - _Requirements: 1.3, 1.4, 1.6, 1.7, 2.1, 2.2, 7.3_

  - [ ]* 1.4 Write unit tests for data backfill logic
    - Test session_id generation format
    - Test transaction_ref uniqueness
    - Test type mapping accuracy
    - Test net_amount calculation
    - Test settlement_status assignment rules
    - _Requirements: 1.3, 1.4, 1.6, 1.7, 2.2, 7.3_
  
  - [x] 1.5 Create Phase 3 migration: Enforce NOT NULL constraints
    - Change session_id, transaction_ref, transaction_type, settlement_status, net_amount to NOT NULL
    - Add UNIQUE constraint on transaction_ref
    - Add CHECK constraint for amount > 0
    - _Requirements: 1.2, 1.5, 1.8_
  
  - [x] 1.6 Create TransactionStatusLog table migration
    - Create transaction_status_logs table with fields: transaction_id, old_status, new_status, source, metadata, changed_at
    - Add foreign key to transactions table with CASCADE delete
    - Add indexes on transaction_id and changed_at
    - _Requirements: 3.8, 10.2_
  
  - [x]* 1.7 Write rollback migrations for all phases
    - Phase 3 rollback: Remove constraints
    - Phase 2 rollback: Clear backfilled data
    - Phase 1 rollback: Drop columns and indexes
    - Test rollback procedures on staging data

- [ ] 2. Checkpoint - Verify migrations on staging environment
  - Run all migrations on staging with production data copy
  - Verify data integrity after each phase
  - Measure execution time for capacity planning
  - Test rollback procedures
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 3. Validation layer implementation
  - [x] 3.1 Create TransactionValidator class
    - Implement validate() method checking required fields, enum values, amount constraints, foreign keys
    - Implement generateDefaults() method for session_id, transaction_ref, fee, net_amount, settlement_status
    - Implement generateTransactionRef() private method
    - Implement determineSettlementStatus() private method
    - _Requirements: 1.2, 1.3, 1.4, 1.5, 1.6, 1.7, 1.8, 7.3, 7.5, 11.2, 11.5, 11.6, 11.7, 11.8, 11.9, 11.10_
  
  - [ ]* 3.2 Write property test for TransactionValidator
    - **Property 1: Transaction Validation Completeness**
    - **Validates: Requirements 1.2, 1.5, 1.8, 11.2, 11.5, 11.6, 11.7, 11.8, 11.9, 11.10**
    - Test rejection of missing required fields
    - Test rejection of invalid enum values
    - Test rejection of invalid amounts (amount <= 0, fee > amount)
    - Test rejection of non-existent foreign keys
  
  - [ ]* 3.3 Write property test for unique identifier generation
    - **Property 2: Unique Identifier Generation**
    - **Validates: Requirements 1.3**
    - Generate 1000 transaction_ref values and verify all are unique
  
  - [ ]* 3.4 Write property test for automatic field generation
    - **Property 3: Automatic Field Generation**
    - **Validates: Requirements 1.4, 1.6, 5.8**
    - Test session_id auto-generation when not provided
    - Test fee defaults to 0.00 when not specified
  
  - [ ]* 3.5 Write property test for net amount calculation
    - **Property 4: Net Amount Calculation Invariant**
    - **Validates: Requirements 1.7**
    - Test net_amount = amount - fee for all valid transactions
  
  - [ ]* 3.6 Write unit tests for TransactionValidator edge cases
    - Test fee = 0 (net_amount = amount)
    - Test fee = amount (net_amount = 0)
    - Test settlement_status for each transaction_type
    - Test error message formatting
    - _Requirements: 1.6, 1.7, 7.3, 7.5_

- [ ] 4. Transaction model updates
  - [x] 4.1 Update Transaction model with new fillable fields
    - Add all new fields to $fillable array
    - Update $casts for decimal and datetime fields
    - Add metadata JSON casting
    - _Requirements: 1.1, 1.2, 13.3, 13.4_
  
  - [x] 4.2 Implement Transaction model boot method
    - Auto-generate session_id if not provided (sess_ + UUID)
    - Auto-generate transaction_ref if not provided
    - Calculate net_amount automatically
    - Set default settlement_status based on type and status
    - _Requirements: 1.3, 1.4, 1.6, 1.7, 7.3, 7.5_
  
  - [x] 4.3 Add Transaction model scopes
    - customerFacing() scope: filter to 4 customer-facing types
    - internal() scope: filter to 3 internal types
    - settled() scope: filter to settlement_status = 'settled'
    - unsettled() scope: filter to settlement_status = 'unsettled'
    - _Requirements: 4.1, 4.3, 7.1, 7.2_
  
  - [x] 4.4 Add Transaction model relationships
    - company() belongsTo relationship
    - customer() belongsTo relationship
    - virtualAccount() belongsTo relationship
    - _Requirements: 1.8, 11.8_
  
  - [ ]* 4.5 Write unit tests for Transaction model
    - Test auto-generation of session_id and transaction_ref
    - Test net_amount calculation on create
    - Test settlement_status defaults for each transaction_type
    - Test all scopes return correct filtered results
    - _Requirements: 1.3, 1.4, 1.6, 1.7, 4.1, 7.3_

- [ ] 5. Create TransactionStatusLog model
  - [x] 5.1 Create TransactionStatusLog model class
    - Define fillable fields: transaction_id, old_status, new_status, source, metadata, changed_at
    - Add metadata JSON casting
    - Add changed_at datetime casting
    - Add transaction() belongsTo relationship
    - _Requirements: 3.8, 10.2_
  
  - [ ]* 5.2 Write unit tests for TransactionStatusLog
    - Test log creation on status change
    - Test metadata storage and retrieval
    - Test relationship to Transaction model
    - _Requirements: 3.8, 10.2_

- [ ] 6. Checkpoint - Ensure model tests pass
  - Run all model tests
  - Verify auto-generation works correctly
  - Verify scopes filter correctly
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 7. Status reconciliation service implementation
  - [x] 7.1 Create TransactionReconciliationService class
    - Implement reconcileFromWebhook() method to process provider webhooks
    - Implement mapProviderStatus() method to convert provider status to system status
    - Implement updateTransactionStatus() method with atomic DB transaction
    - Implement determineSettlementStatus() method for settlement_status calculation
    - Implement runScheduledReconciliation() method for stale transaction queries
    - Implement queryProviderStatus() method to call provider API
    - _Requirements: 3.5, 3.6, 3.8, 10.1, 10.2, 10.3, 10.4_
  
  - [ ]* 7.2 Write property test for provider reconciliation
    - **Property 5: Provider Reconciliation Status Update**
    - **Validates: Requirements 3.5, 10.1**
    - Test that successful webhook updates both status and settlement_status atomically
  
  - [ ]* 7.3 Write property test for status consistency
    - **Property 6: Status Consistency Across Views**
    - **Validates: Requirements 3.6**
    - Query same transaction through admin and RA APIs, verify status matches
  
  - [ ]* 7.4 Write property test for conflict detection
    - **Property 7: Conflict Detection and Canonical Source**
    - **Validates: Requirements 3.8, 10.2**
    - Test that status conflicts are logged and transactions.status is used as authoritative
  
  - [ ]* 7.5 Write unit tests for reconciliation service
    - Test webhook processing for each provider status
    - Test status transition logging
    - Test scheduled reconciliation for stale transactions
    - Test error handling for unknown provider_reference
    - Test retry logic for provider API failures
    - _Requirements: 3.5, 3.8, 10.1, 10.2, 10.3, 10.4, 15.1, 15.2_
  
  - [x] 7.6 Create scheduled reconciliation command
    - Create Artisan command ProcessTransactionReconciliation
    - Schedule to run every 5 minutes via cron
    - Add logging for reconciliation results
    - _Requirements: 10.3, 10.4_

- [ ] 8. API endpoints for transaction queries
  - [ ] 8.1 Update TransactionController for company queries
    - Implement index() method with filters: transaction_type, status, session_id, transaction_ref, provider_reference, date_from, date_to
    - Implement show() method for single transaction by transaction_ref
    - Apply company_id filter from authenticated user
    - Order by created_at DESC by default
    - Paginate results (50 per page default)
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 4.7, 5.1, 5.2, 5.3, 5.4, 5.5, 5.6, 5.7, 6.2, 6.3_
  
  - [-] 8.2 Create RADashboardController for customer-facing transactions
    - Implement transactions() method filtering to 4 customer-facing types only
    - Apply filters: session_id, transaction_ref, customer_id, date_from, date_to
    - Apply company_id filter from authenticated user
    - Order by created_at DESC
    - Paginate results (50 per page default)
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 5.6_
  
  - [ ]* 8.3 Write property test for RA Dashboard filtering
    - **Property 8: RA Dashboard Transaction Type Filtering**
    - **Validates: Requirements 4.1, 4.3**
    - Test that all returned transactions have customer-facing types only
    - Test that all returned transactions match authenticated company_id
  
  - [ ] 8.4 Create AdminTransactionController for all transactions
    - Implement index() method with filters: company_id, transaction_type, status, session_id, transaction_ref, provider_reference
    - Include all 7 transaction types without filtering
    - Eager load company and customer relationships
    - Order by created_at DESC
    - Paginate results (100 per page default)
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 4.8, 6.2, 6.3_
  
  - [ ] 8.5 Implement export endpoint for admin
    - Create exportTransactions() method in AdminTransactionController
    - Generate CSV with all transaction fields
    - Apply same filters as index() method
    - Stream large result sets to avoid memory issues
    - _Requirements: 6.4_
  
  - [ ]* 8.6 Write unit tests for API endpoints
    - Test RA Dashboard returns only customer-facing types
    - Test Admin Dashboard returns all 7 types
    - Test session_id filter returns all matching transactions
    - Test pagination works correctly
    - Test export generates valid CSV
    - Test authentication and authorization
    - _Requirements: 4.1, 4.3, 4.7, 5.6, 6.2, 6.3, 6.4_

- [ ] 9. API resource formatters
  - [ ] 9.1 Create TransactionResource for API responses
    - Format all required fields: transaction_ref, session_id, transaction_type, status, settlement_status, amount, fee, net_amount, currency, created_at, updated_at
    - Format amounts as decimal strings with 2 decimal places
    - Format timestamps in ISO 8601 format
    - Include recipient details conditionally (when present)
    - Return empty string for null provider_reference (not null)
    - _Requirements: 4.4, 13.1, 13.2, 13.3, 13.4, 13.5, 13.6_
  
  - [ ] 9.2 Create AdminTransactionResource for admin API
    - Extend TransactionResource with company and customer relationships
    - Include all internal fields
    - _Requirements: 13.1, 13.2_
  
  - [ ]* 9.3 Write property test for API response completeness
    - **Property 9: API Response Completeness**
    - **Validates: Requirements 4.4, 13.1**
    - Test that all required fields are present and non-null in responses
  
  - [ ]* 9.4 Write property test for API response formatting
    - **Property 13: API Response Formatting Standards**
    - **Validates: Requirements 13.2, 13.3, 13.4, 13.5, 13.6**
    - Test decimal formatting (2 decimal places)
    - Test ISO 8601 timestamp format
    - Test enum values returned as strings
  
  - [ ]* 9.5 Write property test for serialization integrity
    - **Property 14: Serialization Round-Trip Integrity**
    - **Validates: Requirements 13.10**
    - Test JSON serialize → deserialize → serialize produces equivalent object
  
  - [ ]* 9.6 Write unit tests for resource formatters
    - Test amount formatting with various decimal values
    - Test timestamp formatting
    - Test conditional recipient details
    - Test null provider_reference returns empty string
    - _Requirements: 13.2, 13.3, 13.4, 13.5, 13.6_

- [ ] 10. Checkpoint - Ensure API tests pass
  - Run all API endpoint tests
  - Verify filtering works correctly
  - Verify response formatting is correct
  - Test export functionality
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 11. Frontend: RA Dashboard transaction list component
  - [ ] 11.1 Create RATransactions.js component
    - Implement transaction list table with columns: transaction_ref, session_id, type, amount, fee, net_amount, status, settlement, date
    - Implement filters: transaction_type (4 customer-facing types), status, transaction_ref, session_id
    - Implement pagination with page state management
    - Implement session_id clickable links to filter by session
    - Display transaction type labels (not raw enum values)
    - Display status with color-coded chips
    - Display settlement_status as "Not Applicable" when value is 'not_applicable'
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 5.6, 7.4, 13.7_
  
  - [ ]* 11.2 Write unit tests for RATransactions component
    - Test component renders transaction list
    - Test filters update query parameters
    - Test pagination changes page
    - Test session_id click filters transactions
    - Test no N/A values displayed
    - _Requirements: 4.1, 4.7, 5.6, 7.4_
  
  - [ ] 11.3 Create TransactionDetailModal.js component
    - Display all transaction fields in organized grid layout
    - Show recipient details when present
    - Format amounts with currency symbol
    - Format timestamps in local timezone
    - Display settlement_status as "Not Applicable" for internal types
    - _Requirements: 4.4, 7.4, 13.7_
  
  - [ ]* 11.4 Write unit tests for TransactionDetailModal
    - Test modal displays all fields correctly
    - Test conditional recipient details display
    - Test amount and timestamp formatting
    - Test no N/A values displayed
    - _Requirements: 4.4, 7.4_

- [ ] 12. Frontend: Admin Dashboard transaction list component
  - [ ] 12.1 Create AdminTransactions.js component
    - Implement transaction list table with all fields including company_id
    - Implement filters: company_id, transaction_type (all 7 types), status, transaction_ref, provider_reference
    - Implement export button with CSV download
    - Display all 7 transaction types with distinct visual indicators
    - Show provider_reference column
    - Paginate with 100 items per page
    - _Requirements: 3.1, 3.2, 3.3, 3.4, 4.8, 6.2, 6.3, 6.4_
  
  - [ ]* 12.2 Write unit tests for AdminTransactions component
    - Test component renders all transaction types
    - Test filters work correctly
    - Test export button triggers CSV download
    - Test pagination with 100 items per page
    - _Requirements: 4.8, 6.2, 6.3, 6.4_
  
  - [ ] 12.3 Update admin navigation to include transaction management link
    - Add "Transactions" menu item in admin sidebar
    - Link to /admin/transactions route
    - _Requirements: 4.8_

- [ ] 13. Error handling and logging implementation
  - [ ] 13.1 Implement validation error responses
    - Return 400 Bad Request with structured error object
    - Include field-specific error messages
    - Include error code and timestamp
    - _Requirements: 11.2, 11.3, 11.4, 15.1_
  
  - [ ] 13.2 Implement database constraint error handling
    - Catch duplicate transaction_ref errors, return 409 Conflict
    - Catch foreign key violations, return 400 Bad Request
    - Log all constraint violations with transaction details
    - _Requirements: 11.5, 15.2_
  
  - [ ] 13.3 Implement provider integration error handling
    - Handle provider timeouts with 504 Gateway Timeout
    - Create transaction with status='processing' on timeout
    - Implement retry logic with exponential backoff (3 attempts)
    - Log all provider errors with transaction_ref and session_id
    - _Requirements: 15.1, 15.2_
  
  - [ ] 13.4 Implement reconciliation error handling
    - Log status conflicts with both system and provider status
    - Create alerts for conflicts exceeding 1% threshold
    - Implement webhook retry logic (5 attempts with backoff)
    - Store failed webhook payloads for manual replay
    - _Requirements: 3.8, 10.2, 15.1, 15.2, 15.3_
  
  - [ ]* 13.5 Write property test for error logging
    - **Property 15: Comprehensive Error Logging**
    - **Validates: Requirements 15.1, 15.2, 15.3**
    - Test that all error types create log entries with required fields
  
  - [ ]* 13.6 Write unit tests for error handling
    - Test validation error response format
    - Test duplicate transaction_ref error handling
    - Test provider timeout handling
    - Test webhook retry logic
    - Test status conflict logging
    - _Requirements: 11.2, 11.3, 11.4, 11.5, 15.1, 15.2, 15.3_

- [ ] 14. Monitoring and alerting setup
  - [ ] 14.1 Create monitoring dashboard for transaction metrics
    - Track transaction creation success rate
    - Track status reconciliation accuracy
    - Track API response times (p50, p95, p99)
    - Track error rates by type
    - _Requirements: 10.4, 15.3_
  
  - [ ] 14.2 Configure critical alerts
    - Alert on database constraint violations > 10/minute
    - Alert on provider error rate > 5%
    - Alert on status conflicts > 1%
    - Alert on transactions stuck in 'processing' > 1 hour
    - _Requirements: 15.3_
  
  - [ ] 14.3 Configure warning alerts (hourly digest)
    - Validation error rate > 10%
    - Missing provider_reference for transactions > 30 minutes old
    - _Requirements: 15.3_

- [ ] 15. Checkpoint - Ensure error handling and monitoring work
  - Test validation error responses
  - Test constraint violation handling
  - Test provider timeout handling
  - Verify alerts trigger correctly
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 16. Integration and wiring
  - [ ] 16.1 Update existing transaction creation code to use TransactionValidator
    - Update VirtualAccountService to validate before creating va_deposit transactions
    - Update TransferService to validate before creating api_transfer transactions
    - Update withdrawal logic to validate before creating company_withdrawal transactions
    - Update KYC service to create kyc_charge transactions
    - Update refund logic to create refund transactions
    - _Requirements: 1.2, 1.5, 2.1, 2.2, 2.3, 2.4, 2.5_
  
  - [ ] 16.2 Wire webhook handler to TransactionReconciliationService
    - Update PalmPay webhook handler to call reconcileFromWebhook()
    - Extract provider_reference and status from webhook payload
    - Log all webhook processing attempts
    - _Requirements: 3.5, 10.1, 10.2_
  
  - [ ] 16.3 Register API routes
    - Register /api/v1/ra-dashboard/transactions route to RADashboardController
    - Register /api/v1/admin/transactions routes to AdminTransactionController
    - Register /api/v1/transactions routes to TransactionController
    - Apply authentication middleware
    - Apply rate limiting
    - _Requirements: 3.1, 4.1, 4.8_
  
  - [ ] 16.4 Register scheduled commands
    - Register ProcessTransactionReconciliation command in Kernel.php
    - Schedule to run every 5 minutes
    - Add command to cron documentation
    - _Requirements: 10.3, 10.4_
  
  - [ ] 16.5 Update frontend routing
    - Add /dashboard/transactions route for RATransactions component
    - Add /admin/transactions route for AdminTransactions component
    - Update navigation menus
    - _Requirements: 4.1, 4.8_
  
  - [ ]* 16.6 Write integration tests for complete transaction flows
    - Test VA deposit: webhook → status update → RA Dashboard display
    - Test API transfer: creation → validation → provider call → reconciliation
    - Test KYC charge: creation → settlement_status = not_applicable
    - Test session_id grouping across multiple transactions
    - Test status conflict resolution flow
    - _Requirements: 2.1, 2.2, 2.3, 3.5, 3.8, 5.6, 7.3, 10.1, 10.2_

- [ ] 17. Property-based test suite completion
  - [ ]* 17.1 Write property test for default sort order
    - **Property 10: Default Sort Order**
    - **Validates: Requirements 4.7**
    - Test that queries without sort parameters return transactions ordered by created_at DESC
  
  - [ ]* 17.2 Write property test for search filter accuracy
    - **Property 11: Search Filter Accuracy**
    - **Validates: Requirements 5.6**
    - Test that session_id search returns only exact matches
  
  - [ ]* 17.3 Write property test for settlement status business rules
    - **Property 12: Settlement Status Business Rules**
    - **Validates: Requirements 7.3, 7.5**
    - Test that internal types always have settlement_status = 'not_applicable'
    - Test that pending/processing transactions have settlement_status = 'unsettled'
  
  - [ ]* 17.4 Run all property tests with 100+ iterations
    - Execute all 15 property tests
    - Verify each runs minimum 100 iterations
    - Document any failures with counterexamples
    - _Requirements: All requirements_

- [ ] 18. Deployment preparation
  - [ ] 18.1 Create deployment runbook
    - Document pre-deployment checklist (database backup, staging test, performance test)
    - Document Phase 1 deployment steps (zero downtime)
    - Document Phase 2 deployment steps (low traffic window)
    - Document Phase 3 deployment steps (maintenance window)
    - Document rollback procedures for each phase
    - Document post-deployment monitoring checklist
    - _Requirements: All requirements_
  
  - [ ] 18.2 Create database backup and restore scripts
    - Script to backup transactions table
    - Script to backup related tables
    - Script to verify backup integrity
    - Script to restore from backup
    - _Requirements: All requirements_
  
  - [ ] 18.3 Prepare staging environment
    - Copy production data to staging
    - Run all migrations on staging
    - Verify data integrity after migrations
    - Measure migration execution time
    - Test rollback procedures
    - _Requirements: All requirements_
  
  - [ ] 18.4 Performance testing on staging
    - Load test with 1M+ transactions
    - Verify query performance with new indexes
    - Test API response times under load (target: p95 < 200ms)
    - Monitor database CPU and memory usage
    - _Requirements: 10.3, 12.1, 12.2, 12.3_
  
  - [ ] 18.5 Create monitoring dashboards
    - Dashboard for transaction creation metrics
    - Dashboard for status reconciliation metrics
    - Dashboard for API performance metrics
    - Dashboard for error rates
    - _Requirements: 10.4, 15.3_

- [ ] 19. Checkpoint - Pre-deployment verification
  - All tests pass (unit + property + integration)
  - Staging environment tested successfully
  - Performance benchmarks meet targets
  - Rollback procedures tested
  - Monitoring dashboards configured
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 20. Production deployment execution
  - [ ] 20.1 Execute Phase 1 deployment (zero downtime)
    - Run Phase 1 migration (add nullable columns and indexes)
    - Deploy application code that populates new fields
    - Monitor for 24 hours
    - Verify no errors in logs
    - _Requirements: 1.1, 1.2, 1.3_
  
  - [ ] 20.2 Execute Phase 2 deployment (low traffic window)
    - Run Phase 2 migration (backfill historical data)
    - Verify data integrity after backfill
    - Monitor for errors
    - Estimated time: 2-4 hours for 1M transactions
    - _Requirements: 1.3, 1.4, 1.6, 1.7, 2.1, 2.2, 7.3_
  
  - [ ] 20.3 Execute Phase 3 deployment (maintenance window)
    - Announce maintenance window to users
    - Run Phase 3 migration (enforce NOT NULL constraints)
    - Deploy updated application code with validation enforcement
    - Verify constraints are active
    - Estimated downtime: 15-30 minutes
    - _Requirements: 1.2, 1.5, 1.8_
  
  - [ ] 20.4 Deploy frontend updates
    - Build and deploy updated RA Dashboard
    - Build and deploy updated Admin Dashboard
    - Clear frontend caches
    - Verify no N/A values displayed
    - _Requirements: 4.1, 4.8, 7.4_
  
  - [ ] 20.5 Enable scheduled reconciliation
    - Activate ProcessTransactionReconciliation cron job
    - Verify job runs every 5 minutes
    - Monitor reconciliation logs
    - _Requirements: 10.3, 10.4_

- [ ] 21. Post-deployment monitoring and verification
  - [ ] 21.1 Monitor first 24 hours
    - Check error rates every 15 minutes
    - Verify transaction creation success rate > 99.9%
    - Verify webhook processing works correctly
    - Monitor database performance
    - Check for N/A displays in UI
    - _Requirements: All requirements_
  
  - [ ] 21.2 Verify success metrics
    - Zero N/A displays in production
    - Transaction creation success rate > 99.9%
    - Status reconciliation accuracy > 99.5%
    - API response time p95 < 200ms
    - Zero status conflicts between admin and company views
    - _Requirements: 3.6, 4.4, 7.4, 10.1, 10.4, 12.1_
  
  - [ ] 21.3 Conduct manual verification testing
    - Verify RA Dashboard displays only customer-facing transactions
    - Verify Admin Dashboard displays all 7 transaction types
    - Verify session ID search returns all related transactions
    - Verify transaction status matches between admin and company views
    - Verify no N/A values displayed in any transaction field
    - Verify provider webhook updates transaction status correctly
    - Verify export functionality works
    - _Requirements: 3.6, 4.1, 4.3, 4.4, 4.8, 5.6, 6.4, 7.4, 10.1_
  
  - [ ] 21.4 Review and adjust monitoring thresholds
    - Review alert thresholds based on actual production metrics
    - Adjust critical alert thresholds if needed
    - Adjust warning alert thresholds if needed
    - Document baseline metrics for future reference
    - _Requirements: 15.3_

- [ ] 22. Final checkpoint - Production verification complete
  - All success metrics achieved
  - No critical errors in 24 hours
  - Manual verification tests passed
  - Monitoring and alerting working correctly
  - Documentation updated
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional testing tasks and can be skipped for faster MVP, but are strongly recommended for production deployment
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation at critical milestones
- Property tests validate universal correctness properties (minimum 100 iterations each)
- Unit tests validate specific examples, edge cases, and integration points
- Three-phase migration strategy ensures zero data loss and minimal downtime
- Post-deployment monitoring is critical for catching issues early

## Testing Summary

- **15 Property Tests**: Validate universal correctness across all inputs
- **Unit Tests**: Cover all transaction types, status transitions, validation rules, and error conditions
- **Integration Tests**: Verify complete transaction flows from creation to display
- **Manual Tests**: Verify UI displays correctly with no N/A values
- **Performance Tests**: Ensure query performance meets targets (p95 < 200ms)

## Deployment Timeline Estimate

- **Phase 1**: 2-10 minutes (zero downtime)
- **Phase 2**: 15 minutes - 6 hours (depends on transaction volume, low traffic window)
- **Phase 3**: 15-30 minutes (maintenance window required)
- **Frontend**: 5-10 minutes (zero downtime)
- **Total**: ~1-7 hours depending on transaction volume

## Success Criteria

- ✓ Zero N/A displays in production UI
- ✓ Transaction creation success rate > 99.9%
- ✓ Status reconciliation accuracy > 99.5%
- ✓ API response time p95 < 200ms
- ✓ Zero status conflicts between admin and company views
- ✓ All 15 property tests passing with 100+ iterations
- ✓ All unit tests passing
- ✓ All integration tests passing
- ✓ Manual verification tests passed
