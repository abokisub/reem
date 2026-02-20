# Implementation Plan: Transaction History Enhancement

## Overview

This implementation plan breaks down the transaction history enhancement feature into discrete coding tasks. The feature adds three critical display columns (amount, date, session ID) to transaction history tables and implements CBN-compliant receipt generation for both company users and admin users.

The implementation uses the existing Laravel backend and React frontend, requiring no database migrations since all necessary fields already exist in the transactions table.

## Tasks

- [ ] 1. Set up receipt generation infrastructure
  - Install and configure `barryvdh/laravel-dompdf` package in Laravel
  - Create ReceiptService class at `app/Services/ReceiptService.php`
  - Create receipt Blade template at `resources/views/receipts/transaction.blade.php`
  - _Requirements: AC-6.1, AC-6.2, AC-6.3, AC-6.4_

- [ ]* 1.1 Write property test for receipt number uniqueness
  - **Property 7: Session ID Uniqueness**
  - **Validates: Requirements AC-3.5, AC-8.1**

- [ ] 2. Implement ReceiptService core methods
  - [ ] 2.1 Implement `generateReceipt()` method
    - Generate PDF from transaction data using DomPDF
    - Return HTTP response with PDF stream
    - Set appropriate headers (Content-Type, Content-Disposition)
    - _Requirements: AC-6.3, AC-6.5_
  
  - [ ] 2.2 Implement `getReceiptData()` method
    - Extract transaction details for template
    - Format amounts with currency symbol and decimals
    - Format dates in Nigerian format (DD/MM/YYYY HH:MM:SS WAT)
    - Include company and customer information
    - _Requirements: AC-6.2, AC-8.2, AC-8.3, AC-8.4_
  
  - [ ] 2.3 Implement `generateReceiptNumber()` method
    - Create unique receipt number using pattern: RCP-{YYYYMMDD}-{TRANSACTION_ID}
    - _Requirements: AC-6.2, AC-8.1_
  
  - [ ] 2.4 Implement `validateTransaction()` method
    - Check transaction has required fields
    - Handle missing optional fields with defaults
    - _Requirements: AC-10.5_

- [ ]* 2.5 Write property tests for receipt generation
  - **Property 12: Receipt CBN Field Completeness**
  - **Validates: Requirements AC-6.2, AC-8.6**
  - **Property 13: Receipt PDF Format**
  - **Validates: Requirements AC-6.3**
  - **Property 25: Receipt Missing Field Defaults**
  - **Validates: Requirements AC-10.5**

- [ ]* 2.6 Write unit tests for ReceiptService
  - Test receipt data preparation with sample transaction
  - Test receipt number generation format
  - Test handling of missing optional fields
  - Test validation errors

- [ ] 3. Create receipt Blade template
  - Design professional receipt layout with company branding
  - Display all CBN-required fields (transaction reference, amount, date, customer info, type, status, company info, receipt number)
  - Format amounts with ₦ symbol and thousand separators
  - Format dates in Nigerian standard (DD/MM/YYYY HH:MM:SS WAT)
  - Ensure printable format (A4 size)
  - _Requirements: AC-6.2, AC-6.4, AC-8.6_

- [ ]* 3.1 Write property tests for template formatting
  - **Property 1: Amount Currency Formatting**
  - **Validates: Requirements AC-1.2**
  - **Property 2: Amount Thousand Separator Formatting**
  - **Validates: Requirements AC-1.3**
  - **Property 3: Date Format Compliance**
  - **Validates: Requirements AC-2.1, AC-2.2, AC-2.3**

- [ ] 4. Add receipt generation API endpoints
  - [ ] 4.1 Add company user receipt endpoint
    - Create POST route `/api/company/transactions/{id}/receipt`
    - Implement controller method in TransactionController
    - Validate user can only generate receipts for own company's transactions
    - Call ReceiptService to generate PDF
    - Return PDF stream response
    - _Requirements: AC-6.1, AC-5.1_
  
  - [ ] 4.2 Add admin receipt endpoint
    - Create POST route `/api/admin/transactions/{id}/receipt`
    - Implement controller method in AdminTransController
    - Allow admin to generate receipts for any transaction
    - Call ReceiptService to generate PDF
    - Return PDF stream response
    - _Requirements: AC-6.1, AC-4.1_
  
  - [ ] 4.3 Implement rate limiting
    - Add rate limiting middleware (max 10 receipts per minute per user)
    - Return 429 status with Retry-After header when exceeded
    - _Requirements: NFR-2_

- [ ]* 4.4 Write property tests for API authorization
  - **Property 8: Company User Authorization**
  - **Validates: Requirements AC-5.1**
  - **Property 11: Session ID Search Authorization**
  - **Validates: Requirements AC-5.5**

