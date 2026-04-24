# Design Document: KYC Pool Management Enhancements

## Overview

This design addresses three critical operational issues in the KYC Pool Management system:

1. **Sparse Global KYC Pool**: The global_kyc_pool table lacks sufficient BVN/NIN entries, limiting fallback capacity when company director KYC is exhausted
2. **Non-functional Refresh Operations**: The "Refresh" button in the admin dashboard doesn't actually assign fresh KYC from the pool or update company status
3. **Missing Virtual Account Detection**: No automated mechanism exists to identify and regenerate virtual accounts for customers who lack them

The solution enhances the existing GlobalKycService and VirtualAccountService to provide robust KYC rotation, automated VA regeneration, and comprehensive health monitoring through an improved admin dashboard.

### Key Design Goals

- Enable administrators to populate and manage a healthy global KYC pool (target: 50+ entries)
- Implement functional KYC refresh that assigns least-used pool entries to companies
- Automate detection and bulk regeneration of missing virtual accounts
- Provide real-time visibility into KYC pool health and company VA usage
- Ensure circuit breaker resilience during bulk operations
- Maintain backward compatibility with existing virtual account creation flow

## Architecture

### System Components

The enhancement builds upon the existing architecture with minimal changes:

```
┌─────────────────────────────────────────────────────────────┐
│                     Admin Dashboard                          │
│              (KycPoolManagement.js)                          │
│  - Pool Statistics Cards                                     │
│  - Company Health Table                                      │
│  - Missing VA Detection                                      │
│  - Bulk Add/Refresh/Regenerate Actions                       │
└────────────────────┬────────────────────────────────────────┘
                     │
                     │ REST API
                     ▼
┌─────────────────────────────────────────────────────────────┐
│              KycPoolController                               │
│  - overview()          : Dashboard data aggregation          │
│  - entries()           : List pool entries                   │
│  - add()               : Bulk add BVN/NIN                    │
│  - assignFresh()       : Assign fresh KYC to company         │
│  - regenerateMissing() : Bulk VA regeneration                │
│  - deactivate()        : Disable pool entry                  │
└────────────┬───────────────────────┬────────────────────────┘
             │                       │
             ▼                       ▼
┌────────────────────────┐  ┌──────────────────────────────┐
│  GlobalKycService      │  │  VirtualAccountService       │
│  - selectOptimalKyc()  │  │  - createVirtualAccount()    │
│  - recordUsage()       │  │  - selectOptimalKycMethod    │
│  - blacklistKyc()      │  │    WithGlobalFallback()      │
│  - getUsageStats()     │  │  - callPalmPayWithKyc        │
└────────┬───────────────┘  │    Fallback()                │
         │                  └──────────┬───────────────────┘
         │                             │
         ▼                             ▼
┌─────────────────────────────────────────────────────────────┐
│                    Database Layer                            │
│  - global_kyc_pool                                           │
│  - global_kyc_usage_logs                                     │
│  - virtual_accounts                                          │
│  - companies                                                 │
│  - company_users                                             │
└─────────────────────────────────────────────────────────────┘
```

### Data Flow

#### 1. KYC Pool Population Flow
```
Admin → Add BVN/NIN → KycPoolController.add()
  → Validate entries (10-20 chars)
  → Check duplicates
  → Insert into global_kyc_pool
  → Return summary (added/skipped counts)
```

#### 2. Company KYC Refresh Flow
```
Admin → Click Refresh → KycPoolController.assignFresh(companyId)
  → Query GlobalKycPool.available()
    → Filter: is_active=true, usage_count < max_usage, not blacklisted
    → Order by: kyc_type (NIN first), usage_count ASC
  → Select least-used NIN and BVN
  → Update company.director_nin, company.director_bvn
  → Clear company.kyc_method_blacklist
  → Set company.kyc_refreshed_at = now()
  → Recalculate company health status
  → Return success message
```

#### 3. Missing VA Detection Flow
```
Dashboard Load → KycPoolController.overview()
  → Query all CompanyUser records
  → Left join VirtualAccount (exclude deleted)
  → Identify customers without VA
  → Group by company_id
  → Return missing VA list with customer details
```

