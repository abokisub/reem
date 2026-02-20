# Design Document: Webhook Logs Enhancement

## Overview

This design enhances the webhook logs display system to show both incoming webhooks (from PalmPay) and outgoing webhooks (to companies) in a unified interface. Currently, the system only displays outgoing webhooks from the `webhook_logs` table, resulting in empty displays when companies haven't configured webhook endpoints. The system already receives and stores incoming PalmPay webhooks in the `palmpay_webhooks` table, but these are invisible to users.

The enhancement provides complete webhook visibility by:
- Combining data from both `palmpay_webhooks` (incoming) and `webhook_logs` (outgoing) tables
- Displaying webhooks in chronological order with clear direction indicators
- Filtering appropriately for admin users (all webhooks) vs company users (company-specific webhooks)
- Maintaining all existing functionality while adding new capabilities

This allows admins to monitor PalmPay webhook activity without accessing the PalmPay dashboard, and enables companies to see the complete webhook flow for their transactions.

## Architecture

### System Components

The webhook logs enhancement follows a three-tier architecture:

1. **Data Layer**: Two existing database tables
   - `palmpay_webhooks`: Stores incoming webhooks from PalmPay
   - `webhook_logs`: Stores outgoing webhooks sent to company endpoints

2. **API Layer**: Backend controllers that query and combine webhook data
   - `CompanyLogsController`: Handles company user webhook requests
   - `AdminController`: Handles admin user webhook requests

3. **Presentation Layer**: React frontend components
   - `WebhookLogs.js`: Company user webhook logs page
   - Admin webhook logs component (embedded in admin dashboard)

### Data Flow

```
┌─────────────────┐
│   PalmPay API   │
└────────┬────────┘
         │ Incoming Webhooks
         ▼
┌─────────────────────────┐
│  palmpay_webhooks table │
└─────────────────────────┘
         │
         │ Query & Combine
         ▼
┌─────────────────────────┐      ┌──────────────────┐
│   API Controller        │◄─────│  Frontend UI     │
│  (Unified Response)     │─────►│  (Display Both)  │
└─────────────────────────┘      └──────────────────┘
         ▲
         │ Query & Combine
         │
┌─────────────────────────┐
│   webhook_logs table    │
└─────────────────────────┘
         ▲
         │ Outgoing Webhooks
┌────────┴────────┐
│  Company URLs   │
└─────────────────┘
```

### Key Design Decisions

1. **Server-Side Combination**: Webhook data from both tables is combined at the API layer rather than the frontend. This ensures consistent sorting, proper pagination, and reduces frontend complexity.

2. **Unified Response Schema**: Both incoming and outgoing webhooks are normalized to a common response structure with a `direction` field to distinguish them.

3. **Backward Compatibility**: All existing table columns and frontend features are preserved. The enhancement is additive only.

4. **Permission-Based Filtering**: Admin users see all webhooks across all companies; company users see only their own webhooks. This filtering happens at the database query level for security.

## Components and Interfaces

### Backend Components

#### 1. CompanyLogsController::getWebhooks()

**Purpose**: Retrieve and combine webhook logs for company users

**Current Implementation**: Only queries `palmpay_webhooks` table
**Enhanced Implementation**: Queries both tables and combines results

**Method Signature**:
```php
public function getWebhooks(Request $request): JsonResponse
```

**Input Parameters**:
- `id` (string): User access token
- `limit` (int, optional): Records per page (default: 50)
- `page` (int, optional): Current page number

**Output Structure**:
```json
{
  "status": "success",
  "webhook_logs": {
    "data": [
      {
        "id": "string",
        "direction": "incoming|outgoing",
        "event_type": "string",
        "status": "string",
        "created_at": "timestamp",
        "webhook_url": "string|N/A",
        "http_status": "int|N/A",
        "attempt_number": "int",
        "transaction_ref": "string",
        "transaction_amount": "decimal",
        "company_name": "string"
      }
    ],
    "total": 0,
    "per_page": 50,
    "current_page": 1,
    "last_page": 1
  }
}
```

