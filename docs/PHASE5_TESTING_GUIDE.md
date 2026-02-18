# Phase 5 Testing Guide

## ✅ Sandbox Mode Enabled!

Sandbox mode is now active - KYC will auto-approve instantly!

## Quick Start: Register & Test

### Step 1: Register New Company
Go to: `http://localhost:3000/register`

**Test Credentials:**
- First Name: Test
- Last Name: User
- Business Name: Test Company Ltd
- Email: test@example.com
- Phone: 08012345678
- Password: test1234

### Step 2: Login & Submit KYC

After registration, log in and submit KYC sections:

**In Sandbox Mode:**
- ✅ KYC sections **instantly approved** on submission
- ✅ No admin approval needed
- ✅ No waiting time

### Step 3: Test Mock Verifications

**Mock BVN Verification:**
```
Success: Use BVN starting with "2" (e.g., 22222222222)
Failure: Use any other BVN
```

**Mock NIN Verification:**
```
Success: Use NIN starting with "3" (e.g., 33333333333)
Failure: Use any other NIN
```

**Mock CAC Verification:**
```
Success: Use CAC starting with "RC" (e.g., RC123456)
Failure: Use any other format
```

## Admin Access (Optional)

If you want to test manual approval:

**Admin Login:**
- Email: `admin@pointpay.com`
- Password: `admin1234`

**Admin Features:**
- View all companies
- Approve/reject KYC sections
- Approve/reject individual documents
- View KYC statistics

## Phase 5 Features to Test

### 1. Partial KYC Status
- Submit only 2-3 sections (not all 5)
- Company status should be `partial`
- Submit remaining sections
- Status should change to `approved`

### 2. Document-Level Approval
- Upload multiple documents
- Each document can be approved/rejected individually
- Check document status in admin panel

### 3. Sandbox Auto-Approval
- Submit any KYC section
- Should instantly approve (no waiting)
- Check company status updates immediately

### 4. KYC Audit Trail
- All actions logged in `company_kyc_history`
- Check history for timestamps and admin notes

## API Endpoints for Testing

### Company KYC Endpoints
```
GET  /api/v1/kyc/status
POST /api/v1/kyc/submit/{section}
POST /api/v1/kyc/verify-bvn
POST /api/v1/kyc/verify-nin
```

### Document Endpoints
```
POST /api/v1/documents/upload
GET  /api/v1/documents
DELETE /api/v1/documents/{id}
```

### Admin Endpoints
```
GET  /api/admin/kyc/pending
POST /api/admin/kyc/approve/{companyId}/{section}
POST /api/admin/kyc/reject/{companyId}/{section}
GET  /api/admin/documents/company/{companyId}
POST /api/admin/documents/{documentId}/approve
POST /api/admin/documents/{documentId}/reject
```

### Sandbox Testing Endpoints
```
GET  /api/sandbox/kyc/guide
POST /api/sandbox/kyc/auto-approve/{section}
POST /api/sandbox/kyc/auto-reject/{section}
POST /api/sandbox/kyc/mock-verify-bvn
POST /api/sandbox/kyc/mock-verify-nin
POST /api/sandbox/kyc/mock-verify-cac
```

## Troubleshooting

**Issue: KYC not auto-approving**
- Check `.env` has `SANDBOX_MODE=true`
- Restart Laravel server: `php artisan serve`

**Issue: Can't log in**
- Admin: `admin@pointpay.com` / `admin1234`
- Make sure email is correct (not pointwave)

**Issue: Documents not uploading**
- Check `storage/app/kyc_documents` exists
- Run: `php artisan storage:link`

## Next Steps

1. ✅ Register new company
2. ✅ Test KYC submission (auto-approves instantly)
3. ✅ Test mock BVN/NIN verification
4. ✅ Test document upload
5. ✅ Check admin panel

Phase 5 is ready for testing! 🚀
