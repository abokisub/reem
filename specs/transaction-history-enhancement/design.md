# Design Document: Transaction History Enhancement

## Overview

This feature enhances the transaction history display for both company users and admin users by ensuring three critical fields are prominently displayed: transaction amount, transaction date/time, and session ID (transaction reference). Additionally, it implements CBN-compliant receipt generation and download functionality.

The enhancement addresses current gaps in transaction visibility and provides regulatory compliance through proper receipt generation. The system will maintain backward compatibility while adding new display columns and receipt generation capabilities.

### Key Objectives

1. Display amount, date, and session ID in all transaction history tables
2. Implement CBN-compliant receipt generation for individual transactions
3. Ensure error-free operation in both frontend and backend
4. Maintain performance standards (page load < 2s, receipt generation < 3s)
5. Provide role-based access (company users see own transactions, admins see all)

### Research Findings

**CBN Compliance Requirements:**
Based on Central Bank of Nigeria guidelines for financial record-keeping, transaction receipts must include:
- Unique transaction reference (traceable and immutable)
- Transaction amount with 2 decimal precision
- Transaction timestamp (immutable, auditable)
- Customer identification information
- Transaction type and status
- Merchant/company information
- Unique receipt number for audit trail

**PDF Generation in Laravel:**
The recommended approach is using `barryvdh/laravel-dompdf` which:
- Provides server-side PDF generation (more secure than client-side)
- Supports HTML/CSS templates for professional formatting
- Handles Nigerian currency symbols (₦) correctly
- Integrates seamlessly with Laravel's view system
- Supports streaming and download responses

**Frontend Date Formatting:**
Using `date-fns` library (already in React ecosystem) for:
- Nigerian date format (DD/MM/YYYY HH:MM:SS)
- Timezone handling (WAT - UTC+1)
- Sortable date columns
- Date range filtering

## Architecture

### System Components

```
┌─────────────────────────────────────────────────────────────┐
│                     Frontend (React)                         │
├─────────────────────────────────────────────────────────────┤
│  ┌──────────────────┐         ┌──────────────────┐         │
│  │ RATransactions   │         │ AdminStatement   │         │
│  │ Component        │         │ Component        │         │
│  └────────┬─────────┘         └────────┬─────────┘         │
│           │                             │                    │
│           └──────────┬──────────────────┘                    │
│                      │                                       │
│           ┌──────────▼─────────┐                            │
│           │ TransactionTable   │                            │
│           │ (Enhanced Display) │                            │
│           └──────────┬─────────┘                            │
│                      │                                       │
│           ┌──────────▼─────────┐                            │
│           │ ReceiptButton      │                            │
│           │ Component          │                            │
│           └──────────┬─────────┘                            │
└──────────────────────┼─────────────────────────────────────┘
                       │
                       │ HTTP/REST API
                       │
┌──────────────────────▼─────────────────────────────────────┐
│                  Backend (Laravel)                          │
├─────────────────────────────────────────────────────────────┤
│  ┌──────────────────┐         ┌──────────────────┐         │
│  │ TransactionAPI   │         │ AdminTransAPI    │         │
│  │ Controller       │         │ Controller       │         │
│  └────────┬─────────┘         └────────┬─────────┘         │
│           │                             │                    │
│           └──────────┬──────────────────┘                    │
│                      │                                       │
│           ┌──────────▼─────────┐                            │
│           │ ReceiptService     │                            │
│           │ (PDF Generation)   │                            │
│           └──────────┬─────────┘                            │
│                      │                                       │
│           ┌──────────▼─────────┐                            │
│           │ Transaction Model  │                            │
│           └──────────┬─────────┘                            │
└──────────────────────┼─────────────────────────────────────┘
                       │
                       │
┌──────────────────────▼─────────────────────────────────────┐
│                    Database (MySQL)                         │
├─────────────────────────────────────────────────────────────┤
│  - transactions table (existing)                            │
│  - companies table (existing)                               │
│  - company_users table (existing)                           │
│  - users table (existing)                                   │
└─────────────────────────────────────────────────────────────┘
```

### Data Flow