#### 4. VA Regeneration Flow
```
Admin → Regenerate All/Fix → KycPoolController.regenerateMissing()
  → Reset circuit breaker cache keys
  → For each missing customer:
    → Call VirtualAccountService.createVirtualAccount()
      → Try company director KYC
      → On failure: Try global KYC pool
      → On success: Increment created counter
      → On failure: Increment failed counter, log error
    → Sleep 2 seconds (rate limiting)
  → Return summary (created, failed, error messages)
```

#### 5. Global KYC Fallback Flow (Existing Enhancement)
```
VirtualAccountService.createVirtualAccount()
  → selectOptimalKycMethodWithGlobalFallback()
    → Try customer BVN/NIN (if provided)
    → Try company director KYC methods
    → If all exhausted/blacklisted:
      → GlobalKycService.selectOptimalGlobalKyc()
        → Filter available entries
        → Prefer NIN over BVN
        → Order by success_rate DESC, usage_count ASC
        → Return optimal entry
  → callPalmPayWithKycFallback()
    → Call PalmPay API
    → On success: GlobalKycService.recordUsage(success=true)
    → On KYC error: Blacklist method, retry with next method
    → On failure: GlobalKycService.recordUsage(success=false)
```

## Components and Interfaces

### Backend Components

#### 1. KycPoolController (Enhanced)

**Location**: `app/Http/Controllers/Admin/KycPoolController.php`

**New/Modified Methods**:

```php
class KycPoolController extends Controller
{
    // GET /api/admin/kyc-pool/overview
    public function overview(): JsonResponse
    {
        // Returns: pool_stats, company_health, missing_vas
    }
    
    // GET /api/admin/kyc-pool/entries
    public function entries(): JsonResponse
    {
        // Returns: array of pool entries with masked numbers
    }
    
    // POST /api/admin/kyc-pool/add
    public function add(Request $request): JsonResponse
    {
        // Input: entries[], max_usage
        // Returns: added count, skipped count
    }
    
    // POST /api/admin/kyc-pool/company/{id}/assign-fresh
    public function assignFresh(int $companyId): JsonResponse
    {
        // Returns: success message with assigned KYC types
    }
    
    // POST /api/admin/kyc-pool/regenerate-missing
    public function regenerateMissing(Request $request): JsonResponse
    {
        // Input: company_id (optional)
        // Returns: created count, failed count, errors[]
    }
    
    // DELETE /api/admin/kyc-pool/{id}
    public function deactivate(int $id): JsonResponse
    {
        // Returns: success message
    }
    
    // Private helper
    private function getMissingVas(?int $companyId = null): array
    {
        // Returns: array of customers missing VAs
    }
}
```

#### 2. GlobalKycService (Existing, No Changes Needed)

**Location**: `app/Services/GlobalKycService.php`

The existing service already provides all necessary methods:
- `selectOptimalGlobalKyc()`: Smart KYC selection with NIN preference
- `recordUsage()`: Tracks usage, success/failure, auto-blacklisting
- `blacklistKyc()`: Manual blacklisting
- `getUsageStats()`: Pool statistics
- `getAvailableKycByType()`: Available count by type

#### 3. VirtualAccountService (Existing, No Changes Needed)

**Location**: `app/Services/PalmPay/VirtualAccountService.php`

The existing service already implements:
- `selectOptimalKycMethodWithGlobalFallback()`: Company KYC → Global fallback
- `callPalmPayWithKycFallback()`: Automatic retry with KYC rotation
- Global KYC usage tracking via GlobalKycService

### Frontend Components

#### 1. KycPoolManagement Component (Enhanced)

**Location**: `frontend/src/pages/admin/KycPoolManagement.js`

**State Management**:
```javascript
const [loading, setLoading] = useState(true);
const [overview, setOverview] = useState(null);
const [entries, setEntries] = useState([]);
const [addDialog, setAddDialog] = useState(false);
const [bulkText, setBulkText] = useState('');
const [bulkType, setBulkType] = useState('nin');
const [maxUsage, setMaxUsage] = useState(130);
const [adding, setAdding] = useState(false);
const [regenerating, setRegenerating] = useState(null);
```