- [ ]* 4.5 Write property tests for API responses
  - **Property 22: API HTTP Status Code Correctness**
  - **Validates: Requirements AC-10.1**
  - **Property 24: API Error Response Structure**
  - **Validates: Requirements AC-10.3**

- [ ]* 4.6 Write unit tests for receipt endpoints
  - Test company user can generate receipt for own transaction
  - Test company user cannot generate receipt for other company's transaction
  - Test admin can generate receipt for any transaction
  - Test rate limiting works correctly
  - Test error handling for invalid transaction ID

- [ ] 5. Checkpoint - Ensure backend receipt generation works
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 6. Enhance transaction list API endpoints
  - [ ] 6.1 Update company transaction list endpoint
    - Ensure GET `/api/company/transactions` returns amount, created_at, transaction_id
    - Implement pagination (page, limit parameters)
    - Implement search by transaction_id (session ID)
    - Implement filtering by type, status, date range
    - Implement sorting by date (descending by default)
    - Optimize queries to avoid N+1 problem (eager load relationships)
    - _Requirements: AC-1.1, AC-2.1, AC-3.1, AC-3.4, AC-5.2, AC-5.3, AC-5.4, AC-5.5, AC-10.2_
  
  - [ ] 6.2 Update admin transaction list endpoint
    - Ensure GET `/api/admin/transactions` returns amount, created_at, transaction_id, company info
    - Implement pagination (page, limit parameters)
    - Implement search by transaction_id (session ID)
    - Implement filtering by company_id, type, status, date range
    - Implement sorting by date (descending by default)
    - Optimize queries to avoid N+1 problem (eager load relationships)
    - _Requirements: AC-1.1, AC-2.1, AC-3.1, AC-3.4, AC-4.1, AC-4.2, AC-4.3, AC-4.5, AC-10.2_

- [ ]* 6.3 Write property tests for filtering and sorting
  - **Property 4: Date Sorting Correctness**
  - **Validates: Requirements AC-2.4**
  - **Property 6: Session ID Search Accuracy**
  - **Validates: Requirements AC-3.4**
  - **Property 9: Transaction Type Filtering**
  - **Validates: Requirements AC-5.3, AC-4.3**
  - **Property 10: Date Range Filtering**
  - **Validates: Requirements AC-5.4**
  - **Property 23: API Query Optimization**
  - **Validates: Requirements AC-10.2**

- [ ]* 6.4 Write unit tests for transaction list endpoints
  - Test pagination returns correct page and total count
  - Test search by session ID returns matching transactions
  - Test filtering by type returns only matching transactions
  - Test filtering by date range returns transactions within range
  - Test sorting by date returns transactions in descending order
  - Test company user only sees own transactions
  - Test admin sees all transactions

- [ ] 7. Implement error handling for API endpoints
  - Add try-catch blocks for database errors
  - Validate all input parameters (page, limit, dates, transaction_id)
  - Return consistent error response format with success, message, error_code fields
  - Return appropriate HTTP status codes (400, 401, 403, 404, 500)
  - Log errors for debugging without exposing details to users
  - _Requirements: AC-10.1, AC-10.3, AC-10.4_

- [ ]* 7.1 Write property tests for error handling
  - **Property 20: Filter Input Error Handling**
  - **Validates: Requirements AC-9.4**
  - **Property 21: Receipt Generation Error Handling**
  - **Validates: Requirements AC-9.5**

- [ ]* 7.2 Write unit tests for error scenarios
  - Test invalid page number returns 400
  - Test invalid date format returns 400
  - Test unauthorized access returns 401
  - Test forbidden access returns 403
  - Test non-existent transaction returns 404
  - Test database error returns 500 with generic message

- [ ] 8. Checkpoint - Ensure backend APIs work correctly
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 9. Create frontend ReceiptButton component
  - Create `frontend/src/components/ReceiptButton.js`
  - Implement button with loading state during PDF generation
  - Call receipt generation API endpoint
  - Trigger browser download with filename: `receipt-{sessionId}-{date}.pdf`
  - Display success notification on download complete
  - Display error notification on failure with user-friendly message
  - Handle network errors gracefully
  - _Requirements: AC-6.1, AC-7.1, AC-7.2, AC-7.3, AC-7.4, AC-7.5, AC-9.5_

- [ ]* 9.1 Write property test for filename format
  - **Property 14: Receipt Filename Format**
  - **Validates: Requirements AC-7.2**

- [ ]* 9.2 Write unit tests for ReceiptButton
  - Test button renders correctly
  - Test button shows loading state during generation
  - Test button triggers API call with correct transaction ID
  - Test download triggers with correct filename
  - Test success notification displays
  - Test error notification displays on failure