**Logic Flow**:
1. Validate user authentication and retrieve user record
2. Determine if user is admin or company user
3. For company users:
   - Query `palmpay_webhooks` filtered by company (via transaction association)
   - Query `webhook_logs` filtered by company_id
   - Combine results with direction indicators
   - Sort by timestamp descending
   - Apply pagination
4. For admin users:
   - Query all `palmpay_webhooks` with company name joins
   - Query all `webhook_logs` with company name joins
   - Combine results with direction indicators
   - Sort by timestamp descending
   - Apply pagination

#### 2. AdminController::getAllWebhookLogs()

**Purpose**: Retrieve and combine webhook logs for admin users

**Current Implementation**: Only queries `webhook_logs` table
**Enhanced Implementation**: Queries both tables and combines results

**Method Signature**:
```php
public function getAllWebhookLogs(Request $request): JsonResponse
```

**Input/Output**: Same structure as CompanyLogsController::getWebhooks()

### Frontend Components

#### 1. WebhookLogs.js (Company Dashboard)

**Current State**: Displays only outgoing webhooks
**Enhanced State**: Displays both incoming and outgoing webhooks

**New Table Columns**:
```javascript
const TABLE_HEAD = [
    { id: 'direction', label: 'Direction', alignRight: false },
    { id: 'event_type', label: 'Event Type', alignRight: false },
    { id: 'webhook_url', label: 'Webhook URL', alignRight: false },
    { id: 'http_status', label: 'HTTP Status', alignRight: false },
    { id: 'status', label: 'Status', alignRight: false },
    { id: 'attempts', label: 'Attempts', alignRight: false },
    { id: 'created_at', label: 'Date', alignRight: false },
];
```

**Direction Indicator Component**:
```javascript
<Label
    variant="ghost"
    color={direction === 'incoming' ? 'info' : 'warning'}
>
    {direction === 'incoming' ? 'Incoming' : 'Outgoing'}
</Label>
```

#### 2. Admin Webhook Logs Component

Similar structure to company component but displays company names for all webhooks.

### Database Schema

#### palmpay_webhooks Table (Existing)
```sql
- id (bigint, primary key)
- transaction_id (bigint, nullable, foreign key)
- event_type (string)
- status (string)
- payload (json)
- created_at (timestamp)
- updated_at (timestamp)
```

#### webhook_logs Table (Existing)
```sql
- id (bigint, primary key)
- company_id (bigint, nullable, foreign key)
- transaction_id (bigint, nullable, foreign key)
- event_type (string)
- webhook_url (string)
- payload (json)
- response (text, nullable)
- http_status (int, nullable)
- status (string)
- attempt_number (int, default: 1)
- created_at (timestamp)
- updated_at (timestamp)
```

## Data Models

### Unified Webhook Record Model

The API returns a normalized webhook record regardless of source table:

```typescript
interface WebhookRecord {
  id: string;
  direction: 'incoming' | 'outgoing';
  event_type: string;
  status: string;
  created_at: string;
  
  // Optional fields (may be N/A)
  webhook_url?: string;
  http_status?: number;
  attempt_number?: number;
  transaction_ref?: string;
  transaction_amount?: number;
  company_name?: string;
}
```

### Field Mapping

**From palmpay_webhooks (incoming)**:
- `id` → `id`
- `'incoming'` → `direction`
- `event_type` → `event_type`
- `status` → `status`
- `created_at` → `created_at`
- `'N/A'` → `webhook_url`
- `'N/A'` → `http_status`
- `1` → `attempt_number`
- `transactions.reference` → `transaction_ref`
- `transactions.amount` → `transaction_amount`
- `users.name` → `company_name` (via transaction → company → user join)