**Key Functions**:
```javascript
// Load all dashboard data
const load = useCallback(async () => {
    const [ov, en] = await Promise.all([
        axios.get('/api/admin/kyc-pool/overview'),
        axios.get('/api/admin/kyc-pool/entries'),
    ]);
    setOverview(ov.data);
    setEntries(en.data.data);
});

// Add BVN/NIN to pool
const handleAdd = async () => {
    const lines = bulkText.split('\n').filter(Boolean);
    await axios.post('/api/admin/kyc-pool/add', {
        entries: lines.map(n => ({ type: bulkType, number: n })),
        max_usage: maxUsage,
    });
    load(); // Refresh dashboard
};

// Assign fresh KYC to company
const handleAssignFresh = async (companyId) => {
    await axios.post(`/api/admin/kyc-pool/company/${companyId}/assign-fresh`);
    load(); // Refresh dashboard
};

// Regenerate missing VAs
const handleRegenerate = async (companyId = null) => {
    setRegenerating(companyId || 'all');
    await axios.post('/api/admin/kyc-pool/regenerate-missing', 
        companyId ? { company_id: companyId } : {}
    );
    setRegenerating(null);
    load(); // Refresh dashboard
};

// Deactivate pool entry
const handleDeactivate = async (id) => {
    await axios.delete(`/api/admin/kyc-pool/${id}`);
    load(); // Refresh dashboard
};
```

### API Endpoints

| Method | Endpoint | Purpose | Request | Response |
|--------|----------|---------|---------|----------|
| GET | `/api/admin/kyc-pool/overview` | Dashboard data | - | pool_stats, company_health, missing_vas |
| GET | `/api/admin/kyc-pool/entries` | List pool entries | - | data: entries[] |
| POST | `/api/admin/kyc-pool/add` | Add BVN/NIN | entries[], max_usage | added, skipped |
| POST | `/api/admin/kyc-pool/company/{id}/assign-fresh` | Refresh company KYC | - | message |
| POST | `/api/admin/kyc-pool/regenerate-missing` | Regenerate VAs | company_id? | created, failed, errors[] |
| DELETE | `/api/admin/kyc-pool/{id}` | Deactivate entry | - | message |

## Data Models

### GlobalKycPool (Existing Model)

**Table**: `global_kyc_pool`

```php
Schema::create('global_kyc_pool', function (Blueprint $table) {
    $table->id();
    $table->enum('kyc_type', ['bvn', 'nin']);
    $table->string('kyc_number', 20)->unique();
    $table->boolean('is_active')->default(true);
    $table->integer('usage_count')->default(0);
    $table->integer('success_count')->default(0);
    $table->integer('failure_count')->default(0);
    $table->integer('max_usage')->nullable(); // Default: 130
    $table->timestamp('last_used_at')->nullable();
    $table->timestamp('last_success_at')->nullable();
    $table->timestamp('blacklisted_until')->nullable();
    $table->text('notes')->nullable();
    $table->timestamps();
    
    $table->index(['kyc_type', 'is_active']);
    $table->index('usage_count');
    $table->index('blacklisted_until');
});
```

**Key Fields**:
- `kyc_type`: 'bvn' or 'nin'
- `kyc_number`: The actual BVN or NIN (11-20 digits)
- `is_active`: Manual enable/disable flag
- `usage_count`: Total times used for VA creation
- `success_count`: Successful VA creations
- `failure_count`: Failed VA creation attempts
- `max_usage`: Maximum allowed usage before rotation (default: 130)
- `blacklisted_until`: Temporary blacklist expiration timestamp
- `success_rate`: Computed property = (success_count / usage_count) * 100

**Scopes**:
- `available()`: Active, not blacklisted, usage_count < max_usage
- `byType(string $type)`: Filter by 'bvn' or 'nin'
- `leastUsedFirst()`: Order by usage_count ASC, last_used_at ASC
- `highestSuccessFirst()`: Order by success_rate DESC, usage_count ASC

### GlobalKycUsageLog (Existing Model)

**Table**: `global_kyc_usage_logs`

```php
Schema::create('global_kyc_usage_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('global_kyc_id')->constrained('global_kyc_pool');
    $table->foreignId('company_id')->constrained('companies');
    $table->foreignId('virtual_account_id')->nullable()->constrained('virtual_accounts');
    $table->string('kyc_number', 20);
    $table->enum('kyc_type', ['bvn', 'nin']);
    $table->boolean('success');
    $table->text('error_message')->nullable();
    $table->json('request_data')->nullable();
    $table->timestamps();
    
    $table->index(['global_kyc_id', 'created_at']);
    $table->index(['company_id', 'success']);
});
```

