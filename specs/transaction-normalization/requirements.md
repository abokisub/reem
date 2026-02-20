# Requirements Document

## Introduction

This specification addresses critical architectural issues in the transaction management system that are causing data inconsistencies, status mismatches, and N/A field displays in financial records. The system currently lacks a single source of truth for transaction data, resulting in conflicting status information between admin and company views, improper display of internal ledger entries in customer-facing dashboards, and missing critical tracking fields like session_id and transaction_ref.

This is a bank-grade financial system refactoring that will establish strict transaction type separation, enforce NOT NULL constraints on critical fields, implement a canonical status source, and filter internal accounting entries from customer-facing views.

## Glossary

- **Transaction_System**: The core financial transaction management system responsible for recording, tracking, and displaying all financial events
- **RA_Dashboard**: The Receivable Account dashboard visible to companies showing their customer-facing transactions
- **Admin_Dashboard**: The administrative interface showing all system transactions including internal accounting entries
- **Transaction_Type**: An enumerated classification of financial events (va_deposit, company_withdrawal, api_transfer, kyc_charge, refund, fee_charge, manual_adjustment)
- **Session_ID**: A unique identifier linking related transaction events and API requests
- **Transaction_Ref**: A unique reference number for each transaction used for external communication and reconciliation
- **Provider_Reference**: The external payment provider's reference number for a transaction
- **Settlement_Status**: The state of fund settlement (settled, unsettled, not_applicable)
- **Canonical_Status**: The single authoritative source of truth for transaction status stored in transactions.status
- **Internal_Ledger_Entry**: System-generated accounting entries (fee_charge, manual_adjustment) that should not appear in customer-facing views
- **Customer_Facing_Transaction**: Transactions initiated by or visible to end customers (va_deposit, api_transfer, company_withdrawal, refund)

## Requirements

### Requirement 1: Transaction Schema Normalization

**User Story:** As a system administrator, I want all transactions to have complete required fields with NOT NULL constraints, so that financial records never display N/A values and data integrity is guaranteed at the database level.

#### Acceptance Criteria

1. THE Transaction_System SHALL enforce NOT NULL constraints on transaction_ref, session_id, amount, status, and transaction_type fields
2. THE Transaction_System SHALL reject any transaction creation attempt with missing required fields before database insertion
3. THE Transaction_System SHALL generate unique transaction_ref values for all new transactions
4. THE Transaction_System SHALL generate session_id values at transaction initiation for all new transactions
5. THE Transaction_System SHALL validate that amount values are greater than zero before insertion
6. THE Transaction_System SHALL set fee to zero by default when not specified
7. THE Transaction_System SHALL calculate and store net_amount as (amount - fee) for all transactions
8. WHEN a transaction is created, THE Transaction_System SHALL validate that transaction_type matches one of the seven enumerated values
9. THE Transaction_System SHALL enforce UNIQUE constraint on transaction_ref field
10. THE Transaction_System SHALL create database indexes on session_id and transaction_ref for search performance

### Requirement 2: Transaction Type Classification

**User Story:** As a financial analyst, I want strict transaction type separation using enumerated values, so that different financial events are never mixed incorrectly and reporting is accurate.

#### Acceptance Criteria

1. THE Transaction_System SHALL support exactly seven transaction types: va_deposit, company_withdrawal, api_transfer, kyc_charge, refund, fee_charge, manual_adjustment
2. THE Transaction_System SHALL implement transaction_type as a database ENUM with the seven specified values
3. THE Transaction_System SHALL reject any transaction with a transaction_type value not in the enumerated list
4. WHEN a virtual account receives funds, THE Transaction_System SHALL create a transaction with type va_deposit
5. WHEN a company withdraws funds, THE Transaction_System SHALL create a transaction with type company_withdrawal
6. WHEN a customer initiates an API transfer, THE Transaction_System SHALL create a transaction with type api_transfer
7. WHEN a KYC verification is charged, THE Transaction_System SHALL create a transaction with type kyc_charge
8. WHEN funds are returned to a customer, THE Transaction_System SHALL create a transaction with type refund
9. WHEN a service fee is charged, THE Transaction_System SHALL create a transaction with type fee_charge
10. WHEN an administrator makes a manual correction, THE Transaction_System SHALL create a transaction with type manual_adjustment

### Requirement 3: Single Source of Truth for Transaction Status

**User Story:** As a company user, I want transaction status to be consistent across all system views, so that I see the same status information as administrators and can trust the system data.

#### Acceptance Criteria