**Transaction List Display:**
1. User navigates to transaction history page
2. Frontend component requests transaction data via API
3. Backend controller queries database with pagination, filtering, sorting
4. Backend applies authorization (company users see own, admins see all)
5. Backend returns JSON with transaction data including amount, date, transaction_id
6. Frontend renders table with enhanced columns

**Receipt Generation:**
1. User clicks "Generate Receipt" button for a transaction
2. Frontend sends POST request to receipt generation endpoint
3. Backend validates user authorization for that transaction
4. Backend retrieves transaction details with related data (company, customer)
5. ReceiptService generates PDF using Blade template
6. Backend streams PDF response with appropriate headers
7. Frontend triggers browser download with meaningful filename

## Components and Interfaces

### Frontend Components

#### Enhanced TransactionTable Component

**Location:** `frontend/src/components/TransactionTable.js` (new) or enhance existing table

**Props:**
```typescript
interface TransactionTableProps {
  transactions: Transaction[];
  isAdmin: boolean;
  onReceiptDownload: (transactionId: string) => void;
  loading: boolean;
  page: number;
  rowsPerPage: number;
  totalCount: number;
  onPageChange: (page: number) => void;
  onRowsPerPageChange: (rows: number) => void;
  onSearch: (query: string) => void;
}
```

**Columns:**
- Session ID (transaction_id) - with copy-to-clipboard
- Customer Name/Email
- Amount (₦ formatted with 2 decimals)
- Date (DD/MM/YYYY HH:MM:SS WAT)
- Status (with color-coded chip)
- Type
- Fee (if applicable)
- Actions (View Details, Generate Receipt)

**Features:**
- Sortable columns (especially date)
- Search by session ID
- Date range filter
- Status filter
- Responsive design (mobile, tablet, desktop)

#### ReceiptButton Component

**Location:** `frontend/src/components/ReceiptButton.js` (new)

**Props:**
```typescript
interface ReceiptButtonProps {
  transactionId: string;
  sessionId: string;
  onDownloadStart: () => void;
  onDownloadComplete: () => void;
  onError: (error: string) => void;
}
```

**Behavior:**
- Shows loading state during PDF generation
- Triggers download with filename: `receipt-{sessionId}-{date}.pdf`
- Displays success/error notifications
- Handles network errors gracefully

### Backend Components

#### ReceiptService

**Location:** `app/Services/ReceiptService.php` (new)

**Methods:**

```php
class ReceiptService
{
    /**
     * Generate PDF receipt for a transaction
     * 
     * @param Transaction $transaction
     * @return \Illuminate\Http\Response
     */
    public function generateReceipt(Transaction $transaction): Response;
    
    /**
     * Get receipt data for template
     * 
     * @param Transaction $transaction
     * @return array
     */
    protected function getReceiptData(Transaction $transaction): array;
    
    /**
     * Generate unique receipt number
     * 
     * @param Transaction $transaction
     * @return string
     */
    protected function generateReceiptNumber(Transaction $transaction): string;
    
    /**
     * Validate transaction for receipt generation
     * 
     * @param Transaction $transaction
     * @return bool
     * @throws \Exception
     */
    protected function validateTransaction(Transaction $transaction): bool;
}
```

**Receipt Data Structure:**
```php
[
    'receipt_number' => 'RCP-20260219-TXN123456',
    'transaction_id' => 'txn_abc123',
    'amount' => '1500.00',
    'currency' => 'NGN',
    'date' => '19/02/2026 14:30:45 WAT',
    'status' => 'successful',
    'type' => 'deposit',
    'customer' => [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'phone' => '+234...',
    ],
    'company' => [
        'name' => 'ABC Company Ltd',
        'email' => 'support@abc.com',
        'address' => '...',
    ],
    'fee' => '15.00',
    'net_amount' => '1485.00',
    'description' => 'Virtual account deposit',
    'generated_at' => '19/02/2026 15:00:00 WAT',
]
```

#### API Endpoints

**Company User Endpoints:**

```
GET /api/company/transactions
- Query params: page, limit, search, status, date_from, date_to
- Response: Paginated transaction list with amount, date, session ID
- Authorization: Company user token

POST /api/company/transactions/{id}/receipt
- Response: PDF stream
- Authorization: Company user can only generate receipts for own transactions
```