**Purpose**: Audit trail for every global KYC usage, enabling:
- Success rate calculation
- Failure pattern analysis
- Company-specific KYC performance tracking
- Debugging VA creation issues

### Company Model (Enhanced Fields)

**Existing Fields Used**:
- `director_nin`: Primary director NIN
- `director_bvn`: Primary director BVN
- `kyc_method_blacklist`: JSON of temporarily blacklisted methods
- `kyc_refreshed_at`: Timestamp of last KYC refresh

**No schema changes required** - all necessary fields already exist.

### VirtualAccount Model (Existing)

**Relevant Fields**:
- `company_id`: Links to company
- `company_user_id`: Links to customer
- `user_id` / `uuid`: Customer identifier
- `kyc_source`: Tracks which KYC method was used
  - Values: 'customer_bvn', 'customer_nin', 'director_bvn', 'director_nin', 'global_bvn', 'global_nin', 'backup_director_N_bvn', 'backup_director_N_nin', 'company_rc'
- `status`: 'active', 'inactive', 'deleted'
- `deleted_at`: Soft delete timestamp

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system-essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*


### Property 1: KYC Entry Length Validation

*For any* BVN or NIN entry submitted for addition to the pool, the system should accept it if and only if its length is between 10 and 20 characters inclusive.

**Validates: Requirements 1.2**

### Property 2: Duplicate Entry Deduplication

*For any* set of KYC entries submitted for bulk addition, the system should skip entries that already exist in the pool and accurately report the count of added entries versus skipped duplicates.

**Validates: Requirements 1.3**

### Property 3: Max Usage Initialization

*For any* newly added KYC entry, the max_usage field should be set to the provided value (if specified and between 10-500) or default to 130.

**Validates: Requirements 1.4**

### Property 4: New Entry Initial State

*For any* newly created KYC entry, is_active should be true and usage_count should be 0.

**Validates: Requirements 1.5**

### Property 5: Bulk Entry Parsing

*For any* multi-line text input containing BVN/NIN entries, the system should correctly parse entries by splitting on newlines and trimming whitespace.

**Validates: Requirements 1.6**

### Property 6: Least-Used KYC Selection

*For any* available KYC pool state and type (NIN or BVN), when selecting the least-used entry, the system should return the entry with the lowest usage_count, breaking ties by earliest last_used_at timestamp.

**Validates: Requirements 2.1, 2.2, 2.13**

### Property 7: Company KYC Field Update

*For any* company and selected fresh KYC entries (NIN and/or BVN), after refresh the company's director_nin and director_bvn fields should match the selected entries' kyc_number values.

**Validates: Requirements 2.3**

### Property 8: Refresh Timestamp Update

*For any* successful KYC refresh operation, the company's kyc_refreshed_at field should be set to the current timestamp.

**Validates: Requirements 2.4**

### Property 9: Blacklist Clearing on Refresh

*For any* successful KYC refresh operation, the company's kyc_method_blacklist field should be set to null.

**Validates: Requirements 2.5**

### Property 10: Health Status Calculation

*For any* company with a known VA count and max limit, the health status should be:
- "healthy" if usage < 80% of limit
- "warning" if 80% <= usage <= 100% of limit  
- "critical" if director_nin is exhausted in the pool (regardless of percentage)

**Validates: Requirements 2.6, 2.7, 2.8, 2.9**

### Property 11: Refresh Audit Logging

*For any* successful KYC refresh operation, a log entry should be created containing company_id, assigned nin_kyc_id, and assigned bvn_kyc_id.

**Validates: Requirements 2.11**

### Property 12: NIN Preference Over BVN

*For any* KYC selection operation where both NIN and BVN entries are available with similar characteristics, the system should select the NIN entry.

**Validates: Requirements 2.12, 6.3**

### Property 13: Missing VA Detection

*For any* database state, the system should correctly identify all CompanyUser records that lack an associated non-deleted VirtualAccount entry.

**Validates: Requirements 3.1, 3.2**

### Property 14: Missing VA Filtering by Company

*For any* company_id filter applied to missing VA detection, the results should only include customers belonging to that specific company.

**Validates: Requirements 3.7**

