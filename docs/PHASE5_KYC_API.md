# Phase 5: KYC API Documentation

## Overview
This document provides API endpoint documentation for the KYC (Know Your Customer) system implemented in Phase 5.

## Authentication
All endpoints require authentication via the `auth.token` middleware. Include the bearer token in the Authorization header:
```
Authorization: Bearer {your_token}
```

---

## Company KYC Endpoints

### Get KYC Status
**GET** `/api/v1/kyc/status`

Returns the current KYC status for the authenticated company.

**Response:**
```json
{
  "success": true,
  "data": {
    "overall_status": "under_review",
    "reviewed_at": null,
    "reviewed_by": null,
    "rejection_reason": null,
    "sections": {
      "business_info": {
        "status": "approved",
        "reviewed_at": "2026-02-17T14:00:00Z",
        "reviewed_by": {...},
        "rejection_reason": null
      },
      "account_info": {
        "status": "pending",
        ...
      }
    },
    "history": [...]
  }
}
```

### Submit KYC Section
**POST** `/api/v1/kyc/submit/{section}`

Submit a KYC section for admin review.

**Sections:** `business_info`, `account_info`, `bvn_info`, `documents`, `board_members`

**Request Body:**
```json
{
  "data": {
    "company_name": "Example Corp",
    "company_type": "Limited Liability",
    ...
  }
}
```

**Response:**
```json
{
  "success": true,
  "message": "KYC section submitted successfully",
  "data": {
    "id": 1,
    "company_id": 123,
    "section": "business_info",
    "status": "pending",
    ...
  }
}
```

### Verify BVN
**POST** `/api/v1/kyc/verify-bvn`

Verify a Bank Verification Number via EaseID.

**Request Body:**
```json
{
  "bvn": "12345678901"
}
```

**Response:**
```json
{
  "success": true,
  "message": "BVN verified successfully",
  "data": {
    "bvn": "12345678901",
    "firstName": "John",
    "lastName": "Doe",
    "gender": "Male",
    "birthday": "1990-01-01",
    "photo": "base64_encoded_image"
  }
}
```

### Verify NIN
**POST** `/api/v1/kyc/verify-nin`

Verify a National Identification Number via EaseID.

**Request Body:**
```json
{
  "nin": "12345678901"
}
```

**Response:** Similar to BVN verification

### Verify Bank Account
**POST** `/api/v1/kyc/verify-bank-account`

Verify bank account ownership.

**Request Body:**
```json
{
  "account_number": "1234567890",
  "bank_code": "058"
}
```

---

## Document Management Endpoints

### List Documents
**GET** `/api/v1/documents`

List all uploaded documents for the authenticated company.

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "path": "kyc_documents/123/cac_certificate_123_1234567890_abc123.pdf",
      "filename": "cac_certificate_123_1234567890_abc123.pdf",
      "size": 524288,
      "last_modified": 1708185600
    }
  ]
}
```

### Upload Document
**POST** `/api/v1/documents/upload`

Upload a KYC document.

**Request (multipart/form-data):**
- `type`: `cac_certificate`, `utility_bill`, `id_card`, `director_id`, `bank_statement`, `other`
- `file`: File (max 5MB)

**Response:**
```json
{
  "success": true,
  "message": "Document uploaded successfully",
  "data": {
    "path": "kyc_documents/123/cac_certificate_123_1234567890_abc123.pdf",
    "filename": "cac_certificate_123_1234567890_abc123.pdf",
    "type": "cac_certificate",
    "size": 524288,
    "mime_type": "application/pdf"
  }
}
```

### Delete Document
**DELETE** `/api/v1/documents/delete`

Delete a document.

**Request Body:**
```json
{
  "path": "kyc_documents/123/cac_certificate_123_1234567890_abc123.pdf"
}
```

---

## Admin KYC Endpoints
**Requires admin role**

### List Pending Submissions
**GET** `/api/admin/kyc/pending`

List all pending KYC submissions.

**Query Parameters:**
- `section` (optional): Filter by section
- `per_page` (optional): Items per page (default: 20)

### List All Submissions
**GET** `/api/admin/kyc/submissions`

List all KYC submissions with filters.

**Query Parameters:**
- `status` (optional): `pending`, `approved`, `rejected`
- `section` (optional): Filter by section
- `company_id` (optional): Filter by company
- `per_page` (optional): Items per page

### Get Company KYC Details
**GET** `/api/admin/kyc/company/{companyId}`

Get complete KYC details for a specific company.

### Approve Section
**POST** `/api/admin/kyc/approve/{companyId}/{section}`

Approve a KYC section.

**Request Body:**
```json
{
  "notes": "All documents verified"
}
```

### Reject Section
**POST** `/api/admin/kyc/reject/{companyId}/{section}`

Reject a KYC section.

**Request Body:**
```json
{
  "reason": "Missing CAC certificate"
}
```

### Get KYC Statistics
**GET** `/api/admin/kyc/stats`

Get overall KYC statistics.

**Response:**
```json
{
  "success": true,
  "data": {
    "total_companies": 150,
    "kyc_pending": 45,
    "kyc_under_review": 30,
    "kyc_approved": 60,
    "kyc_rejected": 15,
    "pending_sections": 75
  }
}
```

---

## Error Responses

All endpoints return standard error responses:

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

**HTTP Status Codes:**
- `200`: Success
- `201`: Created
- `400`: Bad Request
- `401`: Unauthorized
- `403`: Forbidden
- `404`: Not Found
- `422`: Validation Error
- `500`: Server Error