**Admin Endpoints:**

```
GET /api/admin/transactions
- Query params: page, limit, search, status, company_id, date_from, date_to
- Response: Paginated transaction list (all companies)
- Authorization: Admin token

POST /api/admin/transactions/{id}/receipt
- Response: PDF stream
- Authorization: Admin can generate receipts for any transaction
```

### Receipt Template

**Location:** `resources/views/receipts/transaction.blade.php` (new)

**Design Requirements:**
- Professional layout with company branding
- Clear hierarchy of information
- CBN-compliant field display
- Printable format (A4 size)
- QR code for verification (optional enhancement)

**Template Structure:**
```
┌─────────────────────────────────────────┐
│  Company Logo          RECEIPT          │
│                                         │
│  Receipt #: RCP-20260219-TXN123456     │
│  Generated: 19/02/2026 15:00:00 WAT    │
├─────────────────────────────────────────┤
│  TRANSACTION DETAILS                    │
│                                         │
│  Session ID: txn_abc123                │
│  Date: 19/02/2026 14:30:45 WAT        │
│  Status: Successful                     │
│  Type: Deposit                          │
├─────────────────────────────────────────┤
│  AMOUNT BREAKDOWN                       │
│                                         │
│  Amount:        ₦1,500.00              │
│  Fee:           ₦15.00                 │
│  Net Amount:    ₦1,485.00              │
├─────────────────────────────────────────┤
│  CUSTOMER INFORMATION                   │
│                                         │
│  Name: John Doe                         │
│  Email: john@example.com               │
├─────────────────────────────────────────┤
│  COMPANY INFORMATION                    │
│                                         │
│  ABC Company Ltd                        │
│  support@abc.com                        │
└─────────────────────────────────────────┘
```

## Data Models

### Transaction Model (Existing - No Changes Required)

The existing `Transaction` model already contains all necessary fields:

```php
// Existing fields used for display and receipts
- id (primary key)
- transaction_id (session ID/reference)
- company_id (foreign key)
- company_user_id (foreign key)
- amount (decimal 2 places)
- fee (decimal 2 places)
- net_amount (decimal 2 places)
- currency (default: NGN)
- status (enum)
- type (string)
- created_at (timestamp)
- updated_at (timestamp)
- recipient_account_name (customer name)
- recipient_account_number
- recipient_bank_name
- description
- metadata (JSON)
```

**No database migrations required** - all necessary data already exists in the transactions table.

### Receipt Number Format

Receipt numbers will be generated using the pattern:
```
RCP-{YYYYMMDD}-{TRANSACTION_ID}
```

Example: `RCP-20260219-TXN123456`

This ensures:
- Uniqueness (tied to transaction_id)
- Traceability (date embedded)
- CBN compliance (unique identifier)
- Human readability


## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property Reflection

After analyzing all acceptance criteria, I identified several areas of redundancy:

1. **UI Column Visibility**: AC-1.1, AC-3.1, AC-4.1, AC-4.2, AC-4.5, AC-5.2 all test that specific columns are visible. These can be consolidated into comprehensive UI rendering tests rather than separate properties.

2. **CBN Field Requirements**: AC-6.2 and AC-8.6 are duplicates - both require receipts to include all CBN-mandated fields.

3. **Filtering Properties**: AC-4.3, AC-5.3, AC-5.4 all test filtering behavior. These can be combined into a general filtering property.

4. **Error Handling**: AC-9.3, AC-9.4, AC-9.5, AC-10.4 all test error handling. These can be consolidated into comprehensive error handling properties.

5. **Performance Requirements**: AC-6.5 and AC-10.6 are performance tests that should be handled separately from functional properties.

The following properties represent the unique, non-redundant correctness requirements:

### Property 1: Amount Currency Formatting

*For any* transaction amount displayed in the UI, the formatted string should include the Nigerian Naira symbol (₦).

**Validates: Requirements AC-1.2**

### Property 2: Amount Thousand Separator Formatting

