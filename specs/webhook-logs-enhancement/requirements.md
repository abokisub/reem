# Requirements Document

## Introduction

This feature enhances the webhook logs display pages for both admin and company users to show BOTH incoming webhooks (from PalmPay) and outgoing webhooks (to companies). Currently, the webhook logs pages only display outgoing webhooks from the `webhook_logs` table, which results in empty displays when no outgoing webhooks have been configured. The system already receives and stores incoming webhooks from PalmPay in the `palmpay_webhooks` table, but these are not visible to users. This enhancement will provide complete webhook visibility, allowing admins to monitor PalmPay webhook activity without logging into the PalmPay dashboard.

## Glossary

- **Webhook_Display_System**: The combined frontend and backend components that present webhook logs to users
- **Admin_User**: A user with admin privileges who can view all webhook activity across all companies
- **Company_User**: A regular user associated with a specific company who can view only their company's webhook activity
- **Incoming_Webhook**: A webhook received by the system from PalmPay, stored in the `palmpay_webhooks` table
- **Outgoing_Webhook**: A webhook sent by the system to a company's configured webhook URL, stored in the `webhook_logs` table
- **Webhook_Direction**: An indicator showing whether a webhook is incoming (from PalmPay) or outgoing (to company)
- **Admin_Webhook_Page**: The webhook logs page at `/secure/webhooks` accessible to admin users
- **Company_Webhook_Page**: The webhook logs page at `/dashboard/webhooks` accessible to company users
- **PalmPay**: The payment provider that sends incoming webhooks to the system
- **Transaction_Association**: The relationship between a webhook and its corresponding transaction record

## Requirements

### Requirement 1: Display Incoming Webhooks on Admin Page

**User Story:** As an admin, I want to see incoming webhooks from PalmPay on the admin webhook logs page, so that I can monitor PalmPay webhook activity without logging into the PalmPay dashboard.

#### Acceptance Criteria

1. WHEN an Admin_User accesses the Admin_Webhook_Page, THE Webhook_Display_System SHALL retrieve records from the `palmpay_webhooks` table
2. WHEN displaying incoming webhooks, THE Webhook_Display_System SHALL include the event type, status, timestamp, and associated company name
3. FOR ALL incoming webhooks displayed, THE Webhook_Display_System SHALL show "Incoming from PalmPay" as the Webhook_Direction
4. WHEN an incoming webhook has a Transaction_Association, THE Webhook_Display_System SHALL display the associated company name
5. WHEN an incoming webhook has no Transaction_Association, THE Webhook_Display_System SHALL display "N/A" for the company name

### Requirement 2: Display Outgoing Webhooks on Admin Page

**User Story:** As an admin, I want to see outgoing webhooks sent to companies on the admin webhook logs page, so that I can monitor webhook delivery status to company endpoints.

#### Acceptance Criteria

1. WHEN an Admin_User accesses the Admin_Webhook_Page, THE Webhook_Display_System SHALL retrieve records from the `webhook_logs` table
2. WHEN displaying outgoing webhooks, THE Webhook_Display_System SHALL include the event type, webhook URL, HTTP status, delivery status, attempt number, and timestamp
3. FOR ALL outgoing webhooks displayed, THE Webhook_Display_System SHALL show "Outgoing to Company" as the Webhook_Direction
4. WHEN an outgoing webhook is associated with a company, THE Webhook_Display_System SHALL display the company name

### Requirement 3: Combine and Sort Webhook Records for Admin

**User Story:** As an admin, I want to see both incoming and outgoing webhooks in a single unified list sorted by time, so that I can understand the complete webhook flow chronologically.

#### Acceptance Criteria

1. WHEN the Admin_Webhook_Page displays webhooks, THE Webhook_Display_System SHALL combine records from both `palmpay_webhooks` and `webhook_logs` tables
2. THE Webhook_Display_System SHALL sort the combined webhook list by timestamp in descending order (newest first)
3. WHEN paginating webhook records, THE Webhook_Display_System SHALL maintain the chronological sort order across both incoming and outgoing webhooks
4. THE Webhook_Display_System SHALL support pagination with configurable page sizes (5, 10, 20, 50 records per page)

### Requirement 4: Display Incoming Webhooks on Company Page

**User Story:** As a company user, I want to see incoming webhooks from PalmPay for my transactions, so that I can monitor when PalmPay sends webhook notifications about my company's transactions.

#### Acceptance Criteria

1. WHEN a Company_User accesses the Company_Webhook_Page, THE Webhook_Display_System SHALL retrieve records from the `palmpay_webhooks` table filtered by the user's company
2. WHEN filtering incoming webhooks for a company, THE Webhook_Display_System SHALL use the Transaction_Association to match webhooks to the company
3. FOR ALL incoming webhooks displayed to a Company_User, THE Webhook_Display_System SHALL show "Incoming from PalmPay" as the Webhook_Direction
4. WHEN displaying incoming webhooks to a Company_User, THE Webhook_Display_System SHALL include the event type, status, transaction reference, transaction amount, and timestamp