### Property 15: Bulk VA Regeneration Scope

*For any* regeneration operation, if no company_id is specified, all missing VAs across all companies should be processed; if a company_id is specified, only that company's missing VAs should be processed.

**Validates: Requirements 4.1, 4.2**

### Property 16: VA Creation Parameters

*For any* virtual account creation during regeneration, the VirtualAccountService should be called with company_id, customer uuid, customer data (first_name, last_name, email, phone), bank_code "100033", and company_user_id.

**Validates: Requirements 4.5**

### Property 17: Success Counter Increment

*For any* successful virtual account creation during regeneration, the created counter should increase by exactly 1.

**Validates: Requirements 4.6**

### Property 18: Failure Counter and Error Logging

*For any* failed virtual account creation during regeneration, the failed counter should increase by 1 and the error message should be logged.

**Validates: Requirements 4.7**

### Property 19: Circuit Breaker Reset on Failure

*For any* failed virtual account creation during regeneration, the circuit_breaker cache should be reset before attempting the next customer.

**Validates: Requirements 4.8, 7.4**

### Property 20: Rate Limiting Delay

*For any* two consecutive virtual account creation attempts during regeneration, there should be a 2-second delay between them.

**Validates: Requirements 4.9**

### Property 21: Regeneration Summary Response

*For any* completed regeneration operation, the response should contain created count, failed count, and up to 10 error messages.

**Validates: Requirements 4.10**

### Property 22: Available Count Calculation

*For any* global KYC pool state, the available count should exclude entries where usage_count >= max_usage OR blacklisted_until is in the future.

**Validates: Requirements 5.2, 5.3**

### Property 23: Exhausted Count Calculation

*For any* global KYC pool state, the exhausted count should include entries where max_usage is set AND usage_count >= max_usage.

**Validates: Requirements 5.4**

### Property 24: Blacklisted Count Calculation

*For any* global KYC pool state, the blacklisted count should include entries where blacklisted_until is greater than the current time.

**Validates: Requirements 5.5**

### Property 25: KYC Number Masking

*For any* KYC number displayed to administrators, the masked version should show the first 5 characters followed by "***".

**Validates: Requirements 5.10**

### Property 26: Manual KYC Deactivation

*For any* active KYC entry, when an administrator clicks "Disable", the entry's is_active field should be set to false.

**Validates: Requirements 5.12**

### Property 27: Automatic Blacklisting on Low Success Rate

*For any* global KYC entry with at least 5 usage attempts and a success_rate below 20%, the system should automatically blacklist the entry.

**Validates: Requirements 5.13, 6.10**

### Property 28: Blacklist Duration

*For any* auto-blacklisted KYC entry, the blacklisted_until timestamp should be set to 24 hours from the current time.

**Validates: Requirements 5.14**

### Property 29: Automatic Blacklist Expiration

*For any* KYC entry with a blacklisted_until timestamp in the past, the entry should be considered not blacklisted (available if other conditions are met).

**Validates: Requirements 5.15**

### Property 30: Company KYC Priority

*For any* virtual account creation attempt, the system should first try the company's director_nin and director_bvn before querying the global KYC pool.

**Validates: Requirements 6.1, 6.2**

### Property 31: Global KYC Selection Ordering

*For any* global KYC pool query, entries should be ordered by highest success_rate first, then by lowest usage_count.

**Validates: Requirements 6.4**

### Property 32: Global KYC Selection Logging

*For any* global KYC entry selected for use, a log entry should be created containing kyc_id, kyc_type, kyc_number (masked), usage_count, and success_rate.

**Validates: Requirements 6.5**

### Property 33: Global KYC Usage Counter Increment

*For any* virtual account creation attempt using global KYC, the entry's usage_count should increase by 1.

**Validates: Requirements 6.6**

### Property 34: Global KYC Success Tracking

*For any* successful virtual account creation using global KYC, the entry's success_count should increase by 1 and last_success_at should be updated to the current timestamp.

**Validates: Requirements 6.7, 6.13**

### Property 35: Global KYC Failure Tracking

*For any* failed virtual account creation using global KYC, the entry's failure_count should increase by 1.

**Validates: Requirements 6.8**

### Property 36: Global KYC Usage Audit Log