*For any* transaction amount greater than or equal to 1000, the formatted display should include thousand separators (e.g., 1,500.00).

**Validates: Requirements AC-1.3**

### Property 3: Date Format Compliance

*For any* transaction date displayed, the format should match the pattern DD/MM/YYYY HH:MM:SS and include the WAT timezone indicator.

**Validates: Requirements AC-2.1, AC-2.2, AC-2.3**

### Property 4: Date Sorting Correctness

*For any* list of transactions sorted by date, each transaction's date should be greater than or equal to the next transaction's date (descending order).

**Validates: Requirements AC-2.4**

### Property 5: Session ID Data Mapping

*For any* transaction displayed in the UI, the session ID shown should exactly match the transaction's transaction_id field from the database.

**Validates: Requirements AC-3.2**

### Property 6: Session ID Search Accuracy

*For any* session ID search query, the results should only include transactions where the transaction_id matches the search query.

**Validates: Requirements AC-3.4**

### Property 7: Session ID Uniqueness

*For any* two different transactions in the system, their transaction_id values should be unique.

**Validates: Requirements AC-3.5, AC-8.1**

### Property 8: Company User Authorization

*For any* company user requesting transaction history, all returned transactions should have a company_id matching the user's company_id.

**Validates: Requirements AC-5.1**

### Property 9: Transaction Type Filtering

*For any* transaction type filter applied, all returned transactions should have a type field matching the filter value.

**Validates: Requirements AC-5.3, AC-4.3**

### Property 10: Date Range Filtering

*For any* date range filter (from_date, to_date), all returned transactions should have created_at timestamps within the specified range (inclusive).

**Validates: Requirements AC-5.4**

### Property 11: Session ID Search Authorization

*For any* company user searching by session ID, if a matching transaction exists but belongs to a different company, it should not be returned in the results.

**Validates: Requirements AC-5.5**

### Property 12: Receipt CBN Field Completeness

*For any* generated receipt PDF, the content should include all CBN-mandated fields: transaction reference, amount, date/time, customer information, transaction type, transaction status, company information, and unique receipt number.

**Validates: Requirements AC-6.2, AC-8.6**

### Property 13: Receipt PDF Format

*For any* receipt generation request, the response should be a valid PDF document (Content-Type: application/pdf).

**Validates: Requirements AC-6.3**

### Property 14: Receipt Filename Format

*For any* receipt download, the filename should follow the pattern `receipt-{session_id}-{date}.pdf` where session_id is the transaction_id and date is in YYYYMMDD format.

**Validates: Requirements AC-7.2**

### Property 15: Transaction Amount Decimal Precision

*For any* transaction stored in the database, the amount field should have exactly 2 decimal places.

**Validates: Requirements AC-8.2**

### Property 16: Transaction Timestamp Immutability

*For any* transaction, once created, the created_at timestamp should never be modified (updates should only affect updated_at).

**Validates: Requirements AC-8.3**

### Property 17: Transaction Customer Linkage

*For any* transaction in the system, it should have either a company_user_id or recipient_account_name populated (customer information present).

**Validates: Requirements AC-8.4**

### Property 18: Transaction Audit Trail Completeness

*For any* transaction created, it should have all audit fields populated: transaction_id, company_id, created_at, and status.

**Validates: Requirements AC-8.5**

### Property 19: Pagination Error-Free Operation

*For any* valid page number and rows-per-page value, the pagination request should return a successful response without errors.

**Validates: Requirements AC-9.3**

### Property 20: Filter Input Error Handling

*For any* filter or search input (including edge cases like empty strings, special characters), the system should handle it without throwing errors.

**Validates: Requirements AC-9.4**

### Property 21: Receipt Generation Error Handling

*For any* receipt generation error (e.g., missing data, PDF generation failure), the API should return a proper error response with a user-friendly message rather than crashing.

**Validates: Requirements AC-9.5**

### Property 22: API HTTP Status Code Correctness

*For any* API request, the response should have an appropriate HTTP status code: 200 for success, 400 for bad request, 401 for unauthorized, 404 for not found, 500 for server error.

**Validates: Requirements AC-10.1**

### Property 23: API Query Optimization