1. THE Transaction_System SHALL use transactions.status as the only canonical source for transaction status
2. THE Transaction_System SHALL ignore status values from other tables when displaying transaction information
3. WHEN a transaction status is updated, THE Transaction_System SHALL update only the transactions.status field
4. THE Transaction_System SHALL support exactly five status values: pending, processing, successful, failed, reversed
5. WHEN the payment provider confirms success, THE Transaction_System SHALL set status to successful AND settlement_status to settled
6. THE Transaction_System SHALL display identical status values in both Admin_Dashboard and company views for the same transaction
7. THE Transaction_System SHALL implement status as a database ENUM with the five specified values
8. WHEN a status conflict is detected between transactions table and provider data, THE Transaction_System SHALL log the conflict and use transactions.status as authoritative
9. THE Transaction_System SHALL provide a status reconciliation process that updates transactions.status based on provider confirmation
10. THE Transaction_System SHALL prevent direct status updates that bypass validation logic

### Requirement 4: RA Dashboard Transaction Filtering

**User Story:** As a company user viewing the RA Dashboard, I want to see only customer-facing transactions, so that internal ledger splits, revenue entries, and system balancing entries are hidden from my view.

#### Acceptance Criteria

1. THE RA_Dashboard SHALL display only transactions with types: va_deposit, api_transfer, company_withdrawal, refund
2. THE RA_Dashboard SHALL exclude transactions with types: fee_charge, kyc_charge, manual_adjustment
3. THE RA_Dashboard SHALL filter transactions by company_id to show only the logged-in company's transactions
4. THE RA_Dashboard SHALL display transaction_ref, session_id, amount, fee, net_amount, status, and settlement_status for each transaction
5. THE RA_Dashboard SHALL never display N/A for any transaction field
6. WHEN a company views their RA Dashboard, THE Transaction_System SHALL query only customer-facing transaction types
7. THE RA_Dashboard SHALL sort transactions by created_at in descending order by default
8. THE RA_Dashboard SHALL provide search functionality by session_id and transaction_ref
9. THE RA_Dashboard SHALL display customer_id for each transaction when available
10. THE RA_Dashboard SHALL show settlement_status as "Not Applicable" only when settlement_status field equals not_applicable

### Requirement 5: Session ID Tracking and Searchability

**User Story:** As a support agent, I want to search transactions by session_id, so that I can trace all related transaction events for a customer inquiry or debugging session.

#### Acceptance Criteria

1. THE Transaction_System SHALL generate a unique session_id for every transaction at initiation time
2. THE Transaction_System SHALL create a database index on session_id for efficient searching
3. THE Transaction_System SHALL link related transactions using the same session_id value
4. THE Transaction_System SHALL display session_id in all transaction list views
5. THE Transaction_System SHALL provide a search interface that accepts session_id as input
6. WHEN a user searches by session_id, THE Transaction_System SHALL return all transactions with matching session_id
7. THE Transaction_System SHALL validate that session_id is not null before saving a transaction
8. THE Transaction_System SHALL format session_id as a UUID or similar unique identifier
9. THE RA_Dashboard SHALL display session_id as a clickable link that filters to all transactions with that session_id
10. THE Admin_Dashboard SHALL display session_id as a clickable link that filters to all transactions with that session_id

### Requirement 6: Provider Reference Tracking

**User Story:** As a financial reconciliation specialist, I want to track external provider references for each transaction, so that I can match our internal records with provider statements and resolve discrepancies.

#### Acceptance Criteria

1. THE Transaction_System SHALL store provider_reference for transactions processed by external payment providers
2. THE Transaction_System SHALL allow provider_reference to be null for transactions without external provider involvement
3. WHEN a payment provider returns a reference number, THE Transaction_System SHALL store it in the provider_reference field
4. THE Transaction_System SHALL display provider_reference in transaction detail views
5. THE Transaction_System SHALL provide search functionality by provider_reference
6. WHEN reconciling with provider statements, THE Transaction_System SHALL match transactions using provider_reference
7. THE Transaction_System SHALL update provider_reference when received asynchronously via webhook
8. THE Transaction_System SHALL log any changes to provider_reference for audit purposes
9. THE Transaction_System SHALL display provider_reference as "N/A" only when the field is genuinely null and not applicable
10. THE Admin_Dashboard SHALL allow filtering transactions by presence or absence of provider_reference

### Requirement 7: Settlement Status Management

**User Story:** As a finance manager, I want accurate settlement status tracking for all transactions, so that I can distinguish between settled funds, pending settlements, and transactions that don't require settlement.

#### Acceptance Criteria