*For any* global KYC usage (success or failure), a GlobalKycUsageLog record should be created containing global_kyc_id, company_id, virtual_account_id, kyc_number, kyc_type, success, error_message, and request_data.

**Validates: Requirements 6.9**

### Property 37: Global KYC Source Recording

*For any* virtual account created using global KYC, the kyc_source field should be set to "global_nin" or "global_bvn" based on the KYC type used.

**Validates: Requirements 6.12**

### Property 38: Batch Processing Resilience

*For any* regeneration batch containing multiple customers, individual VA creation failures should not prevent processing of remaining customers in the batch.

**Validates: Requirements 7.5, 7.6**

### Property 39: Circuit Breaker Reset Logging

*For any* circuit breaker reset operation, a log entry should be created containing the timestamp and reason for the reset.

**Validates: Requirements 7.7**

## Error Handling

### Input Validation Errors

**KYC Entry Length Validation**:
- **Error**: Entry length < 10 or > 20 characters
- **Response**: HTTP 422 with validation error message
- **User Action**: Correct the entry length and resubmit

**Max Usage Range Validation**:
- **Error**: max_usage < 10 or > 500
- **Response**: HTTP 422 with validation error message
- **User Action**: Provide a value between 10 and 500

### Business Logic Errors

**Empty Global KYC Pool**:
- **Error**: No available KYC entries in pool when refresh is requested
- **Response**: HTTP 400 with message "No available NIN or BVN in pool"
- **User Action**: Add more BVN/NIN entries to the pool before retrying

**All KYC Methods Exhausted**:
- **Error**: Company KYC exhausted and global pool empty during VA creation
- **Response**: Exception thrown with message "No KYC methods available: Company KYC exhausted and global pool empty"
- **User Action**: Add more entries to global pool or refresh company KYC

**Company Not Found**:
- **Error**: Invalid company_id provided to refresh or regenerate operations
- **Response**: HTTP 404 with message "Company not found"
- **User Action**: Verify company_id and retry

### External Service Errors

**PalmPay API Failures**:
- **Error**: PalmPay API returns error during VA creation
- **Handling**: 
  - If KYC-related error: Blacklist current KYC method, retry with next available method
  - If rate limit error: Respect 2-second delay, reset circuit breaker
  - If other error: Log error, increment failed counter, continue batch
- **User Visibility**: Error message included in regeneration summary (up to 10 errors)

**Circuit Breaker Activation**:
- **Error**: Too many consecutive PalmPay API failures
- **Handling**: Reset circuit breaker cache before each regeneration attempt
- **Prevention**: 2-second delay between requests, automatic reset on individual failures

### Data Integrity Errors

**Duplicate KYC Entry**:
- **Error**: Attempting to add KYC number that already exists in pool
- **Handling**: Skip the duplicate, increment skipped counter
- **User Visibility**: Summary shows "X added, Y skipped (duplicates)"

**Missing Customer Data**:
- **Error**: CompanyUser record lacks required fields (first_name, last_name, email, phone)
- **Handling**: Log error, increment failed counter, continue with next customer
- **User Visibility**: Error message in regeneration summary

### Concurrency Errors

**Concurrent KYC Refresh**:
- **Risk**: Multiple admins refreshing same company simultaneously
- **Mitigation**: Database-level unique constraints on company fields
- **Handling**: Last write wins, both operations succeed but second overwrites first

**Concurrent VA Creation**:
- **Risk**: Regeneration running while normal VA creation occurs
- **Mitigation**: VirtualAccountService already has deduplication logic (checks existing VA by user_id, email, phone)
- **Handling**: Duplicate VA creation attempts return existing VA

## Testing Strategy

### Unit Testing

Unit tests will focus on specific business logic functions and edge cases:

**KycPoolController Tests**:
- `test_add_validates_entry_length()`: Reject entries < 10 or > 20 chars
- `test_add_skips_duplicates()`: Verify duplicate detection and skip count
- `test_add_sets_default_max_usage()`: Verify default 130 when not specified
- `test_assign_fresh_selects_least_used()`: Verify least-used selection logic
- `test_assign_fresh_updates_company_fields()`: Verify director_nin/bvn update
- `test_assign_fresh_clears_blacklist()`: Verify kyc_method_blacklist cleared
- `test_assign_fresh_returns_error_when_pool_empty()`: Verify error message
- `test_regenerate_missing_resets_circuit_breaker()`: Verify cache clearing
- `test_regenerate_missing_respects_company_filter()`: Verify company_id filtering
- `test_regenerate_missing_continues_on_individual_failures()`: Verify batch resilience
- `test_get_missing_vas_excludes_deleted()`: Verify soft-delete filtering
- `test_get_missing_vas_filters_by_company()`: Verify company_id filtering