### Requirement 5: Display Outgoing Webhooks on Company Page

**User Story:** As a company user, I want to see outgoing webhooks sent to my webhook URL, so that I can monitor webhook delivery status and troubleshoot integration issues.

#### Acceptance Criteria

1. WHEN a Company_User accesses the Company_Webhook_Page, THE Webhook_Display_System SHALL retrieve records from the `webhook_logs` table filtered by the user's company
2. FOR ALL outgoing webhooks displayed to a Company_User, THE Webhook_Display_System SHALL show "Outgoing to Your Endpoint" as the Webhook_Direction
3. WHEN displaying outgoing webhooks to a Company_User, THE Webhook_Display_System SHALL include the event type, webhook URL, HTTP status, delivery status, attempt number, and timestamp

### Requirement 6: Combine and Sort Webhook Records for Company

**User Story:** As a company user, I want to see both incoming and outgoing webhooks in a single unified list sorted by time, so that I can understand the complete webhook flow for my transactions.

#### Acceptance Criteria

1. WHEN the Company_Webhook_Page displays webhooks, THE Webhook_Display_System SHALL combine records from both `palmpay_webhooks` and `webhook_logs` tables filtered by the company
2. THE Webhook_Display_System SHALL sort the combined webhook list by timestamp in descending order (newest first)
3. WHEN paginating webhook records, THE Webhook_Display_System SHALL maintain the chronological sort order across both incoming and outgoing webhooks
4. THE Webhook_Display_System SHALL support pagination with configurable page sizes (5, 10, 20, 50 records per page)

### Requirement 7: Visual Distinction Between Webhook Types

**User Story:** As a user (admin or company), I want to easily distinguish between incoming and outgoing webhooks, so that I can quickly understand the webhook flow direction.

#### Acceptance Criteria

1. THE Webhook_Display_System SHALL display a direction indicator column showing whether each webhook is incoming or outgoing
2. WHEN displaying incoming webhooks, THE Webhook_Display_System SHALL use a distinct visual indicator (such as a badge or icon) labeled "Incoming"
3. WHEN displaying outgoing webhooks, THE Webhook_Display_System SHALL use a distinct visual indicator (such as a badge or icon) labeled "Outgoing"
4. THE Webhook_Display_System SHALL use different colors for incoming and outgoing direction indicators to enhance visual distinction

### Requirement 8: Preserve Existing Functionality

**User Story:** As a user, I want all existing webhook log features to continue working, so that the enhancement does not break current functionality.

#### Acceptance Criteria

1. THE Webhook_Display_System SHALL maintain all existing table columns for outgoing webhooks (event type, webhook URL, HTTP status, status, attempts, date)
2. THE Webhook_Display_System SHALL preserve the existing status color coding (success = green, failed = red)
3. THE Webhook_Display_System SHALL maintain the existing dense padding toggle functionality
4. THE Webhook_Display_System SHALL preserve the existing pagination controls and behavior
5. WHEN no webhooks exist (neither incoming nor outgoing), THE Webhook_Display_System SHALL display the existing "No results found" message

### Requirement 9: Handle Missing Data Gracefully

**User Story:** As a user, I want the system to handle missing or incomplete webhook data gracefully, so that I can still view available webhook information even when some fields are missing.

#### Acceptance Criteria

1. WHEN an incoming webhook has no associated transaction, THE Webhook_Display_System SHALL display "N/A" for transaction-related fields
2. WHEN an outgoing webhook has no HTTP status, THE Webhook_Display_System SHALL display "N/A" for the HTTP status field
3. WHEN an incoming webhook has no company association, THE Webhook_Display_System SHALL display "N/A" for the company name field
4. WHEN webhook URL is not applicable (for incoming webhooks), THE Webhook_Display_System SHALL display "N/A" for the webhook URL field

### Requirement 10: API Response Structure Compatibility

**User Story:** As a developer, I want the API to return webhook data in a consistent structure, so that the frontend can reliably parse and display the information.

#### Acceptance Criteria

1. THE Webhook_Display_System SHALL return webhook data with a consistent schema for both incoming and outgoing webhooks
2. FOR ALL webhook records returned, THE Webhook_Display_System SHALL include an identifier field indicating the webhook direction
3. THE Webhook_Display_System SHALL normalize field names across incoming and outgoing webhooks where they represent the same concept
4. WHEN returning paginated results, THE Webhook_Display_System SHALL include standard pagination metadata (total, per_page, current_page)
