# Company Detail Page Upgrade - TODO List

## Issues to Fix:
1. ✅ Wallet balance showing NaN - FIXED
2. ❌ Documents not showing - NEEDS FIX
3. ❌ Bank code showing "N/A" - NEEDS FIX  
4. ❌ Manual bank entry instead of dropdown - NEEDS FIX
5. ❌ No bank account verification - NEEDS FIX
6. ❌ Page not professional looking - NEEDS REDESIGN

## What Needs to Be Done:

### Backend (Laravel):
1. **Document Management API**
   - Create endpoints to upload/view/delete documents
   - Store documents in `storage/app/company_documents/{company_id}/`
   - Return document URLs in company detail API

2. **Bank Verification**
   - Use existing `/api/gateway/banks/verify` endpoint
   - Integrate with admin company update

3. **Enhanced Company Detail API**
   - Include documents in response
   - Include onboarding checklist status
   - Include all KYC information

### Frontend (React):
1. **Document Section**
   - Show all uploaded documents with thumbnails
   - Allow viewing full documents
   - Allow replacing documents
   - Show upload status

2. **Bank Selector**
   - Dropdown with all Nigerian banks
   - Auto-verify account number
   - Show verified account name
   - No more manual bank code entry

3. **Professional Layout**
   - Clean sections with proper spacing
   - Status badges and indicators
   - Responsive design
   - Loading states

4. **Onboarding Checklist**
   - Visual progress indicator
   - Show what's completed/pending
   - Different view before/after activation

## Files to Modify:

### Backend:
- `app/Http/Controllers/Admin/CompanyKycController.php` - Add document methods
- `app/Http/Controllers/Admin/DocumentController.php` - NEW FILE
- `routes/api.php` - Add document routes

### Frontend:
- `frontend/src/pages/admin/companies/detail.js` - Complete redesign
- `frontend/src/components/BankSelector.js` - NEW COMPONENT
- `frontend/src/components/DocumentViewer.js` - NEW COMPONENT

## Estimated Time:
- Backend: 2-3 hours
- Frontend: 3-4 hours
- Testing: 1 hour
- **Total: 6-8 hours**

## Priority:
**HIGH** - This affects admin's ability to properly onboard companies

## Next Session:
We'll implement this step by step:
1. First: Backend document management
2. Second: Bank verification integration
3. Third: Frontend redesign
4. Fourth: Testing and polish