**GlobalKycService Tests**:
- `test_select_optimal_prefers_nin_over_bvn()`: Verify NIN preference
- `test_select_optimal_orders_by_success_rate()`: Verify ordering logic
- `test_select_optimal_excludes_blacklisted()`: Verify blacklist filtering
- `test_select_optimal_excludes_exhausted()`: Verify max_usage filtering
- `test_record_usage_increments_counters()`: Verify counter updates
- `test_record_usage_auto_blacklists_low_success_rate()`: Verify auto-blacklist at 20%
- `test_blacklist_sets_24_hour_expiration()`: Verify blacklist duration
- `test_is_blacklisted_respects_expiration()`: Verify expiration logic

**VirtualAccountService Tests** (existing tests, verify coverage):
- `test_create_va_tries_company_kyc_first()`: Verify priority order
- `test_create_va_falls_back_to_global_pool()`: Verify fallback logic
- `test_create_va_records_global_kyc_usage()`: Verify usage logging
- `test_create_va_sets_correct_kyc_source()`: Verify kyc_source field

**Frontend Component Tests**:
- `test_load_fetches_overview_and_entries()`: Verify parallel data loading
- `test_handle_add_parses_multiline_input()`: Verify newline splitting
- `test_handle_add_refreshes_dashboard()`: Verify refresh after add
- `test_handle_assign_fresh_refreshes_dashboard()`: Verify refresh after refresh
- `test_handle_regenerate_shows_loading_state()`: Verify loading indicator
- `test_handle_regenerate_refreshes_dashboard()`: Verify refresh after regenerate

### Property-Based Testing

Property tests will verify universal behaviors across randomized inputs. Each test should run a minimum of 100 iterations.

**Property Test 1: KYC Entry Length Validation**
```php
/**
 * Feature: kyc-pool-management-enhancements, Property 1:
 * For any BVN or NIN entry, accept if length is between 10-20 chars
 */
public function test_property_kyc_entry_length_validation()
{
    // Generate random strings of various lengths (5-25 chars)
    // Assert: Entries 10-20 chars are accepted, others rejected
}
```

**Property Test 2: Duplicate Entry Deduplication**
```php
/**
 * Feature: kyc-pool-management-enhancements, Property 2:
 * For any set of entries with duplicates, skip duplicates and report accurate counts
 */
public function test_property_duplicate_entry_deduplication()
{
    // Generate random sets of entries with known duplicates
    // Assert: added + skipped = total submitted, no duplicates in DB
}
```

**Property Test 3: Least-Used KYC Selection**
```php
/**
 * Feature: kyc-pool-management-enhancements, Property 6:
 * For any pool state, least-used selection returns entry with lowest usage_count
 */
public function test_property_least_used_kyc_selection()
{
    // Generate random pool states with varying usage_counts
    // Assert: Selected entry has minimum usage_count among available
}
```

**Property Test 4: Health Status Calculation**
```php
/**
 * Feature: kyc-pool-management-enhancements, Property 10:
 * For any company with VA count and limit, status matches threshold rules
 */
public function test_property_health_status_calculation()
{
    // Generate random companies with varying VA counts and limits
    // Assert: Status is "healthy" (<80%), "warning" (80-100%), or "critical" (exhausted)
}
```

**Property Test 5: Missing VA Detection**
```php
/**
 * Feature: kyc-pool-management-enhancements, Property 13:
 * For any database state, correctly identify all customers without non-deleted VAs
 */
public function test_property_missing_va_detection()
{
    // Generate random sets of customers with/without VAs, some soft-deleted
    // Assert: Detected missing VAs = customers without non-deleted VAs
}
```

**Property Test 6: Available Count Calculation**
```php
/**
 * Feature: kyc-pool-management-enhancements, Property 22:
 * For any pool state, available count excludes exhausted and blacklisted entries
 */
public function test_property_available_count_calculation()
{
    // Generate random pool states with varying usage, max_usage, blacklist status
    // Assert: Available count = entries where usage < max AND not blacklisted
}
```