- [ ] 10. Enhance TransactionTable component for company users
  - Update `frontend/src/pages/dashboard/RATransactions.js`
  - Add Amount column with ₦ symbol and thousand separators (right-aligned)
  - Add Date column with format DD/MM/YYYY HH:MM:SS WAT
  - Add Session ID column with transaction_id (with copy-to-clipboard icon)
  - Add ReceiptButton component in Actions column
  - Implement sortable columns (especially date)
  - Implement search input for session ID
  - Implement date range filter (from_date, to_date)
  - Implement status filter dropdown
  - Implement type filter dropdown
  - Show loading skeleton during data fetch
  - Show empty state when no transactions found
  - Handle pagination (page, rowsPerPage controls)
  - _Requirements: AC-1.1, AC-1.2, AC-1.3, AC-1.4, AC-2.1, AC-2.2, AC-2.3, AC-2.4, AC-2.5, AC-3.1, AC-3.2, AC-3.3, AC-3.4, AC-5.2, AC-5.3, AC-5.4, AC-5.5, AC-9.1, AC-9.2, AC-9.6, AC-9.7_

- [ ]* 10.1 Write property tests for data mapping
  - **Property 5: Session ID Data Mapping**
  - **Validates: Requirements AC-3.2**
  - **Property 15: Transaction Amount Decimal Precision**
  - **Validates: Requirements AC-8.2**

- [ ]* 10.2 Write unit tests for RATransactions component
  - Test all columns render correctly
  - Test amount formatting with currency symbol
  - Test date formatting with WAT timezone
  - Test session ID displays transaction_id
  - Test copy-to-clipboard works for session ID
  - Test receipt button appears for each transaction
  - Test search input filters by session ID
  - Test date range filter works
  - Test status filter works
  - Test type filter works
  - Test sorting by date works
  - Test pagination controls work
  - Test loading state displays
  - Test empty state displays

- [ ] 11. Enhance AdminStatement component for admin users
  - Update `frontend/src/pages/admin/AdminStatement.js`
  - Add Amount column with ₦ symbol and thousand separators (right-aligned)
  - Add Date column with format DD/MM/YYYY HH:MM:SS WAT
  - Add Session ID column with transaction_id (with copy-to-clipboard icon)
  - Add Company Name column
  - Add ReceiptButton component in Actions column
  - Implement sortable columns (especially date)
  - Implement search input for session ID
  - Implement company filter dropdown
  - Implement date range filter (from_date, to_date)
  - Implement status filter dropdown
  - Implement type filter dropdown
  - Show loading skeleton during data fetch
  - Show empty state when no transactions found
  - Handle pagination (page, rowsPerPage controls)
  - _Requirements: AC-1.1, AC-1.2, AC-1.3, AC-1.4, AC-2.1, AC-2.2, AC-2.3, AC-2.4, AC-2.5, AC-3.1, AC-3.2, AC-3.3, AC-3.4, AC-4.1, AC-4.2, AC-4.3, AC-4.4, AC-4.5, AC-9.1, AC-9.2, AC-9.6, AC-9.7_

- [ ]* 11.1 Write unit tests for AdminStatement component
  - Test all columns render correctly (including company name)
  - Test amount formatting with currency symbol
  - Test date formatting with WAT timezone
  - Test session ID displays transaction_id
  - Test copy-to-clipboard works for session ID
  - Test receipt button appears for each transaction
  - Test search input filters by session ID
  - Test company filter works
  - Test date range filter works
  - Test status filter works
  - Test type filter works
  - Test sorting by date works
  - Test pagination controls work
  - Test loading state displays
  - Test empty state displays

- [ ] 12. Implement frontend utility functions
  - Create `frontend/src/utils/formatters.js` with:
    - `formatCurrency(amount)` - formats with ₦ symbol and thousand separators
    - `formatDate(dateString)` - formats to DD/MM/YYYY HH:MM:SS WAT
    - `formatSessionId(transactionId)` - formats session ID for display
  - Add copy-to-clipboard utility function
  - _Requirements: AC-1.2, AC-1.3, AC-2.1, AC-2.2, AC-2.3, AC-3.3_

- [ ]* 12.1 Write property tests for formatting functions
  - **Property 1: Amount Currency Formatting**
  - **Validates: Requirements AC-1.2**
  - **Property 2: Amount Thousand Separator Formatting**
  - **Validates: Requirements AC-1.3**
  - **Property 3: Date Format Compliance**
  - **Validates: Requirements AC-2.1, AC-2.2, AC-2.3**

- [ ]* 12.2 Write unit tests for utility functions
  - Test formatCurrency with various amounts (small, large, decimals)
  - Test formatDate with various timestamps
  - Test formatSessionId with various transaction IDs
  - Test copy-to-clipboard function