**From webhook_logs (outgoing)**:
- `id` → `id`
- `'outgoing'` → `direction`
- `event_type` → `event_type`
- `status` → `status`
- `created_at` → `created_at`
- `webhook_url` → `webhook_url`
- `http_status` → `http_status`
- `attempt_number` → `attempt_number`
- `'N/A'` → `transaction_ref`
- `'N/A'` → `transaction_amount`
- `companies.name` → `company_name` (via company join)

### Query Strategy

**Company User Query**:
```sql
-- Incoming webhooks
SELECT 
  palmpay_webhooks.id,
  'incoming' as direction,
  palmpay_webhooks.event_type,
  palmpay_webhooks.status,
  palmpay_webhooks.created_at,
  'N/A' as webhook_url,
  'N/A' as http_status,
  1 as attempt_number,
  transactions.reference as transaction_ref,
  transactions.amount as transaction_amount
FROM palmpay_webhooks
LEFT JOIN transactions ON palmpay_webhooks.transaction_id = transactions.id
WHERE transactions.company_id = ?

UNION ALL

-- Outgoing webhooks
SELECT 
  webhook_logs.id,
  'outgoing' as direction,
  webhook_logs.event_type,
  webhook_logs.status,
  webhook_logs.created_at,
  webhook_logs.webhook_url,
  webhook_logs.http_status,
  webhook_logs.attempt_number,
  'N/A' as transaction_ref,
  'N/A' as transaction_amount
FROM webhook_logs
WHERE webhook_logs.company_id = ?

ORDER BY created_at DESC
```

**Admin User Query**: Same structure but without WHERE clauses, and includes company_name joins.


## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system—essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Property Reflection

After analyzing all acceptance criteria, several redundancies were identified:

- **Sorting properties (3.2, 6.2, 6.3)**: The sorting behavior is the same for both admin and company users, so these can be combined into universal sorting properties.
- **Pagination properties (3.4, 6.4, 8.4)**: Pagination configuration is system-wide, not user-specific, so one property covers all cases.
- **Direction indicator properties (1.3, 4.3, 7.1, 10.2)**: All specify that webhooks must have direction indicators, which can be combined into one comprehensive property.
- **Field presence properties (1.2, 2.2, 4.4, 5.3, 8.1, 10.1)**: These all verify that webhook records contain required fields, which can be consolidated into properties about response schema consistency.
- **N/A handling (1.5, 9.1, 9.3, 9.4)**: All edge cases about displaying "N/A" for missing data can be handled by generators in property tests rather than separate properties.

The following properties represent the unique, non-redundant correctness requirements:

### Property 1: Admin users retrieve both webhook types

*For any* admin user accessing the webhook logs endpoint, the system should query and return records from both the `palmpay_webhooks` table (incoming) and the `webhook_logs` table (outgoing).

**Validates: Requirements 1.1, 2.1, 3.1**

### Property 2: Company users retrieve filtered webhook types

*For any* company user accessing the webhook logs endpoint, the system should return only webhooks associated with that user's company from both the `palmpay_webhooks` table (filtered via transaction association) and the `webhook_logs` table (filtered by company_id).

**Validates: Requirements 4.1, 4.2, 5.1, 6.1**

### Property 3: Incoming webhooks have required fields

*For any* incoming webhook record returned by the system, the response should include event_type, status, created_at, and direction fields, with webhook_url and http_status set to "N/A".

**Validates: Requirements 1.2, 4.4, 10.1**

### Property 4: Outgoing webhooks have required fields

*For any* outgoing webhook record returned by the system, the response should include event_type, webhook_url, http_status, status, attempt_number, created_at, and direction fields.

**Validates: Requirements 2.2, 5.3, 8.1, 10.1**

### Property 5: All webhooks have direction indicators

*For any* webhook record returned by the system, the record should include a direction field with value either "incoming" or "outgoing".

**Validates: Requirements 1.3, 2.3, 4.3, 5.2, 7.1, 10.2**