**Property Test 7: Global KYC Usage Tracking**
```php
/**
 * Feature: kyc-pool-management-enhancements, Property 33-36:
 * For any global KYC usage, counters increment and logs are created correctly
 */
public function test_property_global_kyc_usage_tracking()
{
    // Generate random VA creation attempts using global KYC
    // Assert: usage_count increments, success/failure counts update, logs created
}
```

**Property Test 8: Batch Processing Resilience**
```php
/**
 * Feature: kyc-pool-management-enhancements, Property 38:
 * For any batch with failures, remaining customers are still processed
 */
public function test_property_batch_processing_resilience()
{
    // Generate random batches where some customers will fail VA creation
    // Assert: All customers attempted, failures don't stop batch
}
```

**Property Test 9: KYC Number Masking**
```php
/**
 * Feature: kyc-pool-management-enhancements, Property 25:
 * For any KYC number, masked version shows first 5 chars + "***"
 */
public function test_property_kyc_number_masking()
{
    // Generate random KYC numbers of varying lengths
    // Assert: Masked version = substr(0, 5) + "***"
}
```

**Property Test 10: NIN Preference Over BVN**
```php
/**
 * Feature: kyc-pool-management-enhancements, Property 12:
 * For any selection where both NIN and BVN available, NIN is selected
 */
public function test_property_nin_preference_over_bvn()
{
    // Generate random pool states with both NIN and BVN entries
    // Assert: Selected entry is NIN type when both available
}
```

### Integration Testing

Integration tests will verify end-to-end flows across multiple components:

**Test: Complete KYC Refresh Flow**
1. Populate global pool with test entries
2. Create test company with exhausted director KYC
3. Call assignFresh() API endpoint
4. Verify company fields updated
5. Verify blacklist cleared
6. Verify health status recalculated
7. Verify audit log created

**Test: Complete VA Regeneration Flow**
1. Create test company with customers missing VAs
2. Populate global pool with test entries
3. Call regenerateMissing() API endpoint
4. Verify VAs created for all customers
5. Verify global KYC usage tracked
6. Verify circuit breaker reset between attempts
7. Verify summary response accurate

**Test: Global KYC Fallback Flow**
1. Create company with exhausted director KYC
2. Populate global pool with test entries
3. Attempt VA creation via VirtualAccountService
4. Verify company KYC tried first
5. Verify global pool queried on failure
6. Verify global KYC usage logged
7. Verify VA created with correct kyc_source

**Test: Auto-Blacklisting Flow**
1. Create global KYC entry
2. Simulate 5 VA creation attempts with 4 failures (20% success rate)
3. Verify entry auto-blacklisted
4. Verify blacklisted_until set to now() + 24 hours
5. Verify entry excluded from available() scope
6. Wait 24 hours (or mock time)
7. Verify entry available again

### Manual Testing Checklist

**Dashboard UI Testing**:
- [ ] Pool statistics cards display correct counts
- [ ] Company health table shows accurate VA usage
- [ ] Missing VA list displays all customers without VAs
- [ ] Add BVN/NIN dialog accepts multiline input
- [ ] Refresh button updates company KYC and refreshes dashboard
- [ ] Regenerate All button processes all missing VAs
- [ ] Fix button processes only selected company's missing VAs
- [ ] Disable button deactivates pool entries
- [ ] Loading indicators appear during operations
- [ ] Success/error notifications display correctly
- [ ] KYC numbers are masked (first 5 chars + ***)
- [ ] Progress bars show correct colors (green/yellow/red)

**Error Handling Testing**:
- [ ] Adding invalid length entries shows validation error
- [ ] Refreshing with empty pool shows error message
- [ ] Regenerating with no missing VAs shows success message
- [ ] PalmPay API failures don't crash regeneration
- [ ] Circuit breaker resets allow continued processing
- [ ] Duplicate entries are skipped with correct count

**Performance Testing**:
- [ ] Dashboard loads within 2 seconds with 100+ pool entries
- [ ] Bulk add of 50 entries completes within 5 seconds
- [ ] Regeneration of 100 VAs completes within 5 minutes (2s delay each)
- [ ] Parallel data fetching improves load time vs sequential