- [ ] 13. Implement frontend error handling
  - Add error boundary component for React errors
  - Add error notification system (toast/snackbar)
  - Handle API errors with user-friendly messages
  - Handle network errors with retry options
  - Validate date range inputs (from_date before to_date)
  - Show inline validation messages for invalid inputs
  - _Requirements: AC-9.3, AC-9.4, AC-9.5_

- [ ]* 13.1 Write property test for pagination error handling
  - **Property 19: Pagination Error-Free Operation**
  - **Validates: Requirements AC-9.3**

- [ ]* 13.2 Write unit tests for error handling
  - Test error boundary catches React errors
  - Test API error displays notification
  - Test network error shows retry option
  - Test invalid date range shows validation message

- [ ] 14. Add responsive design for mobile and tablet
  - Make transaction table responsive (horizontal scroll on mobile)
  - Adjust column widths for different screen sizes
  - Ensure filters and search work on mobile
  - Test receipt download on mobile browsers
  - _Requirements: NFR-3_

- [ ]* 14.1 Write unit tests for responsive behavior
  - Test table renders on mobile viewport
  - Test filters work on mobile viewport
  - Test receipt button works on mobile

- [ ] 15. Checkpoint - Ensure frontend works correctly
  - Ensure all tests pass, ask the user if questions arise.

- [ ] 16. Add data integrity validations
  - [ ] 16.1 Add database validation for transaction amount precision
    - Ensure amount field stores exactly 2 decimal places
    - _Requirements: AC-8.2_
  
  - [ ] 16.2 Add validation for transaction timestamp immutability
    - Ensure created_at is never modified after creation
    - _Requirements: AC-8.3_
  
  - [ ] 16.3 Add validation for customer linkage
    - Ensure every transaction has company_user_id or recipient_account_name
    - _Requirements: AC-8.4_
  
  - [ ] 16.4 Add validation for audit trail completeness
    - Ensure transaction_id, company_id, created_at, status are always populated
    - _Requirements: AC-8.5_

- [ ]* 16.5 Write property tests for data integrity
  - **Property 15: Transaction Amount Decimal Precision**
  - **Validates: Requirements AC-8.2**
  - **Property 16: Transaction Timestamp Immutability**
  - **Validates: Requirements AC-8.3**
  - **Property 17: Transaction Customer Linkage**
  - **Validates: Requirements AC-8.4**
  - **Property 18: Transaction Audit Trail Completeness**
  - **Validates: Requirements AC-8.5**

- [ ] 17. Integration testing and wiring
  - [ ] 17.1 Test complete flow for company user
    - Login as company user
    - Navigate to transaction history
    - Verify all columns display correctly
    - Test filtering and searching
    - Generate and download receipt
    - _Requirements: All AC-5.x, AC-6.x, AC-7.x_
  
  - [ ] 17.2 Test complete flow for admin user
    - Login as admin
    - Navigate to admin transaction history
    - Verify all columns display correctly (including company name)
    - Test filtering by company
    - Generate and download receipt
    - _Requirements: All AC-4.x, AC-6.x, AC-7.x_
  
  - [ ] 17.3 Test error scenarios end-to-end
    - Test unauthorized access attempts
    - Test invalid input handling
    - Test network error handling
    - Test receipt generation errors
    - _Requirements: AC-9.x, AC-10.x_

- [ ]* 17.4 Write integration tests
  - Test API authentication flow
  - Test company user can only access own transactions
  - Test admin can access all transactions
  - Test receipt generation produces valid PDF
  - Test error handling across frontend and backend

- [ ] 18. Performance optimization
  - Add database indexes for transaction queries (company_id, created_at, transaction_id)
  - Implement query result caching where appropriate
  - Optimize PDF generation (reuse DomPDF instance)
  - Add frontend pagination to limit data fetching
  - _Requirements: NFR-1, AC-10.2, AC-10.6_

- [ ]* 18.1 Write performance tests
  - Test transaction list API response time < 500ms
  - Test receipt generation time < 3 seconds
  - Test page load time < 2 seconds
  - Test N+1 query prevention

- [ ] 19. Final checkpoint - Complete system verification
  - Ensure all tests pass (unit, property, integration)
  - Verify CBN compliance requirements are met
  - Test on all supported browsers (Chrome, Firefox, Safari, Edge)
  - Test on mobile devices (iOS, Android)
  - Verify no console errors in frontend
  - Verify no errors in Laravel logs
  - Ask the user if questions arise before deployment.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Checkpoints ensure incremental validation at key milestones
- Property tests validate universal correctness properties across all inputs
- Unit tests validate specific examples and edge cases
- No database migrations required - all fields already exist in transactions table
- Backend uses PHP/Laravel, frontend uses JavaScript/React
- Receipt generation uses server-side PDF library (barryvdh/laravel-dompdf)
- All formatting follows Nigerian standards (₦ currency, DD/MM/YYYY dates, WAT timezone)