1. THE Transaction_System SHALL support three settlement_status values: settled, unsettled, not_applicable
2. THE Transaction_System SHALL implement settlement_status as a database ENUM with the three specified values
3. THE Transaction_System SHALL set settlement_status to not_applicable for transaction types that don't involve settlement
4. WHEN a transaction is confirmed successful by the provider, THE Transaction_System SHALL set settlement_status to settled
5. WHEN a transaction is created but not yet confirmed, THE Transaction_System SHALL set settlement_status to unsettled
6. THE Transaction_System SHALL display settlement_status in all transaction views
7. THE Transaction_System SHALL allow filtering transactions by settlement_status
8. WHEN settlement_status is not_applicable, THE Transaction_System SHALL display "Not Applicable" in user interfaces
9. THE Transaction_System SHALL never display N/A for settlement_status field
10. THE Transaction_System SHALL update settlement_status to settled when provider webhook confirms settlement

### Requirement 8: Admin Dashboard Transaction Display

**User Story:** As a system administrator, I want to view all transaction types including internal ledger entries, so that I can audit the complete financial picture and debug system issues.

#### Acceptance Criteria

1. THE Admin_Dashboard SHALL display all seven transaction types without filtering
2. THE Admin_Dashboard SHALL show transaction_type as a column in the transaction list
3. THE Admin_Dashboard SHALL allow filtering by transaction_type
4. THE Admin_Dashboard SHALL display all transaction fields including internal accounting fields
5. THE Admin_Dashboard SHALL calculate statement totals using only the transactions table
6. THE Admin_Dashboard SHALL not reference ledger or other tables for transaction status
7. THE Admin_Dashboard SHALL display identical status values as shown in company views for the same transaction
8. THE Admin_Dashboard SHALL provide export functionality for all transaction data
9. THE Admin_Dashboard SHALL show transaction_ref, session_id, provider_reference, and all other fields without N/A displays
10. THE Admin_Dashboard SHALL allow searching by transaction_ref, session_id, and provider_reference

### Requirement 9: Data Migration and Validation

**User Story:** As a database administrator, I want to migrate existing transactions to the new schema with validation, so that historical data conforms to new constraints and no data is lost.

#### Acceptance Criteria

1. THE Transaction_System SHALL provide a migration script that adds NOT NULL constraints to existing schema
2. THE Transaction_System SHALL generate session_id values for existing transactions that lack them
3. THE Transaction_System SHALL generate transaction_ref values for existing transactions that lack them
4. THE Transaction_System SHALL validate all existing transactions against new constraints before applying them
5. WHEN existing transactions have null required fields, THE Transaction_System SHALL generate appropriate default values
6. THE Transaction_System SHALL log all data transformations performed during migration
7. THE Transaction_System SHALL provide a rollback mechanism for the migration
8. THE Transaction_System SHALL validate that no transactions have N/A string values in any field after migration
9. THE Transaction_System SHALL create database indexes on session_id and transaction_ref during migration
10. THE Transaction_System SHALL verify data integrity after migration by running validation queries

### Requirement 10: Transaction Status Reconciliation

**User Story:** As a system operator, I want automated status reconciliation between our system and payment providers, so that status mismatches are detected and corrected without manual intervention.

#### Acceptance Criteria

1. WHEN a webhook is received from a payment provider, THE Transaction_System SHALL update transactions.status to match provider confirmation
2. WHEN a status mismatch is detected, THE Transaction_System SHALL log the discrepancy with both status values
3. THE Transaction_System SHALL provide a reconciliation report showing transactions with status mismatches
4. WHEN the ledger shows debit and provider shows success, THE Transaction_System SHALL set status to successful
5. THE Transaction_System SHALL update settlement_status to settled when provider confirms successful settlement
6. THE Transaction_System SHALL provide an admin interface to manually reconcile status conflicts
7. THE Transaction_System SHALL record the timestamp of status changes for audit purposes
8. THE Transaction_System SHALL prevent status changes that violate business rules (e.g., successful to pending)
9. WHEN reconciliation updates a status, THE Transaction_System SHALL notify relevant stakeholders
10. THE Transaction_System SHALL run automated reconciliation checks on a scheduled basis

### Requirement 11: Validation Layer for Transaction Creation

**User Story:** As a backend developer, I want a validation layer that rejects invalid transactions before database insertion, so that data integrity is enforced at the application level and database constraints are never violated.

#### Acceptance Criteria