### Property 6: Incoming webhooks show company names when associated

*For any* incoming webhook that has a transaction_id linking to a transaction with a company_id, the response should include the associated company name.

**Validates: Requirements 1.4**

### Property 7: Outgoing webhooks show company names when associated

*For any* outgoing webhook that has a company_id, the response should include the associated company name.

**Validates: Requirements 2.4**

### Property 8: Combined webhook list maintains chronological sort order

*For any* list of webhooks returned by the system, each webhook's timestamp should be greater than or equal to the next webhook's timestamp (descending order, newest first), regardless of whether the webhook is incoming or outgoing.

**Validates: Requirements 3.2, 6.2**

### Property 9: Pagination maintains sort order across pages

*For any* two consecutive pages of webhook results, the timestamp of the last webhook on page N should be greater than or equal to the timestamp of the first webhook on page N+1.

**Validates: Requirements 3.3, 6.3**

### Property 10: Pagination respects configured page size

*For any* valid page size parameter (5, 10, 20, or 50), the system should return at most that many webhook records per page.

**Validates: Requirements 3.4, 6.4, 8.4**

### Property 11: Pagination metadata is complete

*For any* paginated webhook response, the response should include pagination metadata fields: total, per_page, current_page, and last_page.

**Validates: Requirements 10.4**

### Property 12: Field names are normalized across webhook types

*For any* webhook record (incoming or outgoing), fields representing the same concept should use the same field name (e.g., event_type, status, created_at).

**Validates: Requirements 10.3**

### Property 13: Response schema is consistent

*For any* webhook record returned by the system, the record should have the same set of top-level fields, with some fields containing "N/A" when not applicable.

**Validates: Requirements 10.1**

## Error Handling

### Invalid User Authentication

**Scenario**: User provides invalid or expired access token
**Handling**: Return empty webhook list with success status and zero count
**Rationale**: Prevents information leakage about authentication failures while maintaining API contract

```php
return response()->json([
    'status' => 'success',
    'webhook_logs' => [
        'data' => [],
        'total' => 0,
        'per_page' => $request->limit ?? 50,
        'current_page' => 1
    ]
]);
```

### Missing Company Association

**Scenario**: Company user has no active_company_id
**Handling**: Return empty webhook list with success status
**Rationale**: Valid state for new users who haven't completed onboarding

### Database Query Failures

**Scenario**: Database connection error or query timeout
**Handling**: Return error response with 500 status code and error message
**Rationale**: Distinguishes system failures from empty results

```php
return response()->json([
    'status' => 'error',
    'webhook_logs' => [
        'data' => [],
        'total' => 0,
        'per_page' => $request->limit ?? 50,
        'current_page' => 1
    ],
    'message' => $e->getMessage()
], 500);
```

### Missing Transaction or Company Data

**Scenario**: Webhook references non-existent transaction or company
**Handling**: Display "N/A" for missing fields, include webhook in results
**Rationale**: Webhook data is still valuable even if associations are broken

### Invalid Pagination Parameters

**Scenario**: User requests invalid page size or page number
**Handling**: Use default values (page size: 50, page: 1)
**Rationale**: Graceful degradation maintains functionality

### Empty Result Sets

**Scenario**: No webhooks exist for the user's filter criteria
**Handling**: Return empty data array with proper pagination metadata
**Rationale**: Distinguishes "no data" from "error" states

## Testing Strategy

### Dual Testing Approach

This feature requires both unit tests and property-based tests to ensure comprehensive coverage:

**Unit Tests**: Focus on specific examples, edge cases, and integration points
- Test specific webhook record structures
- Test authentication and authorization logic
- Test database join operations
- Test empty result handling
- Test error conditions (invalid tokens, missing companies)

**Property-Based Tests**: Verify universal properties across all inputs
- Test sorting invariants with random webhook datasets
- Test pagination consistency with varying page sizes
- Test field presence across random webhook types
- Test filtering correctness with random user/company combinations
- Test response schema consistency

