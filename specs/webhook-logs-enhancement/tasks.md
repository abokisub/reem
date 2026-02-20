# Implementation Plan: Webhook Logs Enhancement

## Overview

This implementation enhances the webhook logs display system to show both incoming webhooks (from PalmPay stored in `palmpay_webhooks` table) and outgoing webhooks (to companies stored in `webhook_logs` table) in a unified, chronologically sorted interface. The backend will combine data from both tables with proper filtering for admin vs company users, and the frontend will display direction indicators to distinguish webhook types.

## Tasks

- [x] 1. Enhance backend API to combine incoming and outgoing webhooks
  - [x] 1.1 Update CompanyLogsController::getWebhooks() to query both tables
    - Modify the existing method to query both `palmpay_webhooks` and `webhook_logs` tables
    - Add UNION query to combine incoming and outgoing webhooks
    - Add direction field ('incoming' or 'outgoing') to distinguish webhook types
    - Normalize field names across both webhook types (event_type, status, created_at)
    - Map incoming webhooks: set webhook_url and http_status to 'N/A', attempt_number to 1
    - Map outgoing webhooks: include webhook_url, http_status, attempt_number from table
    - For company users: filter incoming webhooks by transactions.company_id, filter outgoing by webhook_logs.company_id
    - For admin users: include company_name joins for both webhook types
    - Sort combined results by created_at DESC
    - Apply pagination to combined results
    - _Requirements: 1.1, 2.1, 3.1, 3.2, 4.1, 4.2, 5.1, 6.1, 6.2_

  - [ ]* 1.2 Write property test for admin webhook retrieval
    - **Property 1: Admin users retrieve both webhook types**
    - **Validates: Requirements 1.1, 2.1, 3.1**

  - [ ]* 1.3 Write property test for company webhook filtering
    - **Property 2: Company users retrieve filtered webhook types**
    - **Validates: Requirements 4.1, 4.2, 5.1, 6.1**

- [x] 2. Implement unified webhook response structure
  - [x] 2.1 Create normalized response schema for both webhook types
    - Ensure all webhook records include: id, direction, event_type, status, created_at
    - For incoming webhooks: include transaction_ref, transaction_amount (company view)
    - For outgoing webhooks: include webhook_url, http_status, attempt_number
    - For admin view: include company_name for both webhook types
    - Handle null values gracefully: display 'N/A' for missing fields
    - Include pagination metadata: total, per_page, current_page, last_page
    - _Requirements: 1.2, 1.4, 1.5, 2.2, 2.4, 4.4, 5.3, 9.1, 9.2, 9.3, 9.4, 10.1, 10.3, 10.4_

  - [ ]* 2.2 Write property test for incoming webhook fields
    - **Property 3: Incoming webhooks have required fields**
    - **Validates: Requirements 1.2, 4.4, 10.1**

  - [ ]* 2.3 Write property test for outgoing webhook fields
    - **Property 4: Outgoing webhooks have required fields**
    - **Validates: Requirements 2.2, 5.3, 8.1, 10.1**

  - [ ]* 2.4 Write property test for direction indicators
    - **Property 5: All webhooks have direction indicators**
    - **Validates: Requirements 1.3, 2.3, 4.3, 5.2, 7.1, 10.2**

- [x] 3. Implement sorting and pagination for combined webhooks
  - [x] 3.1 Add chronological sorting to combined webhook query
    - Ensure ORDER BY created_at DESC applies to UNION query results
    - Verify sorting works correctly across both webhook types
    - Test with mixed timestamps from both tables
    - _Requirements: 3.2, 6.2_

  - [x] 3.2 Implement pagination for combined results
    - Apply Laravel pagination to combined UNION query
    - Support configurable page sizes: 5, 10, 20, 50
    - Ensure pagination metadata is accurate for combined results
    - Handle edge cases: empty results, single page, partial last page
    - _Requirements: 3.3, 3.4, 6.3, 6.4, 8.4_

  - [ ]* 3.3 Write property test for chronological sorting
    - **Property 8: Combined webhook list maintains chronological sort order**
    - **Validates: Requirements 3.2, 6.2**

  - [ ]* 3.4 Write property test for pagination sort order
    - **Property 9: Pagination maintains sort order across pages**
    - **Validates: Requirements 3.3, 6.3**

  - [ ]* 3.5 Write property test for page size limits
    - **Property 10: Pagination respects configured page size**
    - **Validates: Requirements 3.4, 6.4, 8.4**

- [ ] 4. Checkpoint - Ensure backend tests pass
  - Ensure all tests pass, ask the user if questions arise.