1. THE Transaction_System SHALL validate all required fields are present before attempting database insertion
2. WHEN a required field is missing, THE Transaction_System SHALL return a descriptive error message
3. THE Transaction_System SHALL validate that amount is greater than zero
4. THE Transaction_System SHALL validate that transaction_type is one of the seven enumerated values
5. THE Transaction_System SHALL validate that status is one of the five enumerated values
6. THE Transaction_System SHALL validate that settlement_status is one of the three enumerated values
7. THE Transaction_System SHALL validate that transaction_ref is unique before insertion
8. THE Transaction_System SHALL validate that company_id exists in the companies table
9. WHEN customer_id is provided, THE Transaction_System SHALL validate it exists in the customers table
10. THE Transaction_System SHALL calculate net_amount as (amount - fee) and validate the result is non-negative

### Requirement 12: Transaction Query Optimization

**User Story:** As a system user, I want fast transaction queries and searches, so that dashboards load quickly even with large transaction volumes.

#### Acceptance Criteria

1. THE Transaction_System SHALL create a composite index on (company_id, created_at) for company transaction queries
2. THE Transaction_System SHALL create an index on session_id for session-based searches
3. THE Transaction_System SHALL create an index on transaction_ref for reference-based searches
4. THE Transaction_System SHALL create an index on provider_reference for provider reconciliation queries
5. THE Transaction_System SHALL create a composite index on (transaction_type, status) for filtered queries
6. THE RA_Dashboard SHALL use indexed columns in WHERE clauses for filtering
7. THE Transaction_System SHALL limit query results to a maximum page size with pagination support
8. THE Transaction_System SHALL use database query optimization techniques to minimize query execution time
9. WHEN querying transactions, THE Transaction_System SHALL select only required columns rather than SELECT *
10. THE Transaction_System SHALL cache frequently accessed transaction counts and aggregates

### Requirement 13: Transaction Parsing and Display Formatting

**User Story:** As a frontend developer, I want consistent transaction data formatting from the API, so that I never need to handle null values or display N/A in the user interface.

#### Acceptance Criteria

1. THE Transaction_System SHALL return all required fields with non-null values in API responses
2. THE Transaction_System SHALL format amounts as decimal numbers with two decimal places
3. THE Transaction_System SHALL format timestamps in ISO 8601 format
4. THE Transaction_System SHALL return transaction_type as a human-readable string
5. THE Transaction_System SHALL return status as a human-readable string
6. THE Transaction_System SHALL return settlement_status as a human-readable string
7. WHEN provider_reference is null, THE Transaction_System SHALL omit the field from API response or return empty string
8. THE Transaction_System SHALL include a calculated net_amount field in all transaction responses
9. THE Transaction_System SHALL provide consistent field naming across all transaction API endpoints
10. FOR ALL valid transaction objects returned by the API, parsing then formatting then parsing SHALL produce an equivalent object (round-trip property)

### Requirement 14: Transaction History Completeness

**User Story:** As a compliance officer, I want complete transaction history with no missing fields, so that I can generate accurate financial reports and pass regulatory audits.

#### Acceptance Criteria

1. THE Transaction_System SHALL retain all transaction records indefinitely
2. THE Transaction_System SHALL never delete transaction records
3. THE Transaction_System SHALL record created_at timestamp for all transactions
4. THE Transaction_System SHALL record updated_at timestamp and update it on any modification
5. THE Transaction_System SHALL maintain an audit log of all status changes
6. THE Transaction_System SHALL maintain an audit log of all amount or fee modifications
7. THE Transaction_System SHALL provide an export function that includes all transaction fields
8. THE Transaction_System SHALL support date range filtering for transaction queries
9. THE Transaction_System SHALL provide transaction count and sum aggregates by transaction_type
10. THE Transaction_System SHALL ensure zero transactions have null values in required fields

### Requirement 15: Error Handling and Logging

**User Story:** As a system administrator, I want comprehensive error logging for transaction operations, so that I can diagnose issues and ensure no transactions are lost due to errors.

#### Acceptance Criteria

1. WHEN a transaction validation fails, THE Transaction_System SHALL log the validation error with transaction details
2. WHEN a database constraint violation occurs, THE Transaction_System SHALL log the error and return a user-friendly message
3. WHEN a status reconciliation fails, THE Transaction_System SHALL log the failure with both status values
4. THE Transaction_System SHALL log all transaction creation attempts including failed attempts
5. THE Transaction_System SHALL log all transaction status updates with old and new values
6. WHEN a provider webhook fails to update a transaction, THE Transaction_System SHALL log the webhook payload and error
7. THE Transaction_System SHALL provide log aggregation showing error rates by error type
8. THE Transaction_System SHALL alert administrators when error rates exceed thresholds
9. THE Transaction_System SHALL include transaction_ref and session_id in all log entries for traceability
10. THE Transaction_System SHALL retain error logs for at least 90 days for debugging and audit purposes