### Property-Based Testing Configuration

**Library**: Use `fakerphp/faker` for PHP data generation and PHPUnit for test execution
**Iterations**: Minimum 100 iterations per property test
**Tagging**: Each property test must reference its design document property

Tag format:
```php
/**
 * @test
 * Feature: webhook-logs-enhancement, Property 8: Combined webhook list maintains chronological sort order
 */
```

### Test Data Generation

**Generators needed**:
- Random user records (admin and company types)
- Random company records
- Random transaction records
- Random incoming webhook records (palmpay_webhooks)
- Random outgoing webhook records (webhook_logs)
- Random pagination parameters (page size, page number)
- Random timestamps (ensuring variety for sort testing)

**Edge cases to include in generators**:
- Webhooks with null transaction_id
- Webhooks with null company_id
- Webhooks with null http_status
- Empty result sets
- Single-record result sets
- Result sets exactly matching page size
- Result sets requiring multiple pages

### Unit Test Coverage

**Authentication Tests**:
- Valid admin user can access all webhooks
- Valid company user can access only their webhooks
- Invalid token returns empty results
- User with no company returns empty results

**Data Retrieval Tests**:
- Admin endpoint queries both tables
- Company endpoint queries both tables with filters
- Incoming webhooks include correct fields
- Outgoing webhooks include correct fields
- Direction indicators are set correctly

**Join Operation Tests**:
- Incoming webhooks correctly join to transactions and companies
- Outgoing webhooks correctly join to companies
- Missing associations result in "N/A" values

**Pagination Tests**:
- Page size parameter is respected
- Page number parameter is respected
- Pagination metadata is correct
- Last page handles partial results correctly

**Error Handling Tests**:
- Database errors return 500 status
- Invalid parameters use defaults
- Missing data displays "N/A"

### Property Test Coverage

Each correctness property should have a corresponding property-based test:

1. **Property 1 Test**: Generate random admin user, verify response includes both webhook types
2. **Property 2 Test**: Generate random company user, verify response includes only their webhooks
3. **Property 3 Test**: Generate random incoming webhooks, verify all have required fields
4. **Property 4 Test**: Generate random outgoing webhooks, verify all have required fields
5. **Property 5 Test**: Generate random webhooks, verify all have direction field
6. **Property 6 Test**: Generate incoming webhooks with/without transactions, verify company names
7. **Property 7 Test**: Generate outgoing webhooks with/without companies, verify company names
8. **Property 8 Test**: Generate random mixed webhooks, verify chronological sorting
9. **Property 9 Test**: Generate multi-page webhook sets, verify sort order across pages
10. **Property 10 Test**: Generate random page sizes, verify record counts
11. **Property 11 Test**: Generate random webhook sets, verify pagination metadata presence
12. **Property 12 Test**: Generate random webhooks, verify field name consistency
13. **Property 13 Test**: Generate random webhooks, verify schema consistency

### Integration Testing

**Frontend-Backend Integration**:
- Test that frontend correctly parses API response structure
- Test that direction indicators display correctly
- Test that pagination controls work with API
- Test that "N/A" values display correctly in UI

**Database Integration**:
- Test with real database schema
- Test with realistic data volumes
- Test query performance with large datasets
- Test concurrent access scenarios

### Manual Testing Checklist

- [ ] Admin user sees all webhooks from all companies
- [ ] Company user sees only their own webhooks
- [ ] Incoming and outgoing webhooks are visually distinct
- [ ] Webhooks are sorted newest first
- [ ] Pagination works correctly
- [ ] Empty state displays "No results found"
- [ ] "N/A" displays for missing fields
- [ ] Dense padding toggle still works
- [ ] Page size selector works (5, 10, 20, 50)
- [ ] Company names display correctly for admin view
- [ ] Transaction details display correctly for company view