- [x] 5. Update frontend to display both webhook types
  - [x] 5.1 Add direction column to WebhookLogs.js table
    - Add 'direction' as first column in TABLE_HEAD array
    - Update table structure to include direction cell
    - Ensure column aligns with backend response structure
    - _Requirements: 7.1_

  - [x] 5.2 Implement direction indicator badges
    - Create Label component for direction with color coding
    - Use 'info' color (blue) for incoming webhooks
    - Use 'warning' color (orange) for outgoing webhooks
    - Display "Incoming from PalmPay" for incoming webhooks
    - Display "Outgoing to Company" for outgoing webhooks
    - _Requirements: 1.3, 2.3, 4.3, 5.2, 7.2, 7.3, 7.4_

  - [x] 5.3 Update table cells to handle both webhook types
    - Display webhook_url for outgoing, 'N/A' for incoming
    - Display http_status for outgoing, 'N/A' for incoming
    - Display attempt_number for both types (1 for incoming)
    - Display transaction_ref and transaction_amount for incoming (company view)
    - Display company_name for admin view
    - Handle null/undefined values gracefully
    - _Requirements: 1.2, 2.2, 4.4, 5.3, 9.1, 9.2, 9.3, 9.4_

  - [x] 5.4 Update API call to use new response structure
    - Parse webhook_logs.data array from response
    - Handle pagination metadata from response
    - Update state management for combined webhook list
    - Maintain backward compatibility with existing features
    - _Requirements: 10.1, 10.4_

- [x] 6. Preserve existing frontend functionality
  - [x] 6.1 Verify status color coding still works
    - Ensure success statuses display green labels
    - Ensure failed statuses display red labels
    - Test with both incoming and outgoing webhook statuses
    - _Requirements: 8.2_

  - [x] 6.2 Verify dense padding toggle functionality
    - Ensure Switch component still controls table density
    - Test that dense mode applies to all rows including new direction column
    - _Requirements: 8.3_

  - [x] 6.3 Verify pagination controls work correctly
    - Test page size selector with options: 5, 10, 20, 50
    - Test page navigation with combined webhook results
    - Verify pagination displays correct total count
    - _Requirements: 8.4_

  - [x] 6.4 Verify empty state handling
    - Test display when no webhooks exist
    - Ensure "No results found" message appears correctly
    - Test loading state during API calls
    - _Requirements: 8.5_

- [x] 7. Add admin webhook logs view (if not already implemented)
  - [x] 7.1 Create or update admin webhook logs component
    - Implement similar structure to company WebhookLogs.js
    - Add company_name column for admin view
    - Use same direction indicators and color coding
    - Connect to admin API endpoint
    - _Requirements: 1.1, 1.2, 1.3, 1.4, 1.5, 2.1, 2.2, 2.3, 2.4, 3.1, 3.2, 3.3, 3.4_

  - [ ]* 7.2 Write unit tests for admin webhook component
    - Test that admin sees all companies' webhooks
    - Test company name display
    - Test direction indicators
    - Test pagination and sorting

- [x] 8. Integration testing and error handling
  - [x] 8.1 Test authentication and authorization
    - Verify admin users can access all webhooks
    - Verify company users see only their webhooks
    - Test invalid token handling (returns empty results)
    - Test user with no active_company_id (returns empty results)
    - _Requirements: 1.1, 2.1, 4.1, 5.1_

  - [x] 8.2 Test database join operations
    - Verify incoming webhooks correctly join to transactions and companies
    - Verify outgoing webhooks correctly join to companies
    - Test with missing associations (null transaction_id, null company_id)
    - Verify 'N/A' displays for broken associations
    - _Requirements: 1.4, 1.5, 2.4, 9.1, 9.3_

  - [ ]* 8.3 Write property test for company name associations
    - **Property 6: Incoming webhooks show company names when associated**
    - **Property 7: Outgoing webhooks show company names when associated**
    - **Validates: Requirements 1.4, 2.4**

  - [x] 8.4 Test error handling scenarios
    - Test database connection errors (returns 500 with error message)
    - Test invalid pagination parameters (uses defaults)
    - Test empty result sets (returns empty array with pagination metadata)
    - Verify frontend handles API errors gracefully
    - _Requirements: 9.1, 9.2, 9.3, 9.4_

- [ ] 9. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Backend uses PHP/Laravel with Eloquent ORM and query builder
- Frontend uses React with Material-UI components
- Property tests validate universal correctness properties
- Unit tests validate specific examples and edge cases
- The enhancement is additive only - all existing functionality is preserved