*For any* transaction list request, the number of database queries should remain constant regardless of the number of transactions returned (no N+1 query problem).

**Validates: Requirements AC-10.2**

### Property 24: API Error Response Structure

*For any* API error response (4xx or 5xx status), the response body should include a message field with a descriptive error message.

**Validates: Requirements AC-10.3**

### Property 25: Receipt Missing Field Defaults

*For any* transaction with missing optional fields (e.g., description, recipient_bank_name), receipt generation should use appropriate default values (e.g., "N/A", "Not specified") rather than failing.

**Validates: Requirements AC-10.5**

## Error Handling

### Frontend Error Handling

**Network Errors:**
- Display user-friendly error messages when API requests fail
- Show retry options for transient failures
- Maintain UI state during errors (don't lose user's filter/search inputs)

**Data Validation Errors:**
- Validate date range inputs (from_date must be before to_date)
- Validate search inputs (minimum length, allowed characters)
- Show inline validation messages

**Receipt Download Errors:**
- Catch PDF generation failures and show notification
- Handle browser download blocking with instructions
- Provide fallback option to open PDF in new tab

**Loading States:**
- Show skeleton loaders during initial data fetch
- Show button loading states during receipt generation
- Disable actions during processing to prevent double-clicks

**Empty States:**
- Show helpful message when no transactions found
- Provide suggestions (clear filters, adjust date range)
- Show different messages for "no data" vs "no results for filter"

### Backend Error Handling

**Authorization Errors:**
- Return 401 for unauthenticated requests
- Return 403 for unauthorized access (e.g., company user accessing another company's transaction)
- Log authorization failures for security monitoring

**Validation Errors:**
- Return 400 with detailed validation messages
- Validate all input parameters (page, limit, dates, transaction_id)
- Sanitize search inputs to prevent SQL injection

**Database Errors:**
- Catch query exceptions and return 500 with generic message
- Log detailed error for debugging (don't expose to user)
- Use database transactions for data consistency

**PDF Generation Errors:**
- Catch DomPDF exceptions and return 500 with user-friendly message
- Validate transaction data before PDF generation
- Provide fallback for missing template assets (logo, fonts)

**Rate Limiting:**
- Implement rate limiting on receipt generation endpoint (max 10 per minute per user)
- Return 429 (Too Many Requests) when limit exceeded
- Include Retry-After header

### Error Response Format

All API errors should follow this consistent format:

```json
{
    "success": false,
    "message": "User-friendly error message",
    "error_code": "SPECIFIC_ERROR_CODE",
    "details": {
        "field": "Additional context (optional)"
    }
}
```

**Error Codes:**
- `UNAUTHORIZED`: User not authenticated
- `FORBIDDEN`: User lacks permission
- `INVALID_INPUT`: Validation failed
- `NOT_FOUND`: Resource doesn't exist
- `RATE_LIMIT_EXCEEDED`: Too many requests
- `PDF_GENERATION_FAILED`: Receipt generation error
- `DATABASE_ERROR`: Database operation failed
- `INTERNAL_ERROR`: Unexpected server error

## Testing Strategy

### Dual Testing Approach

This feature requires both unit testing and property-based testing to ensure comprehensive coverage:

**Unit Tests** focus on:
- Specific examples of formatting (e.g., "1500.00" formats to "₦1,500.00")
- UI component rendering (columns visible, buttons present)
- Integration points (API endpoints return expected structure)
- Edge cases (empty lists, missing data, error states)

**Property-Based Tests** focus on:
- Universal properties across all inputs (formatting rules, filtering correctness)
- Authorization rules (company users only see own data)
- Data integrity (uniqueness, precision, immutability)
- Error handling (system handles any input without crashing)

Together, these approaches provide comprehensive coverage: unit tests catch concrete bugs in specific scenarios, while property tests verify general correctness across the entire input space.

### Property-Based Testing Configuration

**Library Selection:**
- **PHP Backend**: Use `eris/eris` for property-based testing in PHPUnit
- **JavaScript Frontend**: Use `fast-check` for property-based testing in Jest/Vitest

**Test Configuration:**
- Each property test should run minimum 100 iterations
- Use appropriate generators for test data (amounts, dates, transaction IDs)
- Seed random generators for reproducibility

**Test Tagging:**
Each property-based test must include a comment tag referencing the design property:

```php
/**
 * @test
 * Feature: transaction-history-enhancement, Property 1: Amount Currency Formatting
 */
public function test_amount_includes_naira_symbol()
{
    // Property test implementation
}
```

```javascript
/**
 * Feature: transaction-history-enhancement, Property 3: Date Format Compliance
 */
test('transaction dates follow Nigerian format with WAT timezone', () => {
    // Property test implementation
});
```

### Unit Testing Strategy

**Backend Unit Tests** (`tests/Feature/TransactionHistoryTest.php`):
- Test transaction list API returns correct structure
- Test pagination works correctly
- Test filtering by company, type, date range
- Test search by session ID
- Test authorization (company users vs admin)
- Test receipt generation endpoint
- Test error responses

**Frontend Unit Tests** (`frontend/src/components/__tests__/TransactionTable.test.js`):
- Test table renders with all columns
- Test amount formatting displays correctly
- Test date formatting displays correctly
- Test session ID copy-to-clipboard
- Test filter controls work
- Test pagination controls work
- Test receipt button triggers download
- Test loading and empty states

**Service Unit Tests** (`tests/Unit/ReceiptServiceTest.php`):
- Test receipt data preparation
- Test receipt number generation
- Test PDF generation succeeds
- Test handling of missing optional fields
- Test validation of transaction data

### Integration Testing

**API Integration Tests:**
- Test full flow: authenticate → fetch transactions → generate receipt
- Test admin can access all companies' transactions
- Test company user cannot access other companies' transactions
- Test receipt download produces valid PDF
- Test error handling across API boundaries

**Frontend Integration Tests:**
- Test user can filter transactions and see results
- Test user can search by session ID and find transaction
- Test user can generate and download receipt
- Test error messages display correctly
- Test loading states appear during async operations

### Performance Testing

**Load Testing:**
- Test transaction list API with 10,000+ records
- Verify pagination performance (response time < 500ms)
- Test receipt generation under load (< 3 seconds per receipt)
- Test concurrent receipt generation (10 simultaneous requests)

**Query Performance:**
- Verify no N+1 queries using Laravel Debugbar
- Test database indexes are used for filtering/sorting
- Measure query execution time for large datasets

### Manual Testing Checklist

**Cross-Browser Testing:**
- [ ] Chrome (latest 2 versions)
- [ ] Firefox (latest 2 versions)
- [ ] Safari (latest 2 versions)
- [ ] Edge (latest 2 versions)

**Responsive Testing:**
- [ ] Desktop (1920x1080, 1366x768)
- [ ] Tablet (iPad, Android tablet)
- [ ] Mobile (iPhone, Android phone)

**Receipt Testing:**
- [ ] Receipt opens in PDF reader
- [ ] Receipt prints correctly
- [ ] Receipt contains all required fields
- [ ] Receipt formatting is professional
- [ ] Receipt filename is meaningful

**CBN Compliance Testing:**
- [ ] All transactions have unique references
- [ ] Amounts stored with 2 decimal precision
- [ ] Timestamps are immutable
- [ ] Customer information is linked
- [ ] Audit trail is complete
- [ ] Receipts include all mandated fields

### Test Data Requirements

**Test Transactions:**
- Various amounts (small, large, with decimals)
- Various dates (recent, old, different times)
- Various statuses (successful, pending, failed)
- Various types (deposit, transfer, withdrawal)
- Multiple companies
- Multiple customers
- Edge cases (zero amount, missing optional fields)

**Test Users:**
- Company user with transactions
- Company user without transactions
- Admin user
- Unauthenticated user (for authorization tests)

### Continuous Integration

**Automated Test Execution:**
- Run all unit tests on every commit
- Run property-based tests on every pull request
- Run integration tests before deployment
- Generate code coverage reports (target: 80%+ coverage)

**Quality Gates:**
- All tests must pass before merge
- No decrease in code coverage
- No new linting errors
- Performance benchmarks must pass

